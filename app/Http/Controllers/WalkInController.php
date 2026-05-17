<?php

namespace App\Http\Controllers;

use App\Mail\ReservationReceiptMail;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\StaffProfile;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class WalkInController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]), 403);

        $validated = $request->validate([
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'customer_first_name' => ['required', 'string', 'max:100'],
            'customer_last_name' => ['required', 'string', 'max:100'],
            'customer_mobile' => ['required', 'string', 'max:20'],
            'create_account' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'in:cash,gcash'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'gcash_reference_number' => ['nullable', 'string', 'max:100'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        $court = DB::table('courts as c')
            ->join('locations as l', 'l.id', '=', 'c.location_id')
            ->where('c.id', $validated['court_id'])
            ->where('c.is_active', true)
            ->whereNull('c.deleted_at')
            ->where('l.is_active', true)
            ->whereNull('l.deleted_at')
            ->first(['c.*', 'l.name as location_name']);

        abort_unless($court, 404);

        // Staff at a location may only create walk-ins for their own location
        if ($user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF])) {
            $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
            abort_if($locationId && (int) $court->location_id !== (int) $locationId, 403, 'Walk-ins can only be created for your assigned branch.');
        }

        $startTime = strlen($validated['start_time']) === 5 ? $validated['start_time'].':00' : $validated['start_time'];
        $endTime = strlen($validated['end_time']) === 5 ? $validated['end_time'].':00' : $validated['end_time'];
        $hours = (strtotime($validated['reservation_date'].' '.$endTime) - strtotime($validated['reservation_date'].' '.$startTime)) / 3600;

        if ($hours < 1 || $hours > 4) {
            return back()->withInput()->with('error', 'Walk-in bookings must be between 1 and 4 hours.');
        }

        $rate = (float) (DB::table('court_pricing_rules')
            ->where('court_id', $court->id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->value('base_price') ?? 600);
        $courtSubtotal = round($rate * $hours, 2);
        $grandTotal = $courtSubtotal;

        if ($validated['payment_method'] === 'cash' && (float) ($validated['cash_received'] ?? 0) < $grandTotal) {
            return back()->withInput()->with('error', 'Cash received must cover the total of PHP '.number_format($grandTotal, 2).'.');
        }

        $customer = $this->resolveOrCreateCustomer($validated, $request->boolean('create_account'));
        $code = $this->reservationCode();

        try {
            DB::transaction(function () use (
                $validated, $court, $customer, $code, $rate, $courtSubtotal, $grandTotal,
                $startTime, $endTime, $hours, $user
            ) {
                // Concurrency guard
                DB::table('reservations')
                    ->where('court_id', $court->id)
                    ->where('reservation_date', $validated['reservation_date'])
                    ->lockForUpdate()
                    ->get();

                $conflict = DB::table('reservations')
                    ->where('court_id', $court->id)
                    ->where('reservation_date', $validated['reservation_date'])
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'no_show', 'refunded'])
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->exists();

                if ($conflict) {
                    throw new \RuntimeException('That court is already booked for the selected window.');
                }

                $payload = [
                    'reservation_code' => $code,
                    'user_id' => $customer->id,
                    'court_id' => $court->id,
                    'location_id' => $court->location_id,
                    'reservation_date' => $validated['reservation_date'],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'court_price_per_hour' => $rate,
                    'court_subtotal' => $courtSubtotal,
                    'equipment_total' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'grand_total' => $grandTotal,
                    'reservation_type' => 'walk_in',
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'special_requests' => $validated['special_requests'] ?? null,
                    'is_active' => true,
                    'confirmed_at' => now(),
                    'confirmed_by' => $user->id,
                    'created_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (DB::getDriverName() === 'sqlite') {
                    $payload['total_hours'] = $hours;
                    $payload['expires_at'] = null;
                }

                $reservationId = DB::table('reservations')->insertGetId($payload);

                $payment = Payment::query()->create([
                    'reservation_id' => $reservationId,
                    'payment_reference' => 'WALK-'.$code,
                    'amount' => $grandTotal,
                    'payment_method' => $validated['payment_method'],
                    'payment_type' => 'full',
                    'gcash_number_sent_to' => $validated['payment_method'] === 'gcash' ? $this->ownerGcashNumber() : null,
                    'gcash_reference_number' => $validated['gcash_reference_number'] ?? null,
                    'cash_received_amount' => $validated['payment_method'] === 'cash' ? (float) $validated['cash_received'] : null,
                    'cash_change_amount' => $validated['payment_method'] === 'cash' ? round((float) $validated['cash_received'] - $grandTotal, 2) : null,
                    'status' => 'verified',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'notes' => 'Walk-in counter payment.',
                    'created_by' => $user->id,
                ]);

                $this->audit->log('reservation.walk_in.created', 'reservations', $reservationId, $user, [
                    'reservation_code' => $code,
                    'customer' => $customer->email,
                    'amount' => $grandTotal,
                    'payment_method' => $validated['payment_method'],
                ]);

                $reservation = Reservation::query()->find($reservationId);

                $this->notifications->notify(
                    $customer,
                    'reservation',
                    'Walk-in booking confirmed',
                    'Your walk-in booking '.$code.' is confirmed for '.$validated['reservation_date'].'.',
                    $reservation,
                    ['payment_method' => $validated['payment_method']],
                );

                try {
                    Mail::to($customer->email)->queue(
                        new ReservationReceiptMail($reservation),
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('modules.show', 'walk-ins')
            ->with('status', 'Walk-in '.$code.' confirmed and paid.');
    }

    private function resolveOrCreateCustomer(array $data, bool $createAccount): User
    {
        $existing = User::query()->where('mobile_number', $data['customer_mobile'])->first();

        if ($existing) {
            return $existing;
        }

        if (! $createAccount) {
            // Ephemeral guest user with a deterministic mobile-derived email
            $email = 'walkin+'.preg_replace('/\D+/', '', $data['customer_mobile']).'@walkins.local';

            return User::query()->withTrashed()->updateOrCreate(
                ['email' => $email],
                [
                    'mobile_number' => $data['customer_mobile'],
                    'password' => Hash::make(Str::random(20)),
                    'is_active' => true,
                    'email_verified_at' => null,
                    'mobile_verified_at' => null,
                    'deleted_at' => null,
                ],
            )->fresh();
        }

        $generatedEmail = preg_replace('/\D+/', '', $data['customer_mobile']).'@walkins.local';
        $user = User::query()->create([
            'email' => $generatedEmail,
            'mobile_number' => $data['customer_mobile'],
            'password' => Hash::make(Str::random(16)),
            'is_active' => true,
        ]);

        Role::findOrCreate(User::ROLE_END_USER, 'web');
        $user->assignRole(User::ROLE_END_USER);

        DB::table('end_user_profiles')->insert([
            'user_id' => $user->id,
            'first_name' => $data['customer_first_name'],
            'last_name' => $data['customer_last_name'],
            'preferred_language' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function reservationCode(): string
    {
        do {
            $code = 'PBJ-W-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (DB::table('reservations')->where('reservation_code', $code)->exists());

        return $code;
    }

    private function ownerGcashNumber(): string
    {
        return SystemSetting::value('owner_gcash_number', '09123456789');
    }
}
