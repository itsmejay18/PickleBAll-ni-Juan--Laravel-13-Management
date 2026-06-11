<?php

namespace App\Http\Controllers;

use App\Models\CancellationLog;
use App\Models\CancellationPolicy;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CancellationController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly InventoryService $inventory,
        private readonly NotificationService $notifications,
    ) {}

    public function store(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeCancel($user, $reservation);

        $validated = $request->validate([
            'reason_text' => ['required', 'string', 'max:1000'],
            'reason_category' => ['nullable', 'in:customer_request,no_show,payment_failed,staff_cancelled,system_auto,maintenance'],
        ]);

        if (in_array($reservation->status, ['cancelled', 'no_show', 'refunded', 'completed'], true)) {
            return back()->with('error', 'This reservation can no longer be cancelled.');
        }

        $startsAt = Carbon::parse($reservation->reservation_date->toDateString().' '.$reservation->start_time);
        $hoursUntilStart = max(0, now()->diffInHours($startsAt, false));

        // Pick the most generous matching policy whose threshold is satisfied.
        $policy = CancellationPolicy::query()
            ->where('is_active', true)
            ->whereIn('applies_to', [$reservation->reservation_type, 'both'])
            ->where('hours_before_reservation', '<=', $hoursUntilStart)
            ->orderByDesc('refund_percentage')
            ->first();

        $refundPercent = $policy?->refund_percentage ?? 0;
        $verifiedPaid = (float) Payment::query()
            ->where('reservation_id', $reservation->id)
            ->whereIn('status', ['verified'])
            ->sum('amount');

        $refundAmount = round($verifiedPaid * ($refundPercent / 100), 2);

        $reasonCategory = $validated['reason_category']
            ?? ($user->id === $reservation->user_id ? 'customer_request' : 'staff_cancelled');

        $newPaymentStatus = 'unpaid';
        if ($refundAmount > 0 && $verifiedPaid > 0) {
            $newPaymentStatus = $refundAmount >= $verifiedPaid ? 'refunded' : 'partially_paid';
        } elseif ($verifiedPaid > 0) {
            $newPaymentStatus = 'paid'; // paid but no refund per policy
        }

        DB::transaction(function () use (
            $reservation, $user, $validated, $policy, $refundAmount, $verifiedPaid, $newPaymentStatus, $reasonCategory
        ) {
            $reservation->update([
                'status' => $verifiedPaid > 0 && $refundAmount > 0 ? 'refunded' : 'cancelled',
                'payment_status' => $newPaymentStatus,
                'is_active' => false,
            ]);

            // Record refund as a payment row of type refund (negative amount kept positive on row, status refunded)
            // W3 fix: use the original payment method instead of hardcoding 'gcash'
            $originalPaymentMethod = Payment::query()
                ->where('reservation_id', $reservation->id)
                ->whereIn('status', ['verified'])
                ->orderByDesc('created_at')
                ->value('payment_method') ?? 'gcash';

            $refundPaymentId = null;
            if ($refundAmount > 0) {
                $refundPaymentId = Payment::query()->create([
                    'reservation_id' => $reservation->id,
                    'payment_reference' => 'REFUND-'.$reservation->reservation_code.'-'.now()->format('His'),
                    'amount' => $refundAmount,
                    'payment_method' => $originalPaymentMethod,
                    'payment_type' => 'partial',
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'refunded_by' => $user->id,
                    'refund_reason' => $validated['reason_text'],
                    'notes' => 'Refund issued per cancellation policy.',
                    'created_by' => $user->id,
                ])->id;

                Payment::query()
                    ->where('reservation_id', $reservation->id)
                    ->where('status', 'verified')
                    ->update(['status' => 'refunded', 'refunded_at' => now(), 'refunded_by' => $user->id]);
            }

            CancellationLog::query()->create([
                'reservation_id' => $reservation->id,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_policy_id' => $policy?->id,
                'refund_amount' => $refundAmount,
                'refund_payment_id' => $refundPaymentId,
                'reason_category' => $reasonCategory,
                'reason_text' => $validated['reason_text'],
                'customer_notified' => true,
                'notification_sent_at' => now(),
                'created_at' => now(),
            ]);

            $this->inventory->restoreReservedFor($reservation, $user, 'Equipment released after cancellation.');

            $this->audit->log('reservation.cancelled', 'reservations', $reservation->id, $user, [
                'reservation_code' => $reservation->reservation_code,
                'refund_amount' => $refundAmount,
                'policy_id' => $policy?->id,
                'reason' => $validated['reason_text'],
            ]);
        });

        $this->notifications->notify(
            $reservation->user_id,
            'reservation',
            'Reservation '.$reservation->reservation_code.' cancelled',
            $refundAmount > 0
                ? 'Your booking was cancelled. Refund of PHP '.number_format($refundAmount, 2).' will be processed.'
                : 'Your booking was cancelled. No refund per policy.',
            $reservation,
            ['refund_amount' => $refundAmount, 'reason' => $validated['reason_text']],
        );

        $isStaffOrAdmin = $user->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_LOCATION_MANAGER,
            User::ROLE_STAFF
        ]);

        if (!$isStaffOrAdmin) {
            $this->notifications->notifyStaffAndAdmins(
                'Reservation Cancelled',
                "Client cancelled booking {$reservation->reservation_code}. Reason: {$validated['reason_text']}.",
                $reservation,
                ['reason' => $validated['reason_text']]
            );
        }

        return back()->with('status', 'Reservation '.$reservation->reservation_code.' cancelled. '.($refundAmount > 0 ? 'Refund of PHP '.number_format($refundAmount, 2).' recorded.' : 'No refund per policy.'));
    }

    private function authorizeCancel(User $user, Reservation $reservation): void
    {
        if ($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            return;
        }

        if ($user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF])) {
            $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
            abort_if($locationId && (int) $reservation->location_id !== (int) $locationId, 403);

            return;
        }

        abort_if($reservation->user_id !== $user->id, 403);
    }
}
