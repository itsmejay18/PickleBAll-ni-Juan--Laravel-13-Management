<?php

namespace App\Http\Controllers;

use App\Mail\ReservationReceiptMail;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class XPayLinkController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications
    ) {}

    /**
     * Create an XPayLink session and redirect the user.
     */
    public function redirect(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();

        // Check permission: owner or staff/admin
        $isStaffOrAdmin = $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]);
        $isOwner = (int) $reservation->user_id === (int) $user->id;

        abort_unless($isStaffOrAdmin || $isOwner, 403, 'Unauthorized action.');

        if (in_array($reservation->status, ['cancelled', 'no_show', 'refunded', 'completed', 'confirmed'], true)) {
            return back()->with('error', 'This reservation does not require payment at this stage.');
        }

        $isEnabled = filter_var(SystemSetting::value('xpaylink_enabled', 'false'), FILTER_VALIDATE_BOOLEAN);
        $publicKey = SystemSetting::value('xpaylink_public_key');
        $secretKey = SystemSetting::value('xpaylink_secret_key');
        $endpoint = SystemSetting::value('xpaylink_endpoint', 'https://synthwave.space/api/create-session.php');

        if (! $isEnabled || empty($publicKey) || empty($secretKey)) {
            return back()->with('error', 'Automatic payments are not configured properly. Please contact the administrator.');
        }

        // Get customer name from profile or fallback to email
        $profile = $reservation->user->endUserProfile
            ?? $reservation->user->staffProfile
            ?? $reservation->user->adminProfile;
        $customerName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? '')) ?: $reservation->user->email;

        // Build Payload
        $payload = [
            'external_bill_id' => $reservation->reservation_code,
            'customer_name' => $customerName,
            'amount' => (float) $reservation->grand_total,
            'callback_url' => str_replace('http://', 'https://', route('payments.xpaylink.webhook')),
            'success_url' => str_replace('http://', 'https://', route('modules.show', 'receipts')),
            'return_url' => str_replace('http://', 'https://', route('modules.show', 'receipts')),
            'failed_url' => str_replace('http://', 'https://', route('bookings.pay', $reservation->reservation_code)),
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-PayLink-Key' => $publicKey,
                'X-PayLink-Secret' => $secretKey,
            ])->post($endpoint, $payload);

            if ($response->successful() && $response->json('success') === true) {
                $sessionId = $response->json('session_id');
                $paymentUrl = $response->json('payment_url');

                // Record / Update pending payment
                Payment::query()->updateOrCreate(
                    ['reservation_id' => $reservation->id, 'payment_reference' => 'XPAY-'.$reservation->reservation_code],
                    [
                        'amount' => $reservation->grand_total,
                        'payment_method' => 'gcash',
                        'payment_type' => 'full',
                        'status' => 'pending',
                        'xpaylink_session_id' => $sessionId,
                        'created_by' => $user->id,
                    ]
                );

                return redirect()->away($paymentUrl);
            }

            $errorMessage = $response->json('message') ?? 'Could not create a payment session. Please try again.';

            return back()->with('error', 'Payment Gateway Error: '.$errorMessage);

        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to reach the payment gateway. Please try again later.');
        }
    }

    /**
     * Webhook endpoint called by XPayLink when payment status changes.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secretKey = SystemSetting::value('xpaylink_secret_key');
        $raw = $request->getContent();
        $signature = $request->header('X-PayLink-Signature') ?? '';

        if (empty($secretKey)) {
            return response()->json(['error' => 'Secret key not configured'], 500);
        }

        $expected = hash_hmac('sha256', $raw, $secretKey);

        if (empty($signature) || ! hash_equals($expected, $signature)) {
            \Illuminate\Support\Facades\Log::warning('XPayLink Webhook: Signature verification failed.', [
                'received_signature' => $signature,
                'expected_signature' => $expected,
                'raw_payload' => $raw,
                'secret_key_obfuscated' => empty($secretKey) ? 'empty' : (substr($secretKey, 0, 4) . '...' . substr($secretKey, -4))
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON payload'], 400);
        }

        $event = $payload['event'] ?? '';
        $status = $payload['status'] ?? '';

        if ($event === 'payment.paid' && $status === 'paid') {
            $reservationCode = $payload['external_bill_id'] ?? '';
            $sessionId = $payload['session_id'] ?? '';

            if (str_starts_with($reservationCode, 'OPREG-')) {
                $registrationId = (int) str_replace('OPREG-', '', $reservationCode);
                $registration = \App\Models\OpenPlayRegistration::find($registrationId);

                if (! $registration) {
                    return response()->json(['error' => 'Open Play registration not found'], 404);
                }

                if ($registration->status === 'registered') {
                    return response()->json(['status' => 'ignored', 'message' => 'Registration is already paid.']);
                }

                $registration->update([
                    'status' => 'registered',
                    'amount_paid' => $registration->event->entrance_fee,
                    'registered_at' => now(),
                ]);

                $systemUser = User::query()->role(User::ROLE_SUPER_ADMIN)->first()
                    ?? User::query()->role(User::ROLE_ADMIN)->first()
                    ?? User::query()->first();

                $this->audit->log('open_play.payment_approved', 'open_play_registrations', $registration->id, $systemUser, [
                    'xpaylink_session_id' => $sessionId,
                ]);

                return response()->json(['success' => true, 'message' => 'Open Play payment processed successfully.']);
            }

            $reservation = Reservation::query()->where('reservation_code', $reservationCode)->first();

            if (! $reservation) {
                return response()->json(['error' => 'Reservation not found'], 404);
            }

            // If already verified, return success idempotently
            if ($reservation->status === 'confirmed' && $reservation->payment_status === 'paid') {
                return response()->json(['status' => 'ignored', 'message' => 'Reservation is already confirmed.']);
            }

            // Find a system user to associate with the approval audit log/verified_by
            $systemUser = User::query()->role(User::ROLE_SUPER_ADMIN)->first()
                ?? User::query()->role(User::ROLE_ADMIN)->first()
                ?? User::query()->first();

            DB::transaction(function () use ($reservation, $payload, $sessionId, $systemUser, $request) {
                // 1. Update / Create payment row
                $payment = Payment::query()->updateOrCreate(
                    ['reservation_id' => $reservation->id, 'xpaylink_session_id' => $sessionId],
                    [
                        'payment_reference' => 'XPAY-'.$reservation->reservation_code,
                        'amount' => $payload['amount'] ?? $reservation->grand_total,
                        'payment_method' => 'gcash',
                        'payment_type' => 'full',
                        'status' => 'verified',
                        'gcash_reference_number' => $sessionId,
                        'verified_at' => now(),
                        'verified_by' => $systemUser?->id,
                        'notes' => 'Automated payment verification via XPayLink webhook.',
                        'created_by' => $reservation->user_id,
                    ]
                );

                // 2. Update reservation status
                $reservation->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'confirmed_at' => now(),
                    'confirmed_by' => $systemUser?->id,
                ]);

                // 3. Log transaction
                DB::table('gcash_transactions_log')->updateOrInsert(
                    ['reference_number' => $sessionId],
                    [
                        'payment_id' => $payment->id,
                        'user_id' => $reservation->user_id,
                        'owner_gcash_number' => 'XPAYLINK',
                        'user_sent_from_number' => $payload['sender_gcash_number_masked'] ?? null,
                        'screenshot_path' => null,
                        'amount' => $payload['amount'] ?? $reservation->grand_total,
                        'status' => 'verified',
                        'verification_notes' => 'Verified automatically via XPayLink webhook.',
                        'verified_by' => $systemUser?->id,
                        'verified_at' => now(),
                        'ip_address' => $request->ip(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // 4. Create Audit Log
                $this->audit->log('payment.approved', 'payments', $payment->id, $systemUser, [
                    'reservation_code' => $reservation->reservation_code,
                    'payment_reference' => $payment->payment_reference,
                    'xpaylink_session_id' => $sessionId,
                ]);
            });

            // 5. Send Email and Push Notification (outside transaction)
            try {
                Mail::to($reservation->user->email)->queue(
                    new ReservationReceiptMail($reservation),
                );
            } catch (\Throwable $e) {
                report($e);
            }

            $this->notifications->notify(
                $reservation->user_id,
                'reservation',
                'Payment approved for '.$reservation->reservation_code,
                'Your booking is now confirmed. Tap to view the receipt.',
                $reservation,
            );

            return response()->json(['success' => true, 'message' => 'Payment processed successfully.']);
        }

        return response()->json(['status' => 'ignored', 'message' => 'Unhandled status event']);
    }
}
