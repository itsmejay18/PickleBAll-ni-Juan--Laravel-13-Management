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
            'payment_method' => ['nullable', 'in:cash,gcash'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'gcash_reference_number' => ['nullable', 'string', 'max:100'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['nullable', 'integer', 'min:0', 'max:20'],
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

        if (!$this->isWithinOperatingHours($startTime, $endTime)) {
            return back()->withInput()->with('error', 'That court is closed for the selected time.');
        }

        $courtSubtotal = 0.0;
        $startSecs = strtotime($validated['reservation_date'].' '.$startTime);
        for ($i = 0; $i < $hours; $i++) {
            $slotStart = date('H:i:00', $startSecs + ($i * 3600));
            $slotRate = $this->getSlotPrice($court->id, $validated['reservation_date'], $slotStart);
            $courtSubtotal += $slotRate;
        }

        $requestedEquipment = collect($request->input('equipment', []))
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn ($qty) => $qty > 0);

        $equipmentRows = [];
        $equipmentTotal = 0.0;

        foreach ($requestedEquipment as $equipmentTypeId => $quantity) {
            $equipment = DB::table('equipment_inventory as ei')
                ->join('equipment_types as et', 'et.id', '=', 'ei.equipment_type_id')
                ->where('ei.location_id', $court->location_id)
                ->where('ei.equipment_type_id', (int) $equipmentTypeId)
                ->where('et.is_available_for_rent', true)
                ->whereNull('et.deleted_at')
                ->first([
                    'ei.id as inventory_id',
                    'ei.available_quantity',
                    'ei.reserved_quantity',
                    'ei.total_quantity',
                    'ei.damaged_quantity',
                    'ei.lost_quantity',
                    'ei.under_maintenance_quantity',
                    'ei.equipment_type_id',
                    'et.name',
                    'et.rental_price_per_unit',
                    'et.deposit_amount',
                    'et.requires_deposit',
                    'et.max_rental_quantity_per_booking',
                ]);

            if (! $equipment) {
                return back()->withInput()->with('error', 'One of the selected rental items is not available at this location.');
            }

            if ($quantity > $equipment->max_rental_quantity_per_booking) {
                return back()->withInput()->with('error', $equipment->name.' allows up to '.$equipment->max_rental_quantity_per_booking.' per booking.');
            }

            $slotAvailable = $this->availableEquipmentQuantityForSlot(
                (int) $court->location_id,
                (int) $equipmentTypeId,
                $validated['reservation_date'],
                $startTime,
                $endTime
            );

            if ($quantity > $slotAvailable) {
                return back()->withInput()->with('error', $equipment->name.' only has '.$slotAvailable.' available for the selected slot.');
            }

            $subtotal = round($quantity * (float) $equipment->rental_price_per_unit, 2);
            $equipmentTotal += $subtotal;
            $equipmentRows[] = [
                'inventory_id' => $equipment->inventory_id,
                'equipment_type_id' => $equipment->equipment_type_id,
                'name' => $equipment->name,
                'quantity' => $quantity,
                'price_per_unit' => (float) $equipment->rental_price_per_unit,
                'subtotal' => $subtotal,
                'deposit_charged' => $equipment->requires_deposit ? round($quantity * (float) $equipment->deposit_amount, 2) : 0,
                'previous_available' => (int) $equipment->available_quantity,
                'previous_reserved' => (int) $equipment->reserved_quantity,
                'total_quantity' => (int) $equipment->total_quantity,
                'damaged_quantity' => (int) ($equipment->damaged_quantity ?? 0),
                'lost_quantity' => (int) ($equipment->lost_quantity ?? 0),
                'under_maintenance_quantity' => (int) ($equipment->under_maintenance_quantity ?? 0),
            ];
        }

        $grandTotal = round($courtSubtotal + $equipmentTotal, 2);
        $rate = $courtSubtotal / $hours;

        $paymentMethod = $validated['payment_method'] ?? null;
        if ($paymentMethod === 'cash' && (float) ($validated['cash_received'] ?? 0) < $grandTotal) {
            return back()->withInput()->with('error', 'Cash received must cover the total of PHP '.number_format($grandTotal, 2).'.');
        }

        $customer = $this->resolveOrCreateCustomer($validated, $request->boolean('create_account'));
        $code = $this->reservationCode();

        try {
            DB::transaction(function () use (
                $validated, $court, $customer, $code, $rate, $courtSubtotal, $equipmentTotal, $grandTotal,
                $startTime, $endTime, $hours, $user, $paymentMethod, $equipmentRows
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

                $isPaid = !empty($paymentMethod);
                $status = $isPaid ? 'confirmed' : 'pending_payment';
                $paymentStatus = $isPaid ? 'paid' : 'unpaid';

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
                    'equipment_total' => $equipmentTotal,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'grand_total' => $grandTotal,
                    'reservation_type' => 'walk_in',
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'special_requests' => $validated['special_requests'] ?? null,
                    'is_active' => true,
                    'confirmed_at' => $isPaid ? now() : null,
                    'confirmed_by' => $isPaid ? $user->id : null,
                    'created_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'expires_at' => null, // Walk-ins don't auto-expire
                ];

                if (DB::getDriverName() === 'sqlite') {
                    $payload['total_hours'] = $hours;
                }

                $reservationId = DB::table('reservations')->insertGetId($payload);

                foreach ($equipmentRows as $eq) {
                    $reservationEquipment = [
                        'reservation_id' => $reservationId,
                        'equipment_type_id' => $eq['equipment_type_id'],
                        'quantity' => $eq['quantity'],
                        'price_per_unit' => $eq['price_per_unit'],
                        'deposit_charged' => $eq['deposit_charged'],
                        'deposit_returned' => false,
                        'deposit_returned_at' => null,
                        'is_returned' => false,
                        'returned_at' => null,
                        'returned_to_staff_id' => null,
                        'damage_notes' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (DB::getDriverName() === 'sqlite') {
                        $reservationEquipment['subtotal'] = $eq['subtotal'];
                    }

                    DB::table('reservation_equipment')->insert($reservationEquipment);

                    $damaged = (int) $eq['damaged_quantity'];
                    $lost = (int) $eq['lost_quantity'];
                    $maintenance = (int) $eq['under_maintenance_quantity'];
                    $physicalStock = max(0, (int) $eq['total_quantity'] - $damaged - $lost - $maintenance);

                    $newAvailable = max(0, min($physicalStock, $eq['previous_available'] - $eq['quantity']));
                    $newReserved = max(0, min($physicalStock, $eq['previous_reserved'] + $eq['quantity']));

                    DB::table('equipment_inventory')
                        ->where('location_id', $court->location_id)
                        ->where('equipment_type_id', $eq['equipment_type_id'])
                        ->update([
                            'available_quantity' => $newAvailable,
                            'reserved_quantity' => $newReserved,
                            'updated_at' => now(),
                        ]);

                    DB::table('inventory_transactions')->insert([
                        'location_id' => $court->location_id,
                        'equipment_type_id' => $eq['equipment_type_id'],
                        'reservation_id' => $reservationId,
                        'transaction_type' => 'check_out',
                        'quantity' => -$eq['quantity'],
                        'previous_available' => $eq['previous_available'],
                        'new_available' => $newAvailable,
                        'notes' => 'Reserved during walk-in booking ' . $code . '.',
                        'performed_by' => $user->id,
                        'created_at' => now(),
                    ]);
                }

                if ($isPaid) {
                    $payment = Payment::query()->create([
                        'reservation_id' => $reservationId,
                        'payment_reference' => 'WALK-'.$code,
                        'amount' => $grandTotal,
                        'payment_method' => $paymentMethod,
                        'payment_type' => 'full',
                        'gcash_number_sent_to' => $paymentMethod === 'gcash' ? $this->ownerGcashNumber() : null,
                        'gcash_reference_number' => $validated['gcash_reference_number'] ?? null,
                        'cash_received_amount' => $paymentMethod === 'cash' ? (float) $validated['cash_received'] : null,
                        'cash_change_amount' => $paymentMethod === 'cash' ? round((float) $validated['cash_received'] - $grandTotal, 2) : null,
                        'status' => 'verified',
                        'verified_by' => $user->id,
                        'verified_at' => now(),
                        'notes' => 'Walk-in counter payment.',
                        'created_by' => $user->id,
                    ]);
                }

                $this->audit->log('reservation.walk_in.created', 'reservations', $reservationId, $user, [
                    'reservation_code' => $code,
                    'customer' => $customer->email,
                    'amount' => $grandTotal,
                    'payment_method' => $paymentMethod ?? 'unpaid',
                ]);

                $reservation = Reservation::query()->find($reservationId);

                $this->notifications->notify(
                    $customer,
                    'reservation',
                    'Walk-in booking ' . ($isPaid ? 'confirmed' : 'created'),
                    'Your walk-in booking '.$code.' is ' . ($isPaid ? 'confirmed' : 'created (pending payment)') . ' for '.$validated['reservation_date'].'.',
                    $reservation,
                    ['payment_method' => $paymentMethod],
                );

                if ($isPaid) {
                    try {
                        Mail::to($customer->email)->queue(
                            new ReservationReceiptMail($reservation),
                        );
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('modules.show', 'walk-ins')
            ->with('status', 'Walk-in '.$code.' recorded.');
    }

    public function markPaid(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]), 403);

        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,gcash'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'gcash_reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $grandTotal = (float) $reservation->grand_total;

        if ($validated['payment_method'] === 'cash' && (float) ($validated['cash_received'] ?? 0) < $grandTotal) {
            return back()->with('error', 'Cash received must cover the total of PHP '.number_format($grandTotal, 2).'.');
        }

        DB::transaction(function () use ($reservation, $validated, $grandTotal, $user) {
            $paymentMethod = $validated['payment_method'];

            DB::table('reservations')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'confirmed_at' => now(),
                    'confirmed_by' => $user->id,
                    'updated_at' => now(),
                ]);

            Payment::query()->create([
                'reservation_id' => $reservation->id,
                'payment_reference' => 'WALK-'.$reservation->reservation_code,
                'amount' => $grandTotal,
                'payment_method' => $paymentMethod,
                'payment_type' => 'full',
                'gcash_number_sent_to' => $paymentMethod === 'gcash' ? $this->ownerGcashNumber() : null,
                'gcash_reference_number' => $validated['gcash_reference_number'] ?? null,
                'cash_received_amount' => $paymentMethod === 'cash' ? (float) $validated['cash_received'] : null,
                'cash_change_amount' => $paymentMethod === 'cash' ? round((float) $validated['cash_received'] - $grandTotal, 2) : null,
                'status' => 'verified',
                'verified_by' => $user->id,
                'verified_at' => now(),
                'notes' => 'Walk-in counter payment.',
                'created_by' => $user->id,
            ]);

            $customer = User::query()->find($reservation->user_id);

            $this->audit->log('reservation.walk_in.paid', 'reservations', $reservation->id, $user, [
                'reservation_code' => $reservation->reservation_code,
                'customer' => $customer->email ?? '',
                'amount' => $grandTotal,
                'payment_method' => $paymentMethod,
            ]);

            if ($customer) {
                $this->notifications->notify(
                    $customer,
                    'reservation',
                    'Walk-in booking paid',
                    'Your walk-in booking '.$reservation->reservation_code.' has been marked as Paid.',
                    $reservation,
                    ['payment_method' => $paymentMethod],
                );

                try {
                    Mail::to($customer->email)->queue(
                        new ReservationReceiptMail($reservation),
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        return redirect()
            ->route('modules.show', 'walk-ins')
            ->with('status', 'Reservation marked as Paid.');
    }

    private function resolveOrCreateCustomer(array $data, bool $createAccount): User
    {
        // C4 fix: only reuse an existing account if it's a guest/walk-in account
        // (i.e. email ends with @walkins.local). Never silently attach a booking
        // to a registered customer's account just because the mobile matches.
        $existing = User::query()
            ->where('mobile_number', $data['customer_mobile'])
            ->where(function ($q) {
                $q->where('email', 'like', '%@walkins.local');
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        if (! $createAccount) {
            // W11 fix: use a unique suffix (mobile digits + random) to avoid collisions
            $digits = preg_replace('/\D+/', '', $data['customer_mobile']);
            $email = 'walkin+'.$digits.'@walkins.local';

            // If that email already exists for a different mobile, append a short hash
            if (User::query()->where('email', $email)->where('mobile_number', '!=', $data['customer_mobile'])->exists()) {
                $email = 'walkin+'.$digits.'.'.substr(md5($data['customer_mobile']), 0, 4).'@walkins.local';
            }

            $walkInUser = User::query()->withTrashed()->firstOrNew(['email' => $email]);
            $walkInUser->forceFill([
                'mobile_number' => $data['customer_mobile'],
                'password' => Hash::make(Str::random(20)),
                'is_active' => true,
                'email_verified_at' => null,
                'mobile_verified_at' => null,
                'deleted_at' => null,
            ])->save();
            return $walkInUser->fresh();
        }

        // W11 fix: same collision-safe email for account creation path
        $digits = preg_replace('/\D+/', '', $data['customer_mobile']);
        $generatedEmail = $digits.'@walkins.local';

        if (User::query()->where('email', $generatedEmail)->where('mobile_number', '!=', $data['customer_mobile'])->exists()) {
            $generatedEmail = $digits.'.'.substr(md5($data['customer_mobile']), 0, 4).'@walkins.local';
        }

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

    private function isWithinOperatingHours(string $startTime, string $endTime): bool
    {
        $openTime = SystemSetting::value('public_playing_open_time', '07:00');
        $closeTime = SystemSetting::value('public_playing_close_time', '00:00');

        $openMins = $this->timeToMinutes($openTime);
        $closeMins = $this->timeToMinutes($closeTime);

        if ($closeMins <= $openMins) {
            $closeMins += 1440;
        }

        $startMins = $this->timeToMinutes($startTime);
        $endMins = $this->timeToMinutes($endTime);

        if ($startMins < $openMins) {
            $startMins += 1440;
        }
        if ($endMins <= $openMins) {
            $endMins += 1440;
        }

        return $startMins >= $openMins && $endMins <= $closeMins;
    }

    private function getSlotPrice(int $courtId, string $date, string $slotStart): float
    {
        $day = (int) date('w', strtotime($date));
        
        $rules = DB::table('court_pricing_rules')
            ->where('court_id', $courtId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->where(function ($query) use ($day) {
                $query->whereNull('day_of_week')->orWhere('day_of_week', $day);
            })
            ->get();

        $slotStartMins = $this->timeToMinutes($slotStart);

        $matchedRule = null;
        foreach ($rules as $rule) {
            if (!$rule->start_time || !$rule->end_time) {
                continue;
            }

            $ruleStartMins = $this->timeToMinutes($rule->start_time);
            $ruleEndMins = $this->timeToMinutes($rule->end_time);

            if ($ruleEndMins <= $ruleStartMins) {
                $ruleEndMins += 1440;
            }

            $currentSlotMins = $slotStartMins;
            if ($currentSlotMins < $ruleStartMins && $currentSlotMins + 1440 <= $ruleEndMins) {
                $currentSlotMins += 1440;
            }

            if ($currentSlotMins >= $ruleStartMins && $currentSlotMins <= $ruleEndMins) {
                if ($currentSlotMins == $ruleEndMins) {
                    $matchedRule = $rule;
                    break;
                }
                
                if ($currentSlotMins >= $ruleStartMins && $currentSlotMins < $ruleEndMins) {
                    $matchedRule = $rule;
                }
            }
        }

        if (!$matchedRule) {
            $matchedRule = $rules->whereNull('start_time')->first() 
                ?? $rules->sortBy('priority')->first();
        }

        if (!$matchedRule) {
            return 600.0;
        }

        return round((float) $matchedRule->base_price * (1 + ((float) ($matchedRule->peak_surcharge_percentage ?? 0) / 100)), 2);
    }

    private function timeToMinutes(string $time): int
    {
        $time = trim($time);
        if (preg_match('/(AM|PM)/i', $time)) {
            $timestamp = strtotime('2000-01-01 ' . $time);
            if ($timestamp !== false) {
                return (int) date('H', $timestamp) * 60 + (int) date('i', $timestamp);
            }
        }
        $parts = explode(':', $time);
        return ((int) $parts[0]) * 60 + ((int) ($parts[1] ?? 0));
    }

    private function availableEquipmentQuantityForSlot(int $locationId, int $equipmentTypeId, string $date, string $startTime, string $endTime): int
    {
        $inventory = DB::table('equipment_inventory')
            ->where('location_id', $locationId)
            ->where('equipment_type_id', $equipmentTypeId)
            ->first();

        if (! $inventory) {
            return 0;
        }

        $damaged = (int) ($inventory->damaged_quantity ?? 0);
        $lost = (int) ($inventory->lost_quantity ?? 0);
        $maintenance = (int) ($inventory->under_maintenance_quantity ?? 0);
        $physicalStock = (int) $inventory->total_quantity - $damaged - $lost - $maintenance;

        $alreadyRented = DB::table('reservation_equipment as re')
            ->join('reservations as r', 'r.id', '=', 're.reservation_id')
            ->where('r.location_id', $locationId)
            ->where('re.equipment_type_id', $equipmentTypeId)
            ->where('r.reservation_date', $date)
            ->whereNull('r.deleted_at')
            ->whereNotIn('r.status', ['cancelled', 'no_show', 'refunded'])
            ->where('r.start_time', '<', $endTime)
            ->where('r.end_time', '>', $startTime)
            ->sum('re.quantity');

        return max(0, $physicalStock - (int) $alreadyRented);
    }
}
