<?php

namespace App\Http\Controllers;

use App\Mail\ReservationReceiptMail;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModulePageController extends Controller
{
    public function __invoke(Request $request, string $module): View
    {
        $page = $this->pages()[$module] ?? throw new NotFoundHttpException;
        $page = $this->withLiveData($module, $page, $request->user());

        return view('modules.show', [
            'module' => $module,
            'page' => $page,
        ]);
    }

    public function storeBooking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        self::releaseExpiredReservations();

        $user = $request->user();

        // Spam prevention: Limit active pending unpaid bookings (skip for staff/admins)
        if ($user && ! $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF])) {
            $pendingReservationsCount = Reservation::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending_payment', 'payment_verification'])
                ->where('payment_status', 'unpaid')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->count();

            $timeoutMinutes = (int) SystemSetting::value('booking_timeout_minutes', '3');
            $opThreshold = now()->subMinutes($timeoutMinutes);
            $pendingOpenPlayCount = DB::table('open_play_registrations')
                ->where('user_id', $user->id)
                ->where('status', 'pending_payment')
                ->where('registered_at', '>', $opThreshold)
                ->count();

            $totalPending = $pendingReservationsCount + $pendingOpenPlayCount;
            $maxLimit = (int) SystemSetting::value('max_pending_bookings_limit', '1');

            if ($totalPending >= $maxLimit) {
                return back()->withInput()->with('error', "You have reached the maximum limit of {$maxLimit} pending unpaid bookings. Please complete payment for your existing bookings first.");
            }
        }

        $date = $validated['reservation_date'];
        $startTime = $this->normalizeTime($validated['start_time']);
        $endTime = $this->normalizeTime($validated['end_time']);
        $hours = $this->hoursBetween($date, $startTime, $endTime);

        if ($hours <= 0) {
            return back()->withInput()->with('error', 'End time must be later than start time.');
        }

        if ($hours < 1 || $hours > 4) {
            return back()->withInput()->with('error', 'Bookings must be between 1 and 4 hours.');
        }

        $court = DB::table('courts as c')
            ->join('locations as l', 'l.id', '=', 'c.location_id')
            ->where('c.id', $validated['court_id'])
            ->where('c.location_id', $validated['location_id'])
            ->where('c.is_active', true)
            ->whereNull('c.deleted_at')
            ->where('l.is_active', true)
            ->whereNull('l.deleted_at')
            ->first(['c.*', 'l.name as location_name']);

        if (! $court) {
            return back()->withInput()->with('error', 'Choose an active court from the selected location.');
        }

        if (! $this->courtIsOpen($court->id, $date, $startTime, $endTime)) {
            return back()->withInput()->with('error', 'That court is closed or on break for the selected time.');
        }

        if ($this->courtIsBooked($court->id, $date, $startTime, $endTime)) {
            return back()->withInput()->with('error', 'That court already has a booking in that time window.');
        }

        if ($this->courtIsUnderMaintenance($court->id, $date, $startTime, $endTime)) {
            return back()->withInput()->with('error', 'That court is blocked for maintenance in that time window.');
        }

        if ($this->courtHasOpenPlay($court->location_id, $date, $startTime, $endTime)) {
            return back()->withInput()->with('error', 'That court is reserved for Open Play in that time window.');
        }

        $courtSubtotal = 0.0;
        $startSecs = strtotime($date.' '.$startTime);
        for ($i = 0; $i < $hours; $i++) {
            $slotStart = date('H:i:00', $startSecs + ($i * 3600));
            $slotRate = $this->getSlotPrice($court->id, $date, $slotStart);
            $courtSubtotal += $slotRate;
        }
        $rate = $courtSubtotal / $hours;
        $requestedEquipment = collect($validated['equipment'] ?? [])
            ->map(fn ($quantity) => (int) $quantity)
            ->filter(fn ($quantity) => $quantity > 0);

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
                $date,
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

        $reservationCode = $this->reservationCode();
        $grandTotal = round($courtSubtotal + $equipmentTotal, 2);

        try {
            $reservationId = DB::transaction(function () use (
                $user, $court, $date, $startTime, $endTime, $hours, $rate,
                $courtSubtotal, $equipmentTotal, $grandTotal, $reservationCode,
                $validated, $equipmentRows
            ) {
                // Concurrency guard: lock all live reservations for this court+date so a
                // simultaneous request blocks until we have committed our insert. Then
                // re-check for an overlap inside the transaction (E3 - prevent double booking).
                DB::table('reservations')
                    ->where('court_id', $court->id)
                    ->where('reservation_date', $date)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'no_show', 'refunded'])
                    ->lockForUpdate()
                    ->get(['id']);

                $conflict = DB::table('reservations')
                    ->where('court_id', $court->id)
                    ->where('reservation_date', $date)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'no_show', 'refunded'])
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->exists();

                if ($conflict) {
                    throw new \RuntimeException('That court was just booked for the selected window. Pick another slot.');
                }

                $payload = [
                    'reservation_code' => $reservationCode,
                    'user_id' => $user->id,
                    'court_id' => $court->id,
                    'location_id' => $court->location_id,
                    'reservation_date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'court_price_per_hour' => $rate,
                    'court_subtotal' => $courtSubtotal,
                    'equipment_total' => $equipmentTotal,
                    'discount_amount' => 0,
                    'discount_type' => null,
                    'discount_reason' => null,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'grand_total' => $grandTotal,
                    'reservation_type' => 'online',
                    'status' => 'pending_payment',
                    'payment_status' => 'unpaid',
                    'special_requests' => $validated['special_requests'] ?? null,
                    'is_active' => true,
                    'confirmed_at' => null,
                    'confirmed_by' => null,
                    'created_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                    'expires_at' => now()->addMinutes((int) SystemSetting::value('booking_timeout_minutes', '3')),
                    'terms_accepted_at' => now(),
                ];

                if (DB::getDriverName() === 'sqlite') {
                    $payload['total_hours'] = $hours;
                }

                $reservationId = DB::table('reservations')->insertGetId($payload);

                foreach ($equipmentRows as $equipment) {
                    $reservationEquipment = [
                        'reservation_id' => $reservationId,
                        'equipment_type_id' => $equipment['equipment_type_id'],
                        'quantity' => $equipment['quantity'],
                        'price_per_unit' => $equipment['price_per_unit'],
                        'deposit_charged' => $equipment['deposit_charged'],
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
                        $reservationEquipment['subtotal'] = $equipment['subtotal'];
                    }

                    DB::table('reservation_equipment')->insert($reservationEquipment);

                    $damaged = (int) $equipment['damaged_quantity'];
                    $lost = (int) $equipment['lost_quantity'];
                    $maintenance = (int) $equipment['under_maintenance_quantity'];
                    $physicalStock = max(0, (int) $equipment['total_quantity'] - $damaged - $lost - $maintenance);

                    $newAvailable = max(0, min($physicalStock, $equipment['previous_available'] - $equipment['quantity']));
                    $newReserved = max(0, min($physicalStock, $equipment['previous_reserved'] + $equipment['quantity']));

                    DB::table('equipment_inventory')
                        ->where('id', $equipment['inventory_id'])
                        ->lockForUpdate()
                        ->update([
                            'available_quantity' => $newAvailable,
                            'reserved_quantity' => $newReserved,
                            'updated_at' => now(),
                        ]);

                    DB::table('inventory_transactions')->insert([
                        'location_id' => $court->location_id,
                        'equipment_type_id' => $equipment['equipment_type_id'],
                        'reservation_id' => $reservationId,
                        'transaction_type' => 'check_out',
                        'quantity' => -$equipment['quantity'],
                        'previous_available' => $equipment['previous_available'],
                        'new_available' => $newAvailable,
                        'notes' => 'Reserved during online booking '.$reservationCode.'.',
                        'performed_by' => $user->id,
                        'created_at' => now(),
                    ]);
                }

                DB::table('end_user_profiles')
                    ->where('user_id', $user->id)
                    ->update([
                        'total_bookings' => DB::raw('total_bookings + 1'),
                        'last_active_at' => now(),
                        'updated_at' => now(),
                    ]);

                $this->audit('reservation.created', 'reservations', $reservationId, $user, [
                    'reservation_code' => $reservationCode,
                    'court' => $court->court_number,
                    'grand_total' => $grandTotal,
                    'equipment_count' => count($equipmentRows),
                ]);

                return $reservationId;
            }, 3);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('bookings.pay', $reservationCode)
            ->with('status', 'Booking '.$reservationCode.' created! Complete your payment below.');
    }

    public function getPublicAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $locationId = (int) $validated['location_id'];
        $date = $validated['date'];

        self::releaseExpiredReservations();

        $courts = DB::table('courts')
            ->where('location_id', $locationId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('court_number')
            ->get();

        $openTime = SystemSetting::value('public_playing_open_time', '07:00');
        $closeTime = SystemSetting::value('public_playing_close_time', '00:00');

        $baseDate = '2000-01-01';
        $startTimestamp = strtotime($baseDate . ' ' . $openTime);
        $endTimestamp = strtotime($baseDate . ' ' . $closeTime);

        if ($endTimestamp <= $startTimestamp) {
            $endTimestamp = strtotime($baseDate . ' ' . $closeTime . ' +1 day');
        }

        $hours = [];
        $current = $startTimestamp;
        while ($current < $endTimestamp) {
            $slotStart = date('H:i', $current);
            $next = $current + 3600;
            if ($next > $endTimestamp) {
                break;
            }
            $slotEnd = date('H:i', $next);
            
            $startLabel = date('g:i A', $current);
            if (date('i', $current) === '00') {
                $startLabel = date('g A', $current);
            }
            $endLabel = date('g:i A', $next);
            if (date('i', $next) === '00') {
                $endLabel = date('g A', $next);
            }

            $hours[] = [
                'start' => $slotStart,
                'end' => $slotEnd,
                'label' => $startLabel . ' - ' . $endLabel,
            ];
            $current = $next;
        }

        $openPlayEvent = DB::table('open_play_events')
            ->where('location_id', $locationId)
            ->whereDate('event_date', $date)
            ->where('status', '!=', 'cancelled')
            ->first();

        $results = [];
        foreach ($courts as $court) {
            $courtAvailability = [];
            foreach ($hours as $slot) {
                $isOpen = $this->courtIsOpen($court->id, $date, $slot['start'].':00', $slot['end'].':00');
                $end = $slot['end'].':00' === '00:00:00' ? '24:00:00' : $slot['end'].':00';
                $booking = DB::table('reservations')
                    ->where('court_id', $court->id)
                    ->where('reservation_date', $date)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'no_show', 'refunded'])
                    ->where('start_time', '<', $end)
                    ->whereRaw("(CASE WHEN end_time = '00:00:00' THEN '24:00:00' ELSE end_time END) > ?", [$slot['start'].':00'])
                    ->first();

                $isMaintenance = $this->courtIsUnderMaintenance($court->id, $date, $slot['start'].':00', $slot['end'].':00');

                $isOpenPlay = false;
                if ($openPlayEvent) {
                    $slotStartSec = strtotime('2000-01-01 ' . $slot['start']);
                    $slotEndSec = strtotime('2000-01-01 ' . $slot['end']);
                    $opStartSec = strtotime('2000-01-01 ' . $openPlayEvent->start_time);
                    $opEndSec = strtotime('2000-01-01 ' . $openPlayEvent->end_time);
                    $isOpenPlay = ($slotStartSec < $opEndSec && $slotEndSec > $opStartSec);
                }

                $reason = 'Open';
                $expiresIn = null;
                $expiresAt = null;

                if ($isOpenPlay) {
                    $reason = 'Reserved for Open Play';
                } elseif (!$isOpen) {
                    $reason = 'Closed';
                } elseif ($isMaintenance) {
                    $reason = 'Maintenance';
                } elseif ($booking) {
                    if (in_array($booking->status, ['pending_payment', 'payment_verification'], true)) {
                        $reason = 'Ongoing Payment';
                        $expiresIn = $booking->expires_at ? max(0, strtotime($booking->expires_at) - time()) : null;
                        $expiresAt = $booking->expires_at ? (strtotime($booking->expires_at) * 1000) : null;
                    } else {
                        $reason = 'Booked';
                    }
                }

                $available = $isOpen && !$booking && !$isMaintenance && !$isOpenPlay;

                $slotData = [
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'label' => $slot['label'],
                    'available' => $available,
                    'reason' => $reason,
                ];

                if ($expiresIn !== null) {
                    $slotData['expires_in'] = $expiresIn;
                }
                if ($expiresAt !== null) {
                    $slotData['expires_at'] = $expiresAt;
                }

                $courtAvailability[] = $slotData;
            }

            $results[] = [
                'court_id' => $court->id,
                'court_name' => $court->court_name,
                'court_number' => $court->court_number,
                'slots' => $courtAvailability,
            ];
        }

        return response()->json([
            'date' => $date,
            'location_id' => $locationId,
            'courts' => $results,
        ]);
    }

    public function calculatePrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'reservation_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        $courtId = (int) $validated['court_id'];
        $date = $validated['reservation_date'];
        $startTime = $this->normalizeTime($validated['start_time']);
        
        $duration = (int) $validated['duration'];
        $startSecs = strtotime($date.' '.$startTime);
        $endSecs = $startSecs + ($duration * 3600);
        $endTime = date('H:i:00', $endSecs);

        $breakdown = $this->getPriceBreakdown($courtId, $date, $startTime, $endTime);

        return response()->json($breakdown);
    }

    public function showPaymentPage(string $reservationCode): View|RedirectResponse
    {
        self::releaseExpiredReservations();

        $user = auth()->user();

        $reservation = DB::table('reservations as r')
            ->join('courts as c', 'c.id', '=', 'r.court_id')
            ->join('locations as l', 'l.id', '=', 'r.location_id')
            ->where('r.reservation_code', $reservationCode)
            ->where('r.user_id', $user->id)
            ->whereNull('r.deleted_at')
            ->first([
                'r.id',
                'r.reservation_code',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'r.grand_total',
                'r.status',
                'r.payment_status',
                'r.expires_at',
                'r.court_id',
                'c.court_number',
                'c.court_name',
                'l.name as location_name',
            ]);

        if (! $reservation) {
            return redirect()->route('modules.show', 'book-court')
                ->with('error', 'Reservation not found.');
        }

        // If already paid or pending verification, go to receipts
        if (in_array($reservation->payment_status, ['paid', 'pending_verification'], true)) {
            return redirect()->route('modules.show', 'receipts')
                ->with('status', 'Your payment is already submitted or confirmed.');
        }

        $priceBreakdown = $this->getPriceBreakdown((int) $reservation->court_id, $reservation->reservation_date, $reservation->start_time, $reservation->end_time);

        $ownerGcashNumber = $this->ownerGcashNumber();
        $ownerGcashQr = DB::table('system_settings')->where('setting_key', 'owner_gcash_qr_path')->value('setting_value') ?: null;

        $xpaylinkEnabled = filter_var(SystemSetting::value('xpaylink_enabled', 'false'), FILTER_VALIDATE_BOOLEAN);
        $xpaylinkPublicKey = SystemSetting::value('xpaylink_public_key');
        $xpaylinkEndpoint = SystemSetting::value('xpaylink_endpoint', 'https://synthwave.space/api/create-session.php');

        return view('booking.pay', compact(
            'reservation',
            'ownerGcashNumber',
            'ownerGcashQr',
            'xpaylinkEnabled',
            'xpaylinkPublicKey',
            'xpaylinkEndpoint',
            'priceBreakdown'
        ));
    }

    public function storeCourt(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canManageOperations($user), 403);

        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'court_number' => ['required', 'string', 'max:50'],
            'court_name' => ['nullable', 'string', 'max:200'],
            'court_type' => ['required', 'in:indoor,outdoor,covered'],
            'surface_type' => ['required', 'in:concrete,asphalt,acrylic,grass,clay'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999'],
        ]);

        $location = DB::table('locations')
            ->where('id', $validated['location_id'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $location) {
            return back()->withInput()->with('error', 'Choose an active location for the court.');
        }

        $exists = DB::table('courts')
            ->where('location_id', $location->id)
            ->where('court_number', $validated['court_number'])
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'That court number already exists for this location.');
        }

        DB::transaction(function () use ($validated, $location, $user) {
            $courtId = DB::table('courts')->insertGetId([
                'location_id' => $location->id,
                'court_number' => $validated['court_number'],
                'court_name' => $validated['court_name'] ?: 'Court '.$validated['court_number'],
                'court_type' => $validated['court_type'],
                'surface_type' => $validated['surface_type'],
                'width' => 6.10,
                'length' => 13.41,
                'has_lighting' => true,
                'has_net' => true,
                'has_seating' => true,
                'has_shade' => $validated['court_type'] !== 'outdoor',
                'is_airconditioned' => $validated['court_type'] === 'indoor',
                'is_active' => true,
                'display_order' => (int) DB::table('courts')->where('location_id', $location->id)->max('display_order') + 1,
                'description' => 'Regulation-size pickleball court beside the main venue courts.',
                'special_instructions' => 'Non-marking court shoes required.',
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            foreach (range(0, 6) as $day) {
                DB::table('court_schedules')->insert([
                    'court_id' => $courtId,
                    'day_of_week' => $day,
                    'open_time' => in_array($day, [0, 6], true) ? '07:00:00' : '06:00:00',
                    'close_time' => in_array($day, [5, 6], true) ? '23:00:00' : '22:00:00',
                    'break_start_time' => '12:00:00',
                    'break_end_time' => '12:30:00',
                    'is_available' => true,
                    'effective_from' => now()->toDateString(),
                    'effective_to' => null,
                    'created_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('court_pricing_rules')->insert([
                'court_id' => $courtId,
                'rule_name' => 'Standard Rate',
                'day_of_week' => null,
                'start_time' => '06:00:00',
                'end_time' => '23:00:00',
                'is_holiday' => false,
                'is_peak_season' => false,
                'base_price' => $validated['base_price'],
                'peak_surcharge_percentage' => 0,
                'minimum_hours' => 1,
                'maximum_hours' => 4,
                'priority' => 1,
                'is_active' => true,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit('court.created', 'courts', $courtId, $user, [
                'court_number' => $validated['court_number'],
                'location' => $location->name,
                'base_price' => $validated['base_price'],
            ]);
        });

        return redirect()
            ->route('modules.show', 'courts')
            ->with('status', 'Court '.$validated['court_number'].' added and made bookable.');
    }

    public function storeEquipment(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canManageOperations($user), 403);

        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'rental_price_per_unit' => ['required', 'numeric', 'min:0', 'max:99999'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'reorder_point' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'max_rental_quantity_per_booking' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $location = DB::table('locations')
            ->where('id', $validated['location_id'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $location) {
            return back()->withInput()->with('error', 'Choose an active location for the equipment.');
        }

        $slug = Str::slug($validated['name']);

        DB::transaction(function () use ($validated, $location, $user, $slug) {
            $equipmentType = DB::table('equipment_types')->where('slug', $slug)->first();

            if ($equipmentType) {
                DB::table('equipment_types')
                    ->where('id', $equipmentType->id)
                    ->update([
                        'name' => $validated['name'],
                        'description' => $validated['description'] ?? $equipmentType->description,
                        'rental_price_per_unit' => $validated['rental_price_per_unit'],
                        'deposit_amount' => $validated['deposit_amount'] ?? 0,
                        'requires_deposit' => ($validated['deposit_amount'] ?? 0) > 0,
                        'is_available_for_rent' => true,
                        'max_rental_quantity_per_booking' => $validated['max_rental_quantity_per_booking'],
                        'updated_at' => now(),
                        'deleted_at' => null,
                    ]);

                $equipmentTypeId = $equipmentType->id;
            } else {
                $equipmentTypeId = DB::table('equipment_types')->insertGetId([
                    'category_id' => null,
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'description' => $validated['description'] ?? null,
                    'rental_price_per_unit' => $validated['rental_price_per_unit'],
                    'deposit_amount' => $validated['deposit_amount'] ?? 0,
                    'late_fee_per_hour' => 0,
                    'damage_replacement_cost' => null,
                    'is_available_for_rent' => true,
                    'requires_deposit' => ($validated['deposit_amount'] ?? 0) > 0,
                    'max_rental_quantity_per_booking' => $validated['max_rental_quantity_per_booking'],
                    'display_order' => (int) DB::table('equipment_types')->max('display_order') + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);
            }

            $inventory = DB::table('equipment_inventory')
                ->where('location_id', $location->id)
                ->where('equipment_type_id', $equipmentTypeId)
                ->first();

            if ($inventory) {
                DB::table('equipment_inventory')
                    ->where('id', $inventory->id)
                    ->update([
                        'total_quantity' => $inventory->total_quantity + $validated['quantity'],
                        'available_quantity' => $inventory->available_quantity + $validated['quantity'],
                        'reorder_point' => $validated['reorder_point'] ?? 5,
                        'last_inventory_count_at' => now(),
                        'last_inventory_count_by' => $user->id,
                        'updated_at' => now(),
                    ]);

                $previousAvailable = (int) $inventory->available_quantity;
                $newAvailable = $previousAvailable + $validated['quantity'];
            } else {
                DB::table('equipment_inventory')->insert([
                    'location_id' => $location->id,
                    'equipment_type_id' => $equipmentTypeId,
                    'total_quantity' => $validated['quantity'],
                    'available_quantity' => $validated['quantity'],
                    'reserved_quantity' => 0,
                    'damaged_quantity' => 0,
                    'lost_quantity' => 0,
                    'under_maintenance_quantity' => 0,
                    'last_inventory_count_at' => now(),
                    'last_inventory_count_by' => $user->id,
                    'reorder_point' => $validated['reorder_point'] ?? 5,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $previousAvailable = 0;
                $newAvailable = $validated['quantity'];
            }

            DB::table('inventory_transactions')->insert([
                'location_id' => $location->id,
                'equipment_type_id' => $equipmentTypeId,
                'reservation_id' => null,
                'transaction_type' => 'restock',
                'quantity' => $validated['quantity'],
                'previous_available' => $previousAvailable,
                'new_available' => $newAvailable,
                'notes' => 'Added from equipment management.',
                'performed_by' => $user->id,
                'created_at' => now(),
            ]);

            $this->audit('equipment.restocked', 'equipment_inventory', $equipmentTypeId, $user, [
                'location' => $location->name,
                'item' => $validated['name'],
                'quantity' => $validated['quantity'],
            ]);
        });

        return redirect()
            ->route('modules.show', 'equipment')
            ->with('status', $validated['name'].' stock added and available for rentals.');
    }

    public function uploadPaymentProof(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'gcash_reference_number' => ['required', 'string', 'max:100'],
            'gcash_sender_number' => ['nullable', 'string', 'max:20'],
            'gcash_screenshot' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $reservation = DB::table('reservations')
            ->where('id', $validated['reservation_id'])
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        abort_unless($reservation, 403);

        if (! in_array($reservation->payment_status, ['unpaid', 'pending_verification'], true)) {
            return back()->with('error', 'This reservation is not waiting for GCash proof.');
        }

        $referenceAlreadyUsed = DB::table('payments')
            ->where('gcash_reference_number', $validated['gcash_reference_number'])
            ->where('reservation_id', '<>', $reservation->id)
            ->exists();

        if ($referenceAlreadyUsed) {
            return back()->with('error', 'That GCash reference number is already attached to another payment.');
        }

        $path = $request->file('gcash_screenshot')->store('payment-proofs', 'local');
        $paymentReference = 'GCASH-'.$reservation->reservation_code;

        $paymentId = null;

        DB::transaction(function () use ($validated, $reservation, $user, $path, $paymentReference, &$paymentId) {
            DB::table('payments')->updateOrInsert(
                ['reservation_id' => $reservation->id, 'payment_reference' => $paymentReference],
                [
                    'amount' => $reservation->grand_total,
                    'payment_method' => 'gcash',
                    'payment_type' => 'full',
                    'gcash_number_sent_to' => $this->ownerGcashNumber(),
                    'gcash_sender_number' => $validated['gcash_sender_number'] ?? null,
                    'gcash_reference_number' => $validated['gcash_reference_number'],
                    'gcash_screenshot_path' => $path,
                    'cash_received_amount' => null,
                    'cash_change_amount' => null,
                    'status' => 'pending',
                    'verified_by' => null,
                    'verified_at' => null,
                    'rejection_reason' => null,
                    'refunded_at' => null,
                    'refunded_by' => null,
                    'refund_reason' => null,
                    'notes' => 'Customer uploaded GCash screenshot for admin verification.',
                    'created_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $paymentId = DB::table('payments')
                ->where('reservation_id', $reservation->id)
                ->where('payment_reference', $paymentReference)
                ->value('id');

            DB::table('gcash_transactions_log')->updateOrInsert(
                ['reference_number' => $validated['gcash_reference_number']],
                [
                    'payment_id' => $paymentId,
                    'user_id' => $user->id,
                    'owner_gcash_number' => $this->ownerGcashNumber(),
                    'user_sent_from_number' => $validated['gcash_sender_number'] ?? null,
                    'screenshot_path' => $path,
                    'amount' => $reservation->grand_total,
                    'status' => 'pending_verification',
                    'verification_notes' => 'Waiting for admin review.',
                    'verified_by' => null,
                    'verified_at' => null,
                    'dispute_reason' => null,
                    'resolved_by' => null,
                    'resolved_at' => null,
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('reservations')
                ->where('id', $reservation->id)
                ->update([
                    'status' => 'payment_verification',
                    'payment_status' => 'pending_verification',
                    'updated_at' => now(),
                ]);

            $this->audit('payment.proof_uploaded', 'payments', $paymentId, $user, [
                'reservation_code' => $reservation->reservation_code,
                'gcash_reference_number' => $validated['gcash_reference_number'],
            ]);
        });

        // Trigger immediate notification to staff and admins
        app(NotificationService::class)->notifyStaffAndAdmins(
            'New Payment Proof Uploaded',
            "Booking {$reservation->reservation_code} requires verification. Reference number: {$validated['gcash_reference_number']}.",
            Reservation::find($reservation->id),
            [
                'payment_id' => $paymentId,
                'reference_number' => $validated['gcash_reference_number'],
                'amount' => $reservation->grand_total,
            ]
        );

        return redirect()
            ->route('modules.show', 'payments')
            ->with('status', 'GCash screenshot uploaded. Admin will review it.');
    }

    public function approvePayment(Request $request, int $payment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canReviewPayments($user), 403);

        $row = $this->reviewablePaymentQuery($user)
            ->where('p.id', $payment)
            ->first(['p.*', 'r.reservation_code']);

        abort_unless($row, 404);

        DB::transaction(function () use ($row, $user) {
            DB::table('payments')
                ->where('id', $row->id)
                ->update([
                    'status' => 'verified',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                    'updated_at' => now(),
                ]);

            DB::table('reservations')
                ->where('id', $row->reservation_id)
                ->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'confirmed_at' => now(),
                    'confirmed_by' => $user->id,
                    'updated_at' => now(),
                ]);

            DB::table('gcash_transactions_log')
                ->where('payment_id', $row->id)
                ->update([
                    'status' => 'verified',
                    'verification_notes' => 'Approved by '.$user->email,
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->audit('payment.approved', 'payments', $row->id, $user, [
                'reservation_code' => $row->reservation_code,
                'payment_reference' => $row->payment_reference,
            ]);
        });

        // Email digital receipt (G4) and push in-app notification.
        $reservation = Reservation::query()->find($row->reservation_id);
        if ($reservation) {
            try {
                Mail::to($reservation->user->email)->queue(
                    new ReservationReceiptMail($reservation),
                );
            } catch (\Throwable $e) {
                report($e);
            }

            app(NotificationService::class)->notify(
                $reservation->user_id,
                'reservation',
                'Payment approved for '.$row->reservation_code,
                'Your booking is now confirmed. Tap to view the receipt.',
                $reservation,
            );
        }

        return redirect()
            ->route('modules.show', 'payments')
            ->with('status', 'Payment approved and reservation confirmed.');
    }

    public function rejectPayment(Request $request, int $payment): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        abort_unless($this->canReviewPayments($user), 403);

        $row = $this->reviewablePaymentQuery($user)
            ->where('p.id', $payment)
            ->first(['p.*', 'r.reservation_code']);

        abort_unless($row, 404);

        DB::transaction(function () use ($row, $user, $validated) {
            DB::table('payments')
                ->where('id', $row->id)
                ->update([
                    'status' => 'rejected',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'],
                    'updated_at' => now(),
                ]);

            DB::table('reservations')
                ->where('id', $row->reservation_id)
                ->update([
                    'status' => 'pending_payment',
                    'payment_status' => 'unpaid',
                    'updated_at' => now(),
                ]);

            DB::table('gcash_transactions_log')
                ->where('payment_id', $row->id)
                ->update([
                    'status' => 'rejected',
                    'verification_notes' => $validated['rejection_reason'],
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->audit('payment.rejected', 'payments', $row->id, $user, [
                'reservation_code' => $row->reservation_code,
                'payment_reference' => $row->payment_reference,
                'reason' => $validated['rejection_reason'],
            ]);
        });

        return redirect()
            ->route('modules.show', 'payments')
            ->with('status', 'Payment rejected. Customer can upload a new screenshot.');
    }

    public function updateGcashSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]), 403);

        $validated = $request->validate([
            'owner_gcash_number' => ['required', 'string', 'max:20'],
            'owner_gcash_qr' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'xpaylink_enabled' => ['nullable'],
            'xpaylink_public_key' => ['nullable', 'string', 'max:255'],
            'xpaylink_secret_key' => ['nullable', 'string', 'max:255'],
            'xpaylink_endpoint' => ['nullable', 'url', 'max:500'],
            'booking_timeout_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'max_pending_bookings_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $timeout = $validated['booking_timeout_minutes'] ?? 3;
        $limit = $validated['max_pending_bookings_limit'] ?? 1;

        SystemSetting::set('owner_gcash_number', $validated['owner_gcash_number']);

        if ($request->hasFile('owner_gcash_qr')) {
            $path = $request->file('owner_gcash_qr')->store('uploads', 'public');
            $url = Storage::url($path);
            SystemSetting::set('owner_gcash_qr_path', $url);
        }

        $xpaylinkEnabled = $request->has('xpaylink_enabled') ? 'true' : 'false';
        SystemSetting::set('xpaylink_enabled', $xpaylinkEnabled);
        SystemSetting::set('xpaylink_public_key', $validated['xpaylink_public_key'] ?? '');
        SystemSetting::set('xpaylink_secret_key', $validated['xpaylink_secret_key'] ?? '');
        SystemSetting::set('xpaylink_endpoint', $validated['xpaylink_endpoint'] ?? 'https://synthwave.space/api/create-session.php');
        SystemSetting::set('booking_timeout_minutes', $timeout);
        SystemSetting::set('max_pending_bookings_limit', $limit);

        $this->audit('settings.gcash.updated', 'system_settings', null, $user, [
            'owner_gcash_number' => $validated['owner_gcash_number'],
            'owner_gcash_qr_uploaded' => $request->hasFile('owner_gcash_qr'),
            'xpaylink_enabled' => $xpaylinkEnabled,
            'xpaylink_public_key' => $validated['xpaylink_public_key'] ?? '',
            'xpaylink_secret_key' => $validated['xpaylink_secret_key'] ?? '',
            'xpaylink_endpoint' => $validated['xpaylink_endpoint'] ?? '',
            'booking_timeout_minutes' => $timeout,
            'max_pending_bookings_limit' => $limit,
        ]);

        return redirect()
            ->route('modules.show', 'payments')
            ->with('status', 'GCash settings updated successfully.');
    }

    public function updatePublicSiteSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole(User::ROLE_SUPER_ADMIN), 403);

        if ($request->has('public_playing_open_time')) {
            $open = $request->input('public_playing_open_time');
            if (preg_match('/(AM|PM)/i', $open)) {
                $time = date('H:i', strtotime($open));
                $request->merge(['public_playing_open_time' => $time]);
            }
        }
        if ($request->has('public_playing_close_time')) {
            $close = $request->input('public_playing_close_time');
            if (preg_match('/(AM|PM)/i', $close)) {
                $time = date('H:i', strtotime($close));
                $request->merge(['public_playing_close_time' => $time]);
            }
        }

        $validated = $request->validate([
            'public_playing_open_time' => ['required', 'date_format:H:i'],
            'public_playing_close_time' => ['required', 'date_format:H:i', 'different:public_playing_open_time'],
            'public_facebook_url' => ['nullable', 'url', 'max:500'],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'public_contact_phone' => ['nullable', 'string', 'max:30'],
            'public_developer_name' => ['nullable', 'string', 'max:100'],
            'public_developer_url' => ['nullable', 'url', 'max:500'],
            'booking_terms_and_conditions' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value);
        }

        $enableDelete = $request->has('enable_book_history_deletion') ? 'true' : 'false';
        SystemSetting::set('enable_book_history_deletion', $enableDelete);
        $validated['enable_book_history_deletion'] = $enableDelete;

        $enableLunchBreak = $request->has('enable_lunch_break') ? 'true' : 'false';
        SystemSetting::set('enable_lunch_break', $enableLunchBreak);
        $validated['enable_lunch_break'] = $enableLunchBreak;

        $this->audit('settings.public_site.updated', 'system_settings', null, $user, $validated);

        return redirect()
            ->route('modules.show', 'general-settings')
            ->with('status', 'General settings updated successfully.');
    }

    /**
     * @return array<string, array{
     *     title: string,
     *     eyebrow: string,
     *     description: string,
     *     icon: string,
     *     objectives: string,
     *     owner: string,
     *     status: string,
     *     cards: array<int, array{title: string, text: string, icon: string}>,
     *     rows: array<int, array{feature: string, objective: string, note: string}>
     * }>
     */
    private function pages(): array
    {
        return [
            'locations' => [
                'title' => 'Location Management',
                'eyebrow' => 'Admin Module',
                'description' => 'Manage branches, operating hours, addresses, contact details, and map-ready coordinates.',
                'icon' => 'fa-map-marker-alt',
                'objectives' => 'B1, B4, L3',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Branches', 'text' => 'Create and update location records for every Pickle Ballan ni Juan branch.', 'icon' => 'fa-building'],
                    ['title' => 'Operating Hours', 'text' => 'Prepare independent schedules per location and court.', 'icon' => 'fa-clock'],
                    ['title' => 'Map Details', 'text' => 'Store addresses and coordinates for directions.', 'icon' => 'fa-map'],
                ],
                'rows' => [
                    ['feature' => 'Branch directory', 'objective' => 'B1', 'note' => 'Multiple locations with independent settings.'],
                    ['feature' => 'Google Maps details', 'objective' => 'B4', 'note' => 'Pins, addresses, and direction-ready data.'],
                    ['feature' => 'Admin location setup', 'objective' => 'L3', 'note' => 'Create, edit, and manage branch profile data.'],
                ],
            ],
            'courts' => [
                'title' => 'Court Management',
                'eyebrow' => 'Admin Module',
                'description' => 'Set up courts, photos, schedules, maintenance closures, and pricing-ready court records.',
                'icon' => 'fa-table-tennis',
                'objectives' => 'B2-B7, C1-C6',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Court Records', 'text' => 'Add courts per branch with status and basic metadata.', 'icon' => 'fa-table-tennis'],
                    ['title' => 'Photos', 'text' => 'Plan multiple court photos with primary image support.', 'icon' => 'fa-images'],
                    ['title' => 'Maintenance', 'text' => 'Block regular, recurring, event, or emergency closures.', 'icon' => 'fa-tools'],
                ],
                'rows' => [
                    ['feature' => 'Court CRUD', 'objective' => 'B2', 'note' => 'Add, edit, delete, and manage courts.'],
                    ['feature' => 'Court photos', 'objective' => 'B3', 'note' => 'Multiple images and primary selection.'],
                    ['feature' => 'Maintenance closures', 'objective' => 'B6-B7', 'note' => 'Single and recurring closures.'],
                    ['feature' => 'Pricing rules', 'objective' => 'C1-C6', 'note' => 'Rates, surcharges, taxes, limits, and discounts.'],
                ],
            ],
            'payments' => [
                'title' => 'Payment Verification',
                'eyebrow' => 'Admin Module',
                'description' => 'Review GCash screenshots, reference numbers, approvals, rejections, and verification audit details.',
                'icon' => 'fa-money-check-alt',
                'objectives' => 'F1-F12, G1-G7',
                'owner' => 'Admin, Staff with permission',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Pending Proof', 'text' => 'List uploaded screenshots and reference numbers.', 'icon' => 'fa-receipt'],
                    ['title' => 'Confirm or Reject', 'text' => 'Move reservations to paid or cancelled with a reason.', 'icon' => 'fa-check-circle'],
                    ['title' => 'Digital Receipt', 'text' => 'Generate receipt after payment confirmation.', 'icon' => 'fa-file-invoice'],
                ],
                'rows' => [
                    ['feature' => 'GCash proof upload', 'objective' => 'F1-F5', 'note' => 'Customer submits screenshot and reference number.'],
                    ['feature' => 'Staff verification', 'objective' => 'F6-F10', 'note' => 'Confirm, reject, or process walk-in cash.'],
                    ['feature' => 'Audit trail', 'objective' => 'F11', 'note' => 'Track who verified each payment and when.'],
                    ['feature' => 'Receipt access', 'objective' => 'G1-G7', 'note' => 'Receipt available in dashboard and check-in screen.'],
                ],
            ],
            'equipment' => [
                'title' => 'Equipment Inventory',
                'eyebrow' => 'Admin Module',
                'description' => 'Track rackets, balls, rentals, stock status, low-stock alerts, damage logs, and deposits.',
                'icon' => 'fa-boxes',
                'objectives' => 'D1-D9, O6-O7',
                'owner' => 'Admin, Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Inventory States', 'text' => 'Available, reserved, damaged, lost, and under maintenance.', 'icon' => 'fa-warehouse'],
                    ['title' => 'Rental Pricing', 'text' => 'Set prices per equipment type.', 'icon' => 'fa-tags'],
                    ['title' => 'Damage Charges', 'text' => 'Log return status and add customer charges.', 'icon' => 'fa-exclamation-triangle'],
                ],
                'rows' => [
                    ['feature' => 'Equipment selection', 'objective' => 'D1-D2', 'note' => 'Customers rent gear during booking.'],
                    ['feature' => 'Inventory deduction', 'objective' => 'D3-D5', 'note' => 'Deduct on confirmation, restore on cancellation.'],
                    ['feature' => 'Low-stock and damage', 'objective' => 'D6-D9', 'note' => 'Alerts, loss, damage, charges, and deposits.'],
                    ['feature' => 'Inventory reports', 'objective' => 'O6-O7', 'note' => 'Usage and stock-level reporting.'],
                ],
            ],
            'reports' => [
                'title' => 'Reports and Audit',
                'eyebrow' => 'Admin Module',
                'description' => 'Monitor revenue, utilization, peak hours, cancellations, no-shows, customers, exports, and audit logs.',
                'icon' => 'fa-file-export',
                'objectives' => 'L9-L11, O1-O10, P2',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Revenue', 'text' => 'Daily, weekly, and monthly income by location and payment method.', 'icon' => 'fa-coins'],
                    ['title' => 'Utilization', 'text' => 'Booked court hours compared with available court hours.', 'icon' => 'fa-chart-line'],
                    ['title' => 'Audit Logs', 'text' => 'Significant system actions with user and timestamp.', 'icon' => 'fa-history'],
                ],
                'rows' => [
                    ['feature' => 'Revenue and bookings', 'objective' => 'O1-O2', 'note' => 'Revenue and reservation volume reporting.'],
                    ['feature' => 'Operations reports', 'objective' => 'O3-O8', 'note' => 'Peak hours, cancellations, no-shows, inventory, utilization.'],
                    ['feature' => 'Customer and export reports', 'objective' => 'O9-O10', 'note' => 'Customer behavior and export functions.'],
                    ['feature' => 'Audit trail', 'objective' => 'P2', 'note' => 'Log significant actions across the system.'],
                ],
            ],
            'walk-ins' => [
                'title' => 'Walk-in Booking',
                'eyebrow' => 'Staff Module',
                'description' => 'Create counter bookings for walk-in customers with cash or manually verified GCash payment.',
                'icon' => 'fa-walking',
                'objectives' => 'H1-H7',
                'owner' => 'Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Counter Booking', 'text' => 'Create reservations for customers at the branch.', 'icon' => 'fa-calendar-plus'],
                    ['title' => 'Cash or GCash', 'text' => 'Mark cash as paid or verify GCash manually.', 'icon' => 'fa-cash-register'],
                    ['title' => 'Receipt', 'text' => 'Generate a digital receipt for the walk-in customer.', 'icon' => 'fa-receipt'],
                ],
                'rows' => [
                    ['feature' => 'Staff-created reservation', 'objective' => 'H1', 'note' => 'Staff books on behalf of walk-in customers.'],
                    ['feature' => 'Payment handling', 'objective' => 'H2-H3', 'note' => 'Cash or manual GCash verification.'],
                    ['feature' => 'Customer details', 'objective' => 'H4-H5', 'note' => 'Name, mobile number, optional account creation.'],
                    ['feature' => 'Receipt and inventory', 'objective' => 'H6-H7', 'note' => 'Receipt generation and equipment deduction.'],
                ],
            ],
            'check-ins' => [
                'title' => 'Check-in',
                'eyebrow' => 'Staff Module',
                'description' => 'Search reservations, validate payment status, mark arrivals, and release rented equipment.',
                'icon' => 'fa-clipboard-check',
                'objectives' => 'I1-I5',
                'owner' => 'Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Search', 'text' => 'Find reservations by ID, name, date, time, or court.', 'icon' => 'fa-search'],
                    ['title' => 'Validate', 'text' => 'Review full reservation and payment details.', 'icon' => 'fa-id-card'],
                    ['title' => 'Release Gear', 'text' => 'Mark equipment as released during arrival.', 'icon' => 'fa-hand-holding'],
                ],
                'rows' => [
                    ['feature' => 'Reservation search', 'objective' => 'I1', 'note' => 'Search by ID, customer, or schedule details.'],
                    ['feature' => 'Reservation details', 'objective' => 'I2', 'note' => 'Show payment status and equipment rented.'],
                    ['feature' => 'Arrival record', 'objective' => 'I3-I4', 'note' => 'Record check-in time and staff user.'],
                    ['feature' => 'Equipment release', 'objective' => 'I5', 'note' => 'Release rented items to customer.'],
                ],
            ],
            'check-outs' => [
                'title' => 'Check-out',
                'eyebrow' => 'Staff Module',
                'description' => 'Complete sessions, record equipment returns, damage or loss, and collect additional charges.',
                'icon' => 'fa-sign-out-alt',
                'objectives' => 'I6-I9',
                'owner' => 'Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Complete Session', 'text' => 'Mark reservation as completed after play time.', 'icon' => 'fa-check-double'],
                    ['title' => 'Return Status', 'text' => 'Record returned, damaged, or lost equipment.', 'icon' => 'fa-clipboard-list'],
                    ['title' => 'Extra Charges', 'text' => 'Calculate and collect late or damage fees.', 'icon' => 'fa-coins'],
                ],
                'rows' => [
                    ['feature' => 'Session completion', 'objective' => 'I6', 'note' => 'Complete the reservation after the session ends.'],
                    ['feature' => 'Equipment return', 'objective' => 'I7', 'note' => 'Track returned, damaged, or lost equipment.'],
                    ['feature' => 'Additional charges', 'objective' => 'I8-I9', 'note' => 'Damage and late fees with payment collection.'],
                ],
            ],
            'book-court' => [
                'title' => 'Book Court',
                'eyebrow' => 'Customer Module',
                'description' => 'Browse court availability, choose a slot, add equipment, review pricing, and submit booking.',
                'icon' => 'fa-calendar-plus',
                'objectives' => 'N3-N5, E1-E10',
                'owner' => 'End User',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Availability', 'text' => 'Browse by date, branch, court, and time.', 'icon' => 'fa-calendar-day'],
                    ['title' => 'Equipment', 'text' => 'Add rackets or balls to the reservation.', 'icon' => 'fa-table-tennis'],
                    ['title' => 'Pricing', 'text' => 'Review court fee, rentals, discounts, taxes, and total.', 'icon' => 'fa-calculator'],
                ],
                'rows' => [
                    ['feature' => 'Calendar browsing', 'objective' => 'E1-E7, N3', 'note' => 'Find available courts and equipment.'],
                    ['feature' => 'Reservation creation', 'objective' => 'E8-E10, N4', 'note' => 'Calculate price and hold pending slots.'],
                    ['feature' => 'Payment handoff', 'objective' => 'N5', 'note' => 'Proceed to GCash payment proof upload.'],
                ],
            ],
            'receipts' => [
                'title' => 'Book History',
                'eyebrow' => 'Customer Module',
                'description' => 'View your approved court bookings, receipts, and payment details.',
                'icon' => 'fa-history',
                'objectives' => 'G1-G7, N6',
                'owner' => 'End User, Staff',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Paid bookings', 'text' => 'Approved and confirmed bookings.', 'icon' => 'fa-check-circle'],
                    ['title' => 'GCash Reference', 'text' => 'Show payment method and reference number.', 'icon' => 'fa-wallet'],
                    ['title' => 'Access Anytime', 'text' => 'Bookings history remains available for review.', 'icon' => 'fa-clock'],
                ],
                'rows' => [
                    ['feature' => 'Book History details', 'objective' => 'G1-G2', 'note' => 'Bookings are stored here after they are approved.'],
                    ['feature' => 'Dashboard access', 'objective' => 'G3, N6', 'note' => 'Users can view approved bookings anytime.'],
                    ['feature' => 'Receipt lookup', 'objective' => 'G4-G7', 'note' => 'View fully detailed digital invoice receipts.'],
                ],
            ],
            'reviews' => [
                'title' => 'Reviews',
                'eyebrow' => 'Customer Module',
                'description' => 'Collect customer ratings, court reviews, admin responses, moderation, and audited rating changes.',
                'icon' => 'fa-star',
                'objectives' => 'K1-K10, N8',
                'owner' => 'End User, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Ratings', 'text' => 'Rate completed bookings from 1 to 5 stars.', 'icon' => 'fa-star-half-alt'],
                    ['title' => 'Review Text', 'text' => 'Leave comments and optional photos.', 'icon' => 'fa-comment-alt'],
                    ['title' => 'Moderation', 'text' => 'Admin responds, hides, adjusts, and audits changes.', 'icon' => 'fa-user-shield'],
                ],
                'rows' => [
                    ['feature' => 'Customer ratings', 'objective' => 'K1-K4, N8', 'note' => 'Rating and review flow after completed bookings.'],
                    ['feature' => 'Admin management', 'objective' => 'K5-K8', 'note' => 'View, respond, adjust, or hide reviews.'],
                    ['feature' => 'Helpfulness and audit', 'objective' => 'K9-K10', 'note' => 'Helpful votes and audited rating changes.'],
                ],
            ],
            'users' => [
                'title' => 'User Management',
                'eyebrow' => 'Admin Module',
                'description' => 'Create staff and admin accounts, assign roles, manage end-user accounts, and trigger password resets.',
                'icon' => 'fa-users-cog',
                'objectives' => 'A1-A7, L5',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Roles', 'text' => 'Super admin, admin, manager, staff, and customer accounts.', 'icon' => 'fa-id-badge'],
                    ['title' => 'Account Status', 'text' => 'Activate, disable, archive, and unlock accounts.', 'icon' => 'fa-user-check'],
                    ['title' => 'Password Resets', 'text' => 'Trigger reset emails for any user from the panel.', 'icon' => 'fa-key'],
                ],
                'rows' => [
                    ['feature' => 'Staff and admin creation', 'objective' => 'A3, L5', 'note' => 'Admin-only creation for staff/admin/manager.'],
                    ['feature' => 'Role assignment', 'objective' => 'A4', 'note' => 'Five distinct role permissions.'],
                    ['feature' => 'Account lockout/reset', 'objective' => 'A6, A7', 'note' => 'Lock counters, reset links via email.'],
                ],
            ],
            'sales' => [
                'title' => 'Sales',
                'eyebrow' => 'Admin Module',
                'description' => 'Full booking and sales history across all customers, with date filters and CSV or PDF export.',
                'icon' => 'fa-cash-register',
                'objectives' => 'O1-O2, O10, G1-G7',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Sales History', 'text' => 'Every approved booking and its payment in one place.', 'icon' => 'fa-receipt'],
                    ['title' => 'Date Filters', 'text' => 'Slice sales by day, week, month, or a custom range.', 'icon' => 'fa-calendar-day'],
                    ['title' => 'Export', 'text' => 'Download the filtered sales report as CSV or PDF.', 'icon' => 'fa-file-export'],
                ],
                'rows' => [
                    ['feature' => 'Sales history', 'objective' => 'O1-O2', 'note' => 'All customer bookings and payments.'],
                    ['feature' => 'CSV export', 'objective' => 'O10', 'note' => 'Spreadsheet-ready sales download.'],
                    ['feature' => 'PDF export', 'objective' => 'O10', 'note' => 'Printable sales report.'],
                ],
            ],
            'general-settings' => [
                'title' => 'General Settings',
                'eyebrow' => 'Admin Module',
                'description' => 'System-wide configuration: opening time, floating contact links, and Book History bulk options.',
                'icon' => 'fa-sliders-h',
                'objectives' => 'L1-L4',
                'owner' => 'Super Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Opening Time', 'text' => 'Set the public playing hours shown to customers.', 'icon' => 'fa-clock'],
                    ['title' => 'Contact Links', 'text' => 'Configure the floating Facebook, email, and phone links.', 'icon' => 'fa-address-book'],
                    ['title' => 'Bulk Options', 'text' => 'Enable or disable delete and bulk-delete on Book History.', 'icon' => 'fa-trash-alt'],
                ],
                'rows' => [
                    ['feature' => 'Opening time', 'objective' => 'L1', 'note' => 'Public playing open and close hours.'],
                    ['feature' => 'Floating contact links', 'objective' => 'L2', 'note' => 'Facebook, email, phone, highlighted name.'],
                    ['feature' => 'Bulk options', 'objective' => 'L3', 'note' => 'Delete and bulk-delete toggle for bookings.'],
                ],
            ],
            'rates' => [
                'title' => 'Rates Management',
                'eyebrow' => 'Admin Module',
                'description' => 'Configure standard base rates, peak season rules, and equipment rental rates for all branches.',
                'icon' => 'fa-tags',
                'objectives' => 'C1-C6, D2',
                'owner' => 'Super Admin, Admin',
                'status' => 'Planned',
                'cards' => [
                    ['title' => 'Court Pricing', 'text' => 'Set custom pricing rules and base hourly rates per court.', 'icon' => 'fa-table-tennis'],
                    ['title' => 'Equipment Pricing', 'text' => 'Set daily or per-unit rental rates and deposit amounts.', 'icon' => 'fa-boxes'],
                ],
                'rows' => [
                    ['feature' => 'Court pricing rules', 'objective' => 'C1-C6', 'note' => 'Standard, weekend, and holiday rates.'],
                    ['feature' => 'Equipment pricing', 'objective' => 'D2', 'note' => 'Racket and ball rental prices.'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    private function withLiveData(string $module, array $page, User $user): array
    {
        $live = match ($module) {
            'locations' => $this->locationData(),
            'courts' => $this->courtData($user),
            'payments' => $this->paymentData($user),
            'equipment' => $this->equipmentData($user),
            'reports' => $this->reportData(),
            'walk-ins' => $this->walkInData($user),
            'check-ins' => $this->checkInData($user),
            'check-outs' => $this->checkOutData($user),
            'book-court' => $this->bookCourtData(),
            'receipts' => $this->receiptData($user),
            'reviews' => $this->reviewData($user),
            'users' => $this->userData($user),
            'sales' => $this->receiptData($user),
            'general-settings' => $this->generalSettingsData(),
            'rates' => $this->ratesData($user),
            default => [],
        };

        return array_replace($page, ['status' => 'Live'], $live);
    }

    /**
     * @return array<string, mixed>
     */
    private function locationData(): array
    {
        $today = now()->toDateString();
        $user = request()->user();
        $canManage = $user ? $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]) : false;

        $locations = DB::table('locations')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
        $activeCourts = DB::table('courts')->where('is_active', true)->whereNull('deleted_at')->count();
        $todayRevenue = DB::table('daily_reports_aggregates as dra')
            ->join('locations as l', 'l.id', '=', 'dra.location_id')
            ->where('l.is_active', true)
            ->whereNull('l.deleted_at')
            ->where('dra.report_date', $today)
            ->sum('dra.total_revenue');

        $manageable = $locations->map(function ($location) {
            $courtCount = DB::table('courts')->where('location_id', $location->id)->whereNull('deleted_at')->count();

            return [
                'id' => (int) $location->id,
                'name' => $location->name,
                'branch_code' => $location->branch_code,
                'address_line1' => $location->address_line1,
                'address_line2' => $location->address_line2,
                'city' => $location->city,
                'province' => $location->province,
                'postal_code' => $location->postal_code,
                'country' => $location->country,
                'latitude' => $location->latitude !== null ? (float) $location->latitude : null,
                'longitude' => $location->longitude !== null ? (float) $location->longitude : null,
                'whatsapp_number' => $location->whatsapp_number,
                'landline_number' => $location->landline_number,
                'email_address' => $location->email_address,
                'timezone' => $location->timezone,
                'is_active' => (bool) $location->is_active,
                'court_count' => $courtCount,
            ];
        })->values()->all();

        return [
            'canManageLocations' => $canManage,
            'manageableLocations' => $manageable,
            'cards' => [
                $this->card('Active branches', (string) $locations->where('is_active', 1)->count(), 'Locations accepting bookings today.', 'fa-building'),
                $this->card('Active courts', (string) $activeCourts, 'Courts assigned across all branches.', 'fa-table-tennis'),
                $this->card('Revenue today', $this->money($todayRevenue), 'From branch report aggregates.', 'fa-coins'),
            ],
            'rows' => $locations->map(function ($location) use ($today) {
                $courtCount = DB::table('courts')->where('location_id', $location->id)->whereNull('deleted_at')->count();
                $report = DB::table('daily_reports_aggregates')
                    ->where('location_id', $location->id)
                    ->where('report_date', $today)
                    ->first();

                return $this->row(
                    $location->name,
                    ($location->city ?: 'Unknown').', '.($location->province ?: '').' | '.$courtCount.' courts | '.$this->money($report->total_revenue ?? 0).' today',
                    $location->is_active ? 'Active' : 'Inactive',
                    (int) round($report->utilization_rate ?? 0),
                    'B1, L3',
                    $location->is_active ? 'success' : 'secondary',
                );
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courtData(User $user): array
    {
        $today = now()->toDateString();
        $courts = DB::table('courts')
            ->join('locations', 'locations.id', '=', 'courts.location_id')
            ->whereNull('locations.deleted_at')
            ->whereNull('courts.deleted_at')
            ->orderBy('locations.name')
            ->orderBy('courts.display_order')
            ->get([
                'courts.id',
                'courts.court_number',
                'courts.court_name',
                'courts.court_type',
                'courts.surface_type',
                'courts.is_active',
                'courts.location_id',
                'locations.name as location_name',
                'locations.is_active as location_active',
            ]);
        $maintenanceToday = DB::table('court_maintenance as cm')
            ->join('courts as c', 'c.id', '=', 'cm.court_id')
            ->where('c.is_active', true)
            ->whereNull('c.deleted_at')
            ->whereDate('cm.start_datetime', '<=', $today)
            ->whereDate('cm.end_datetime', '>=', $today)
            ->count();

        $manageable = $courts->map(function ($court) use ($today) {
            $rate = DB::table('court_pricing_rules')
                ->where('court_id', $court->id)
                ->where('is_active', true)
                ->orderBy('priority')
                ->value('base_price');
            $bookings = DB::table('reservations')
                ->where('court_id', $court->id)
                ->where('reservation_date', $today)
                ->whereNull('deleted_at')
                ->count();

            return [
                'id' => (int) $court->id,
                'location_id' => (int) $court->location_id,
                'location_name' => $court->location_name,
                'court_number' => $court->court_number,
                'court_name' => $court->court_name,
                'court_type' => $court->court_type,
                'surface_type' => $court->surface_type,
                'base_price' => (float) ($rate ?? 0),
                'is_active' => (bool) $court->is_active,
                'today_bookings' => $bookings,
            ];
        })->values()->all();

        return [
            'cards' => [
                $this->card('Active courts', (string) $courts->where('is_active', 1)->count(), 'Courts visible for scheduling.', 'fa-table-tennis'),
                $this->card('Blocked today', (string) $maintenanceToday, 'Maintenance or event windows active today.', 'fa-tools'),
                $this->card('Pricing rules', (string) DB::table('court_pricing_rules as cpr')->join('courts as c', 'c.id', '=', 'cpr.court_id')->where('cpr.is_active', true)->where('c.is_active', true)->whereNull('c.deleted_at')->count(), 'Standard, peak, and weekend rates.', 'fa-tags'),
            ],
            'locations' => $this->activeLocations(),
            'canManageOperations' => $this->canManageOperations($user),
            'manageableCourts' => $manageable,
            'rows' => $courts->map(function ($court) use ($today) {
                $rate = DB::table('court_pricing_rules')
                    ->where('court_id', $court->id)
                    ->where('is_active', true)
                    ->orderBy('priority')
                    ->value('base_price');
                $bookings = DB::table('reservations')
                    ->where('court_id', $court->id)
                    ->where('reservation_date', $today)
                    ->whereNull('deleted_at')
                    ->count();

                return $this->row(
                    $court->court_name ?: 'Court '.$court->court_number,
                    $court->location_name.' | '.ucfirst($court->court_type).' '.$court->surface_type.' | '.$this->money($rate).'/hr | '.$bookings.' booking(s) today',
                    $court->is_active ? 'Active' : 'Hidden',
                    min(100, max(20, $bookings * 25)),
                    'B2-B7',
                    $court->is_active ? 'success' : 'secondary',
                );
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ratesData(User $user): array
    {
        return [
            'courts' => $this->courtData($user)['manageableCourts'] ?? [],
            'equipment' => $this->equipmentData($user)['manageableEquipment'] ?? [],
            'locationOptions' => $this->activeLocations(),
            'canManageOperations' => $this->canManageOperations($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Shared system-wide public site settings (opening time, floating contact links).
     *
     * @return array<string, string>
     */
    private function publicSiteSettingsValues(): array
    {
        return [
            'public_playing_open_time' => SystemSetting::value('public_playing_open_time', '07:00'),
            'public_playing_close_time' => SystemSetting::value('public_playing_close_time', '00:00'),
            'public_facebook_url' => SystemSetting::value('public_facebook_url', 'https://www.facebook.com/profile.php?id=61584658084190'),
            'public_contact_email' => SystemSetting::value('public_contact_email', 'cajpulido@yahoo.com'),
            'public_contact_phone' => SystemSetting::value('public_contact_phone', '09383427139'),
            'public_developer_name' => SystemSetting::value('public_developer_name', 'RestBack'),
            'public_developer_url' => SystemSetting::value('public_developer_url', 'https://www.facebook.com/restback200/'),
            'booking_terms_and_conditions' => SystemSetting::value('booking_terms_and_conditions', "IMPORTANT: No refunds will be issued under any circumstances unless court operations are suspended due to inclement weather (e.g. rain). Please ensure you send the exact GCash amount including decimal fractions. Failures to do so will result in booking cancellation without refund."),
            'enable_lunch_break' => SystemSetting::value('enable_lunch_break', 'true'),
        ];
    }

    /**
     * Live data for the General Settings module page.
     *
     * @return array<string, mixed>
     */
    private function generalSettingsData(): array
    {
        return [
            'publicSiteSettings' => $this->publicSiteSettingsValues(),
        ];
    }

    private function paymentData(User $user): array
    {
        $today = now()->toDateString();
        $canReview = $this->canReviewPayments($user);
        $ownerGcashNumber = DB::table('system_settings')->where('setting_key', 'owner_gcash_number')->value('setting_value') ?: '09123456789';
        $ownerGcashQr = DB::table('system_settings')->where('setting_key', 'owner_gcash_qr_path')->value('setting_value') ?: null;
        $base = $this->scopeByUser(
            DB::table('payments as p')
                ->join('reservations as r', 'r.id', '=', 'p.reservation_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
                ->whereNull('r.deleted_at'),
            'r',
            $user,
        );

        $rows = (clone $base)
            ->orderByDesc('p.created_at')
            ->limit(8)
            ->get([
                'p.payment_reference',
                'p.amount',
                'p.payment_method',
                'p.status',
                'p.gcash_reference_number',
                'p.gcash_screenshot_path',
                'r.reservation_code',
                'eup.first_name',
                'eup.last_name',
                'u.email',
                'u.photo_path',
            ]);

        $uploadReservations = $this->paymentUploadRows($user);
        $reviewPayments = $canReview ? $this->paymentReviewRows($user) : [];

        $xpaylinkEnabled = SystemSetting::value('xpaylink_enabled', 'false');
        $xpaylinkPublicKey = SystemSetting::value('xpaylink_public_key', '');
        $xpaylinkSecretKey = SystemSetting::value('xpaylink_secret_key', '');
        $xpaylinkEndpoint = SystemSetting::value('xpaylink_endpoint', 'https://synthwave.space/api/create-session.php');
        $bookingTimeoutMinutes = SystemSetting::value('booking_timeout_minutes', '3');
        $maxPendingBookingsLimit = SystemSetting::value('max_pending_bookings_limit', '1');

        return [
            'ownerGcashNumber' => $ownerGcashNumber,
            'ownerGcashQr' => $ownerGcashQr,
            'xpaylinkEnabled' => $xpaylinkEnabled,
            'xpaylinkPublicKey' => $xpaylinkPublicKey,
            'xpaylinkSecretKey' => $xpaylinkSecretKey,
            'xpaylinkEndpoint' => $xpaylinkEndpoint,
            'bookingTimeoutMinutes' => $bookingTimeoutMinutes,
            'maxPendingBookingsLimit' => $maxPendingBookingsLimit,
            'publicSiteSettings' => $this->publicSiteSettingsValues(),
            'canReviewPayments' => $canReview,
            'paymentUploads' => $uploadReservations,
            'paymentReviews' => $reviewPayments,
            'cards' => [
                $this->card('Pending proof', (string) (clone $base)->where('p.status', 'pending')->count(), 'GCash references awaiting review.', 'fa-receipt'),
                $this->card('Verified today', $this->money((clone $base)->whereDate('p.created_at', $today)->where('p.status', 'verified')->sum('p.amount')), 'Revenue confirmed today.', 'fa-check-circle'),
                $this->card('Refunded', (string) (clone $base)->where('p.status', 'refunded')->count(), 'Refunded payment records.', 'fa-undo'),
            ],
            'rows' => $rows->map(function ($payment) {
                $customer = trim(($payment->first_name ?? '').' '.($payment->last_name ?? '')) ?: $payment->email;
                $owner = $this->userOwner($payment->first_name, $payment->last_name, $payment->email, $payment->photo_path);

                return $this->row(
                    $payment->payment_reference,
                    $customer.' | '.$payment->reservation_code.' | '.strtoupper($payment->payment_method).' | '.$this->money($payment->amount).' | Ref '.($payment->gcash_reference_number ?: 'cash').' | '.($payment->gcash_screenshot_path ? 'proof uploaded' : 'no proof image'),
                    ucfirst($payment->status),
                    $this->statusProgress($payment->status),
                    'F1-F12',
                    $this->statusColor($payment->status),
                    [$owner],
                );
            })->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentUploadRows(User $user): array
    {
        if ($this->canReviewPayments($user)) {
            return [];
        }

        return DB::table('reservations as r')
            ->join('courts as c', 'c.id', '=', 'r.court_id')
            ->join('locations as l', 'l.id', '=', 'r.location_id')
            ->leftJoin('payments as p', function ($join) {
                $join->on('p.reservation_id', '=', 'r.id')
                    ->whereIn('p.status', ['pending', 'rejected']);
            })
            ->where('r.user_id', $user->id)
            ->whereNull('r.deleted_at')
            ->whereIn('r.payment_status', ['unpaid', 'pending_verification'])
            ->whereIn('r.status', ['pending_payment', 'payment_verification'])
            ->orderByDesc('r.reservation_date')
            ->orderBy('r.start_time')
            ->limit(6)
            ->get([
                'r.id',
                'r.reservation_code',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'r.grand_total',
                'r.payment_status',
                'r.status as reservation_status',
                'c.court_number',
                'l.name as location_name',
                'p.status as payment_status_row',
                'p.gcash_reference_number',
                'p.rejection_reason',
            ])
            ->map(fn ($reservation) => [
                'id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'schedule' => $reservation->reservation_date.' '.$this->timeRange($reservation->start_time, $reservation->end_time),
                'court' => $reservation->location_name.' Court '.$reservation->court_number,
                'amount' => $this->money($reservation->grand_total),
                'status' => $reservation->payment_status_row
                    ? ucfirst($reservation->payment_status_row)
                    : $this->reservationStatus($reservation->reservation_status, $reservation->payment_status),
                'gcash_reference_number' => $reservation->gcash_reference_number,
                'rejection_reason' => $reservation->rejection_reason,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentReviewRows(User $user): array
    {
        $query = DB::table('payments as p')
            ->join('reservations as r', 'r.id', '=', 'p.reservation_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
            ->join('courts as c', 'c.id', '=', 'r.court_id')
            ->join('locations as l', 'l.id', '=', 'r.location_id')
            ->where('p.status', 'pending')
            ->where('p.payment_method', 'gcash')
            ->whereIn('r.location_id', $this->activeLocationIds())
            ->whereNull('r.deleted_at');

        if (! $this->canSeeAll($user) && $locationId = DB::table('staff_profiles')->where('user_id', $user->id)->value('assigned_location_id')) {
            $query->where('r.location_id', $locationId);
        }

        return $query
            ->orderBy('p.created_at')
            ->limit(10)
            ->get([
                'p.id',
                'p.payment_reference',
                'p.amount',
                'p.gcash_reference_number',
                'p.gcash_sender_number',
                'p.gcash_screenshot_path',
                'p.created_at',
                'r.reservation_code',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'c.court_number',
                'l.name as location_name',
                'eup.first_name',
                'eup.last_name',
                'u.email',
                'u.photo_path',
            ])
            ->map(function ($payment) {
                $customer = trim(($payment->first_name ?? '').' '.($payment->last_name ?? '')) ?: $payment->email;
                $hasProof = $payment->gcash_screenshot_path && Storage::disk('local')->exists($payment->gcash_screenshot_path);

                return [
                    'id' => $payment->id,
                    'payment_reference' => $payment->payment_reference,
                    'reservation_code' => $payment->reservation_code,
                    'customer' => $customer,
                    'schedule' => $payment->reservation_date.' '.$this->timeRange($payment->start_time, $payment->end_time),
                    'court' => $payment->location_name.' Court '.$payment->court_number,
                    'amount' => $this->money($payment->amount),
                    'gcash_reference_number' => $payment->gcash_reference_number,
                    'gcash_sender_number' => $payment->gcash_sender_number,
                    'proof_url' => $hasProof ? URL::signedRoute('payments.proof.download', ['payment' => $payment->id], now()->addMinutes(30)) : null,
                    'created_at' => date('M d, Y H:i', strtotime($payment->created_at)),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function equipmentData(User $user): array
    {
        $inventory = DB::table('equipment_inventory as ei')
            ->join('equipment_types as et', 'et.id', '=', 'ei.equipment_type_id')
            ->join('locations as l', 'l.id', '=', 'ei.location_id')
            ->where('l.is_active', true)
            ->whereNull('l.deleted_at')
            ->whereNull('et.deleted_at')
            ->orderBy('l.name')
            ->orderBy('et.display_order')
            ->get([
                'ei.id as inventory_id',
                'ei.location_id',
                'ei.total_quantity',
                'ei.available_quantity',
                'ei.reserved_quantity',
                'ei.damaged_quantity',
                'ei.lost_quantity',
                'ei.under_maintenance_quantity',
                'ei.reorder_point',
                'et.id as equipment_type_id',
                'et.name as equipment_name',
                'et.description',
                'et.rental_price_per_unit',
                'et.deposit_amount',
                'et.is_available_for_rent',
                'et.max_rental_quantity_per_booking',
                'l.name as location_name',
            ]);

        $lowStock = $inventory->filter(fn ($item) => $item->available_quantity <= $item->reorder_point)->count();

        $manageable = $inventory->map(fn ($item) => [
            'inventory_id' => (int) $item->inventory_id,
            'equipment_type_id' => (int) $item->equipment_type_id,
            'location_id' => (int) $item->location_id,
            'location_name' => $item->location_name,
            'name' => $item->equipment_name,
            'description' => $item->description,
            'rental_price_per_unit' => (float) $item->rental_price_per_unit,
            'deposit_amount' => (float) $item->deposit_amount,
            'reorder_point' => (int) $item->reorder_point,
            'available_quantity' => (int) $item->available_quantity,
            'reserved_quantity' => (int) $item->reserved_quantity,
            'damaged_quantity' => (int) $item->damaged_quantity,
            'lost_quantity' => (int) $item->lost_quantity,
            'total_quantity' => (int) $item->total_quantity,
            'is_available_for_rent' => (bool) $item->is_available_for_rent,
            'max_rental_quantity_per_booking' => (int) $item->max_rental_quantity_per_booking,
        ])->values()->all();

        return [
            'cards' => [
                $this->card('Available items', (string) $inventory->sum('available_quantity'), 'Ready to rent right now.', 'fa-warehouse'),
                $this->card('Reserved items', (string) $inventory->sum('reserved_quantity'), 'Attached to active bookings.', 'fa-box'),
                $this->card('Low stock', (string) $lowStock, 'Inventory rows at or below reorder point.', 'fa-exclamation-triangle'),
            ],
            'locations' => $this->activeLocations(),
            'canManageOperations' => $this->canManageOperations($user),
            'manageableEquipment' => $manageable,
            'rows' => $inventory->map(fn ($item) => $this->row(
                $item->equipment_name,
                $item->location_name.' | '.$item->available_quantity.'/'.$item->total_quantity.' available | '.$item->reserved_quantity.' reserved | '.$this->money($item->rental_price_per_unit).'/unit',
                $item->available_quantity <= $item->reorder_point ? 'Low stock' : 'Ready',
                $this->percent($item->available_quantity, max(1, $item->total_quantity)),
                'D1-D9',
                $item->available_quantity <= $item->reorder_point ? 'warning' : 'success',
            ))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $monthlyReports = DB::table('daily_reports_aggregates as dra')
            ->join('locations as l', 'l.id', '=', 'dra.location_id')
            ->where('l.is_active', true)
            ->whereNull('l.deleted_at')
            ->whereBetween('dra.report_date', [$monthStart, $monthEnd]);
        $reports = DB::table('daily_reports_aggregates as dra')
            ->join('locations as l', 'l.id', '=', 'dra.location_id')
            ->where('l.is_active', true)
            ->whereNull('l.deleted_at')
            ->whereBetween('dra.report_date', [now()->subDays(6)->toDateString(), now()->toDateString()])
            ->orderByDesc('dra.report_date')
            ->orderBy('l.name')
            ->limit(9)
            ->get([
                'dra.report_date',
                'dra.total_reservations',
                'dra.total_cancellations',
                'dra.total_no_shows',
                'dra.total_revenue',
                'dra.average_rating',
                'dra.peak_hour_start',
                'dra.peak_hour_end',
                'dra.utilization_rate',
                'l.name as location_name',
            ]);

        return [
            'cards' => [
                $this->card('Monthly revenue', $this->money((clone $monthlyReports)->sum('dra.total_revenue')), 'Revenue across active venue reports.', 'fa-coins'),
                $this->card('Monthly bookings', (string) (clone $monthlyReports)->sum('dra.total_reservations'), 'Online and walk-in reservations.', 'fa-calendar-check'),
                $this->card('Average rating', number_format((float) (clone $monthlyReports)->avg('dra.average_rating'), 2), 'Active venue customer score.', 'fa-star'),
            ],
            'rows' => $reports->map(fn ($report) => $this->row(
                $report->location_name.' - '.$report->report_date,
                $report->total_reservations.' reservations | '.$this->money($report->total_revenue).' | peak '.substr($report->peak_hour_start, 0, 5).'-'.substr($report->peak_hour_end, 0, 5).' | rating '.number_format((float) $report->average_rating, 2),
                $report->total_no_shows > 0 ? 'No-show flagged' : ($report->total_cancellations > 0 ? 'Cancellation flagged' : 'Healthy'),
                (int) round($report->utilization_rate ?? 0),
                'O1-O10',
                $report->total_no_shows > 0 || $report->total_cancellations > 0 ? 'warning' : 'success',
            ))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function walkInData(User $user): array
    {
        $today = now()->toDateString();
        $base = $this->scopeByUser(
            DB::table('reservations as r')
                ->join('courts as c', 'c.id', '=', 'r.court_id')
                ->join('locations as l', 'l.id', '=', 'r.location_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
                ->where('r.reservation_type', 'walk_in')
                ->whereNull('r.deleted_at'),
            'r',
            $user,
        );

        $cashRevenue = (clone $base)
            ->join('payments as p', 'p.reservation_id', '=', 'r.id')
            ->where('p.payment_method', 'cash')
            ->where('p.status', 'verified')
            ->sum('p.amount');

        $rows = (clone $base)
            ->orderByDesc('r.reservation_date')
            ->orderByDesc('r.start_time')
            ->limit(8)
            ->get([
                'r.reservation_code',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'r.status',
                'r.payment_status',
                'r.grand_total',
                'c.court_number',
                'l.name as location_name',
                'eup.first_name',
                'eup.last_name',
                'u.email',
                'u.photo_path',
            ]);

        return [
            'cards' => [
                $this->card('Walk-ins today', (string) (clone $base)->where('r.reservation_date', $today)->count(), 'Counter-created court bookings.', 'fa-walking'),
                $this->card('Cash revenue', $this->money($cashRevenue), 'Verified cash walk-in payments.', 'fa-cash-register'),
                $this->card('Completed', (string) (clone $base)->where('r.status', 'completed')->count(), 'Walk-in sessions already closed.', 'fa-check-double'),
            ],
            'bookingLocations' => $this->activeLocations(),
            'bookingCourts' => $this->bookableCourts(),
            'bookingEquipment' => $this->rentableEquipment(),
            'locations' => $this->activeLocations(),
            'rawReservations' => (clone $base)
                ->orderByDesc('r.reservation_date')
                ->orderByDesc('r.start_time')
                ->limit(30)
                ->get([
                    'r.id',
                    'r.reservation_code',
                    'r.reservation_date',
                    'r.start_time',
                    'r.end_time',
                    'r.status',
                    'r.payment_status',
                    'r.grand_total',
                    'r.court_price_per_hour',
                    'r.court_subtotal',
                    'r.equipment_total',
                    'r.special_requests',
                    'c.id as court_id',
                    'c.court_number',
                    'c.court_name',
                    'l.name as location_name',
                    'eup.first_name',
                    'eup.last_name',
                    'u.email',
                    'u.mobile_number',
                ])
                ->map(function ($res) {
                    $res->customer_name = trim(($res->first_name ?? '').' '.($res->last_name ?? '')) ?: $res->email;
                    return $res;
                }),
            'rows' => $rows->map(function ($reservation) {
                $customer = trim(($reservation->first_name ?? '').' '.($reservation->last_name ?? '')) ?: $reservation->email;
                $owner = $this->userOwner($reservation->first_name, $reservation->last_name, $reservation->email, $reservation->photo_path);

                return $this->row(
                    $reservation->reservation_code,
                    $customer.' | '.$reservation->location_name.' Court '.$reservation->court_number.' | '.$reservation->reservation_date.' '.$this->timeRange($reservation->start_time, $reservation->end_time).' | '.$this->money($reservation->grand_total),
                    $this->reservationStatus($reservation->status, $reservation->payment_status),
                    $this->statusProgress($reservation->status),
                    'H1-H7',
                    $this->statusColor($reservation->status),
                    [$owner],
                );
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkInData(User $user): array
    {
        $today = now()->toDateString();
        $base = $this->scopeByUser(
            DB::table('reservations as r')
                ->join('courts as c', 'c.id', '=', 'r.court_id')
                ->join('locations as l', 'l.id', '=', 'r.location_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
                ->leftJoin('check_in_logs as cil', 'cil.reservation_id', '=', 'r.id')
                ->where('r.reservation_date', $today)
                ->whereNull('r.deleted_at'),
            'r',
            $user,
        );

        $rows = (clone $base)
            ->orderBy('r.start_time')
            ->get([
                'r.reservation_code',
                'r.start_time',
                'r.end_time',
                'r.status',
                'r.payment_status',
                'c.court_number',
                'l.name as location_name',
                'eup.first_name',
                'eup.last_name',
                'u.email',
                'u.photo_path',
                'cil.checked_in_at',
            ]);

        return [
            'cards' => [
                $this->card('Due today', (string) (clone $base)->whereIn('r.status', ['confirmed'])->whereNull('cil.id')->count(), 'Paid reservations waiting for arrival.', 'fa-search'),
                $this->card('Checked in', (string) (clone $base)->whereNotNull('cil.id')->count(), 'Arrivals already validated.', 'fa-id-card'),
                $this->card('Paid schedule', (string) (clone $base)->where('r.payment_status', 'paid')->count(), 'Receipts staff can validate.', 'fa-receipt'),
            ],
            'rows' => $rows->map(function ($reservation) {
                $customer = trim(($reservation->first_name ?? '').' '.($reservation->last_name ?? '')) ?: $reservation->email;
                $checkedIn = $reservation->checked_in_at !== null;
                $owner = $this->userOwner($reservation->first_name, $reservation->last_name, $reservation->email, $reservation->photo_path);

                return $this->row(
                    $reservation->reservation_code,
                    $customer.' | '.$reservation->location_name.' Court '.$reservation->court_number.' | '.$this->timeRange($reservation->start_time, $reservation->end_time).' | '.$this->reservationStatus($reservation->status, $reservation->payment_status),
                    $checkedIn ? 'Checked in' : 'Due',
                    $checkedIn ? 100 : 60,
                    'I1-I5',
                    $checkedIn ? 'success' : 'warning',
                    [$owner],
                );
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkOutData(User $user): array
    {
        $today = now()->toDateString();
        $base = $this->scopeByUser(
            DB::table('reservations as r')
                ->join('courts as c', 'c.id', '=', 'r.court_id')
                ->join('locations as l', 'l.id', '=', 'r.location_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
                ->leftJoin('check_out_logs as col', 'col.reservation_id', '=', 'r.id')
                ->whereIn('r.status', ['checked_in', 'ongoing', 'completed'])
                ->whereNull('r.deleted_at'),
            'r',
            $user,
        );

        $openReturns = $this->scopeByUser(
            DB::table('reservation_equipment as re')
                ->join('reservations as r', 'r.id', '=', 're.reservation_id')
                ->whereIn('r.status', ['checked_in', 'ongoing'])
                ->where('re.is_returned', false)
                ->whereNull('r.deleted_at'),
            'r',
            $user,
        );

        $rows = (clone $base)
            ->orderByDesc('r.reservation_date')
            ->orderByDesc('r.end_time')
            ->limit(8)
            ->get([
                'r.reservation_code',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'r.status',
                'c.court_number',
                'l.name as location_name',
                'eup.first_name',
                'eup.last_name',
                'u.email',
                'u.photo_path',
                'col.checked_out_at',
                'col.total_additional_charges',
            ]);

        return [
            'cards' => [
                $this->card('Open returns', (string) (clone $openReturns)->sum('re.quantity'), 'Rental items still checked out.', 'fa-clipboard-list'),
                $this->card('Closed today', (string) (clone $base)->whereDate('col.checked_out_at', $today)->count(), 'Sessions completed by staff.', 'fa-check-double'),
                $this->card('Extra charges', $this->money((clone $base)->sum('col.total_additional_charges')), 'Damage and late charges collected.', 'fa-coins'),
            ],
            'rows' => $rows->map(function ($reservation) {
                $customer = trim(($reservation->first_name ?? '').' '.($reservation->last_name ?? '')) ?: $reservation->email;
                $owner = $this->userOwner($reservation->first_name, $reservation->last_name, $reservation->email, $reservation->photo_path);

                return $this->row(
                    $reservation->reservation_code,
                    $customer.' | '.$reservation->location_name.' Court '.$reservation->court_number.' | '.$reservation->reservation_date.' '.$this->timeRange($reservation->start_time, $reservation->end_time).' | extra '.$this->money($reservation->total_additional_charges ?? 0),
                    $reservation->checked_out_at ? 'Checked out' : 'Needs checkout',
                    $reservation->checked_out_at ? 100 : 65,
                    'I6-I9',
                    $reservation->checked_out_at ? 'success' : 'warning',
                    [$owner],
                );
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookCourtData(): array
    {
        self::releaseExpiredReservations();
        $today = now()->toDateString();
        $courts = DB::table('courts as c')
            ->join('locations as l', 'l.id', '=', 'c.location_id')
            ->where('c.is_active', true)
            ->where('l.is_active', true)
            ->whereNull('c.deleted_at')
            ->whereNull('l.deleted_at')
            ->orderBy('l.name')
            ->orderBy('c.display_order')
            ->get(['c.id', 'c.location_id', 'c.court_number', 'c.court_name', 'c.court_type', 'l.name as location_name']);

        return [
            'cards' => [
                $this->card('Open branches', (string) DB::table('locations')->where('is_active', true)->whereNull('deleted_at')->count(), 'Locations available for booking.', 'fa-map-marker-alt'),
                $this->card('Bookable courts', (string) $courts->count(), 'Active courts with pricing rules.', 'fa-calendar-day'),
                $this->card('Rentable items', (string) array_sum(array_column($this->rentableEquipment(), 'available')), 'Equipment available across branches.', 'fa-table-tennis'),
            ],
            'bookingLocations' => $this->activeLocations(),
            'bookingCourts' => $this->bookableCourts(),
            'bookingEquipment' => $this->rentableEquipment(),
            'publicSiteSettings' => $this->publicSiteSettingsValues(),
            'rows' => $courts->map(function ($court) use ($today) {
                $rate = DB::table('court_pricing_rules')->where('court_id', $court->id)->where('is_active', true)->orderBy('priority')->value('base_price');
                $booked = DB::table('reservations')->where('court_id', $court->id)->where('reservation_date', $today)->whereNull('deleted_at')->count();
                $blocked = DB::table('court_maintenance')
                    ->where('court_id', $court->id)
                    ->whereDate('start_datetime', '<=', $today)
                    ->whereDate('end_datetime', '>=', $today)
                    ->exists();

                return $this->row(
                    $court->location_name.' Court '.$court->court_number,
                    ($court->court_name ?: ucfirst($court->court_type).' court').' | '.$this->money($rate).'/hr | '.$booked.' booking(s) today',
                    $blocked ? 'Blocked today' : 'Available',
                    $blocked ? 35 : max(45, 100 - ($booked * 15)),
                    'E1-E10, N3-N5',
                    $blocked ? 'warning' : 'success',
                );
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptData(User $user): array
    {
        $base = $this->scopeByUser(
            DB::table('reservations as r')
                ->join('courts as c', 'c.id', '=', 'r.court_id')
                ->join('locations as l', 'l.id', '=', 'r.location_id')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
                ->leftJoin('payments as p', 'p.id', '=', DB::raw("(select pp.id from payments pp where pp.reservation_id = r.id order by (pp.status = 'verified') desc, pp.id desc limit 1)"))
                ->whereNull('r.deleted_at'),
            'r',
            $user,
        );

        $filter = request('filter');
        if ($filter) {
            if ($filter === 'today') {
                $base->where('r.reservation_date', '=', now()->toDateString());
            } elseif ($filter === 'yesterday') {
                $base->where('r.reservation_date', '=', now()->subDay()->toDateString());
            } elseif ($filter === 'last_week') {
                $base->whereBetween('r.reservation_date', [
                    now()->subDays(6)->toDateString(),
                    now()->toDateString(),
                ]);
            } elseif ($filter === 'month') {
                $base->whereBetween('r.reservation_date', [
                    now()->subDays(29)->toDateString(),
                    now()->toDateString(),
                ]);
            } elseif ($filter === 'last_month') {
                $base->whereBetween('r.reservation_date', [
                    now()->subMonth()->startOfMonth()->toDateString(),
                    now()->subMonth()->endOfMonth()->toDateString(),
                ]);
            } elseif ($filter === 'custom') {
                $startDate = request('start_date');
                $endDate = request('end_date');
                if ($startDate && $endDate) {
                    $base->whereBetween('r.reservation_date', [
                        $startDate,
                        $endDate,
                    ]);
                } elseif ($startDate) {
                    $base->where('r.reservation_date', '>=', $startDate);
                } elseif ($endDate) {
                    $base->where('r.reservation_date', '<=', $endDate);
                }
            }
        }

        $rows = (clone $base)
            ->orderBy('r.reservation_date', 'asc')
            ->orderBy('r.start_time', 'asc')
            ->limit(100)
            ->get([
                'r.id as reservation_id',
                'p.payment_reference',
                'p.amount',
                'p.payment_method',
                'p.gcash_reference_number',
                'p.status',
                'r.reservation_code',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'r.status as reservation_status',
                'r.grand_total',
                'c.court_number',
                'l.name as location_name',
                'eup.first_name',
                'eup.last_name',
                'u.email',
                'u.photo_path',
            ]);

        return [
            'filter' => $filter,
            'start_date' => request('start_date'),
            'end_date' => request('end_date'),
            'cards' => [
                $this->card('Paid bookings', (string) (clone $base)->where('p.status', 'verified')->count(), 'Confirmed bookings in history.', 'fa-check-circle'),
                $this->card('Paid total', $this->money((clone $base)->where('p.status', 'verified')->sum('p.amount')), 'Total verified payment amount.', 'fa-wallet'),
                $this->card('GCash refs', (string) (clone $base)->where('p.payment_method', 'gcash')->count(), 'Receipts with GCash reference numbers.', 'fa-mobile-alt'),
            ],
            'rows' => $rows->map(function ($payment) {
                $customer = trim(($payment->first_name ?? '').' '.($payment->last_name ?? '')) ?: $payment->email;
                $owner = $this->userOwner($payment->first_name, $payment->last_name, $payment->email, $payment->photo_path);
                $schedule = $this->formatReservationSchedule($payment->reservation_date, $payment->start_time, $payment->end_time);
                $status = $payment->status ?: $payment->reservation_status;
                $amount = $payment->amount ?? $payment->grand_total ?? 0;
                $reference = $payment->gcash_reference_number ?: ($payment->status ? 'cash' : '—');

                return $this->row(
                    $payment->payment_reference ?: $payment->reservation_code,
                    $customer.' | '.$payment->reservation_code.' | '.$payment->location_name.' Court '.$payment->court_number.' | '.$schedule.' | '.$this->money($amount).' | Ref '.$reference,
                    ucfirst($status),
                    $this->statusProgress($status),
                    'G1-G7, N6',
                    $this->statusColor($status),
                    [$owner],
                );
            })->all(),
            'receiptsList' => $rows->map(function ($payment) {
                $customer = trim(($payment->first_name ?? '').' '.($payment->last_name ?? '')) ?: $payment->email;
                $status = $payment->status ?: $payment->reservation_status;
                $amount = $payment->amount ?? $payment->grand_total ?? 0;

                return [
                    'reservation_id' => $payment->reservation_id,
                    'reservation_code' => $payment->reservation_code,
                    'payment_reference' => $payment->payment_reference ?: $payment->reservation_code,
                    'customer' => $customer,
                    'photo_url' => $this->profilePhotoUrl($payment->photo_path),
                    'court' => $payment->location_name.' Court '.$payment->court_number,
                    'schedule' => $this->formatReservationSchedule($payment->reservation_date, $payment->start_time, $payment->end_time),
                    'amount' => $this->money($amount),
                    'status' => ucfirst($status),
                    'color' => $this->statusColor($status),
                    'confirmable' => ! in_array(strtolower((string) $status), ['confirmed', 'checked_in', 'completed', 'ongoing', 'verified', 'refunded'], true),
                    'reference' => $payment->gcash_reference_number ?: ($payment->status ? 'cash' : '—'),
                ];
            })->all(),
        ];
    }

    private function formatReservationSchedule(string $date, string $start, string $end): string
    {
        $formattedDate = strtolower(date('F/d/Y', strtotime($date)));

        $start_ts = strtotime($start);
        $end_ts = strtotime($end);

        $start_min = date('i', $start_ts);
        $end_min = date('i', $end_ts);

        $start_fmt = $start_min === '00' ? date('g', $start_ts) : date('g:i', $start_ts);
        $start_am_pm = date('a', $start_ts);

        $end_fmt = $end_min === '00' ? date('g', $end_ts) : date('g:i', $end_ts);
        $end_am_pm = date('a', $end_ts);

        return $formattedDate.' '.$start_fmt.$start_am_pm.'-'.$end_fmt.$end_am_pm;
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewData(User $user): array
    {
        $base = $this->scopeByUser(
            DB::table('ratings as rt')
                ->join('reservations as r', 'r.id', '=', 'rt.reservation_id')
                ->join('users as u', 'u.id', '=', 'rt.user_id')
                ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
                ->join('courts as c', 'c.id', '=', 'rt.court_id')
                ->whereNull('rt.deleted_at')
                ->whereNull('r.deleted_at'),
            'r',
            $user,
        );

        $rows = (clone $base)
            ->orderByDesc('rt.created_at')
            ->limit(8)
            ->get([
                'rt.rating_score',
                'rt.review_title',
                'rt.review_comment',
                'rt.status',
                'rt.helpful_count',
                'rt.admin_response',
                'r.reservation_code',
                'c.court_number',
                'eup.first_name',
                'eup.last_name',
                'u.email',
                'u.photo_path',
            ]);

        return [
            'moderatableRatings' => $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])
                ? (clone $base)
                    ->orderByDesc('rt.created_at')
                    ->limit(20)
                    ->get([
                        'rt.id',
                        'rt.rating_score',
                        'rt.review_title',
                        'rt.review_comment',
                        'rt.admin_response',
                        'rt.status',
                        'r.reservation_code',
                        'c.court_number',
                        'eup.first_name',
                        'eup.last_name',
                        'u.email',
                    ])
                    ->map(fn ($rt) => [
                        'id' => (int) $rt->id,
                        'reservation_code' => $rt->reservation_code,
                        'court_number' => $rt->court_number,
                        'customer' => trim(($rt->first_name ?? '').' '.($rt->last_name ?? '')) ?: $rt->email,
                        'score' => (int) $rt->rating_score,
                        'title' => $rt->review_title,
                        'comment' => $rt->review_comment,
                        'admin_response' => $rt->admin_response,
                        'status' => $rt->status,
                    ])
                    ->all()
                : [],
            'cards' => [
                $this->card('Average rating', number_format((float) (clone $base)->avg('rt.rating_score'), 2), 'Verified customer review score.', 'fa-star-half-alt'),
                $this->card('Approved reviews', (string) (clone $base)->where('rt.status', 'approved')->count(), 'Reviews visible to admins.', 'fa-comment-alt'),
                $this->card('Pending moderation', (string) (clone $base)->where('rt.status', 'pending')->count(), 'Reviews still waiting for action.', 'fa-user-shield'),
            ],
            'rows' => $rows->map(function ($rating) {
                $customer = trim(($rating->first_name ?? '').' '.($rating->last_name ?? '')) ?: $rating->email;
                $owner = $this->userOwner($rating->first_name, $rating->last_name, $rating->email, $rating->photo_path);

                return $this->row(
                    $rating->review_title ?: $rating->reservation_code,
                    $customer.' | Court '.$rating->court_number.' | '.$rating->rating_score.'/5 | '.$rating->helpful_count.' helpful | '.($rating->admin_response ? 'admin responded' : 'no response yet'),
                    ucfirst($rating->status),
                    $this->percent($rating->rating_score, 5),
                    'K1-K10',
                    $this->statusColor($rating->status),
                    [$owner],
                );
            })->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        if (! $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            return [
                'cards' => [
                    $this->card('Restricted', '0', 'You do not have permission to view this module.', 'fa-lock'),
                ],
                'rows' => [],
            ];
        }

        $users = DB::table('users as u')
            ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
            ->leftJoin('staff_profiles as sp', 'sp.user_id', '=', 'u.id')
            ->leftJoin('admin_profiles as ap', 'ap.user_id', '=', 'u.id')
            ->leftJoin('locations as l', 'l.id', '=', 'sp.assigned_location_id')
            ->leftJoin('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'u.id')
                    ->where('mhr.model_type', '=', User::class);
            })
            ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
            ->whereNull('u.deleted_at')
            ->orderBy('r.name')
            ->orderBy('u.email')
            ->get([
                'u.id', 'u.email', 'u.mobile_number', 'u.is_active', 'u.locked_until',
                'u.last_login_at', 'u.email_verified_at', 'u.photo_path', 'u.created_at',
                'r.name as role',
                DB::raw('COALESCE(eup.first_name, sp.first_name, ap.first_name) as first_name'),
                DB::raw('COALESCE(eup.last_name, sp.last_name, ap.last_name) as last_name'),
                'sp.employee_id', 'sp.position', 'sp.assigned_location_id',
                'sp.can_confirm_payments', 'sp.can_process_refunds', 'sp.can_manage_inventory',
                'ap.admin_level',
                'l.name as location_name',
            ]);

        $manageable = $users->map(function ($u) {
            $name = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: $u->email;
            $isLocked = $u->locked_until && Carbon::parse($u->locked_until)->isFuture();

            return [
                'id' => (int) $u->id,
                'email' => $u->email,
                'mobile_number' => $u->mobile_number,
                'role' => $u->role ?: User::ROLE_END_USER,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'is_active' => (bool) $u->is_active,
                'is_locked' => $isLocked,
                'last_login_at' => $u->last_login_at,
                'employee_id' => $u->employee_id,
                'position' => $u->position,
                'assigned_location_id' => $u->assigned_location_id ? (int) $u->assigned_location_id : null,
                'location_name' => $u->location_name,
                'admin_level' => $u->admin_level,
                'can_confirm_payments' => (bool) ($u->can_confirm_payments ?? false),
                'can_process_refunds' => (bool) ($u->can_process_refunds ?? false),
                'can_manage_inventory' => (bool) ($u->can_manage_inventory ?? false),
                'display_name' => $name,
            ];
        })->values()->all();

        $byRole = collect($manageable)->groupBy('role');
        $totalActive = collect($manageable)->where('is_active', true)->count();
        $locked = collect($manageable)->where('is_locked', true)->count();

        $isSuperAdmin = $user->hasRole(User::ROLE_SUPER_ADMIN);

        return [
            'isSuperAdmin' => $isSuperAdmin,
            'manageableUsers' => $manageable,
            'manageableLocations' => $this->activeLocations(),
            'cards' => [
                $this->card('Active accounts', (string) $totalActive, 'Users currently allowed to log in.', 'fa-user-check'),
                $this->card('Staff & admins', (string) (($byRole->get(User::ROLE_ADMIN, collect())->count())
                    + ($byRole->get(User::ROLE_SUPER_ADMIN, collect())->count())
                    + ($byRole->get(User::ROLE_LOCATION_MANAGER, collect())->count())
                    + ($byRole->get(User::ROLE_STAFF, collect())->count())), 'Operational users across roles.', 'fa-id-badge'),
                $this->card('Locked accounts', (string) $locked, 'Locked due to repeated failed logins.', 'fa-user-lock'),
            ],
            'rows' => collect($manageable)->take(8)->map(fn ($u) => $this->row(
                $u['display_name'],
                $u['email'].' | '.$u['mobile_number'].' | '.str_replace('_', ' ', $u['role']).
                    ($u['location_name'] ? ' | '.$u['location_name'] : '').
                    ($u['last_login_at'] ? ' | last login '.Carbon::parse($u['last_login_at'])->diffForHumans() : ''),
                $u['is_locked'] ? 'Locked' : ($u['is_active'] ? 'Active' : 'Disabled'),
                $u['is_active'] ? 100 : 30,
                'A1-A7, L5',
                $u['is_locked'] ? 'warning' : ($u['is_active'] ? 'success' : 'secondary'),
                [[
                    'name' => $u['display_name'],
                    'photo_url' => asset('images/branding.png'),
                ]],
            ))->all(),
        ];
    }

    /**
     * @return array{title: string, value: string, text: string, icon: string}
     */
    private function card(string $title, string $value, string $text, string $icon): array
    {
        return compact('title', 'value', 'text', 'icon');
    }

    /**
     * @param  array<int, array{name: string, photo_url: string}>  $owners
     * @return array{feature: string, note: string, status: string, progress: int, objective: string, color: string, owners: array<int, array{name: string, photo_url: string}>}
     */
    private function row(string $feature, string $note, string $status, int $progress, string $objective, string $color = 'secondary', array $owners = []): array
    {
        return [
            'feature' => $feature,
            'note' => $note,
            'status' => $status,
            'progress' => max(0, min(100, $progress)),
            'objective' => $objective,
            'color' => $color,
            'owners' => $owners,
        ];
    }

    /**
     * @return array{name: string, photo_url: string}
     */
    private function userOwner(?string $firstName, ?string $lastName, ?string $email, ?string $photoPath): array
    {
        $name = trim(($firstName ?? '').' '.($lastName ?? '')) ?: ($email ?: 'Account user');

        return [
            'name' => $name,
            'photo_url' => $this->profilePhotoUrl($photoPath),
        ];
    }

    private function profilePhotoUrl(?string $photoPath): string
    {
        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            return Storage::url($photoPath);
        }

        return asset('images/branding.png');
    }

    /**
     * @return array<int, object>
     */
    private function activeLocations(): array
    {
        return DB::table('locations')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province', 'latitude', 'longitude'])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function activeLocationIds(): array
    {
        $ids = collect($this->activeLocations())->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $ids ?: [0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bookableCourts(): array
    {
        $today = now()->toDateString();

        return DB::table('courts as c')
            ->join('locations as l', 'l.id', '=', 'c.location_id')
            ->where('c.is_active', true)
            ->where('l.is_active', true)
            ->whereNull('c.deleted_at')
            ->whereNull('l.deleted_at')
            ->orderBy('l.name')
            ->orderBy('c.display_order')
            ->get([
                'c.id',
                'c.location_id',
                'c.court_number',
                'c.court_name',
                'c.court_type',
                'c.surface_type',
                'l.name as location_name',
            ])
            ->map(fn ($court) => [
                'id' => $court->id,
                'location_id' => $court->location_id,
                'label' => $court->location_name.' - Court '.$court->court_number.' ('.($court->court_name ?: ucfirst($court->court_type).' court').')',
                'rate' => $this->courtRate($court->id, $today, '08:00:00'),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rentableEquipment(): array
    {
        return DB::table('equipment_types as et')
            ->leftJoin('equipment_inventory as ei', 'ei.equipment_type_id', '=', 'et.id')
            ->leftJoin('locations as l', 'l.id', '=', 'ei.location_id')
            ->where('et.is_available_for_rent', true)
            ->whereNull('et.deleted_at')
            ->where(function ($query) {
                $query->whereNull('l.id')
                    ->orWhere(function ($branch) {
                        $branch->where('l.is_active', true)->whereNull('l.deleted_at');
                    });
            })
            ->groupBy('et.id', 'et.name', 'et.description', 'et.rental_price_per_unit', 'et.deposit_amount', 'et.max_rental_quantity_per_booking', 'et.display_order')
            ->orderBy('et.display_order')
            ->get([
                'et.id',
                'et.name',
                'et.description',
                'et.rental_price_per_unit',
                'et.deposit_amount',
                'et.max_rental_quantity_per_booking',
                DB::raw('COALESCE(SUM(ei.total_quantity - COALESCE(ei.damaged_quantity, 0) - COALESCE(ei.lost_quantity, 0) - COALESCE(ei.under_maintenance_quantity, 0)), 0) as available_quantity'),
            ])
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => (float) $item->rental_price_per_unit,
                'deposit' => (float) $item->deposit_amount,
                'max' => (int) $item->max_rental_quantity_per_booking,
                'available' => (int) $item->available_quantity,
            ])
            ->all();
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function hoursBetween(string $date, string $start, string $end): float
    {
        $startAt = strtotime($date.' '.$start);
        $endAt = strtotime($date.' '.$end);

        if ($endAt <= $startAt) {
            $endAt = strtotime($date.' '.$end.' +1 day');
        }

        return round(($endAt - $startAt) / 3600, 2);
    }

    private function reservationCode(): string
    {
        do {
            $code = 'PBJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (DB::table('reservations')->where('reservation_code', $code)->exists());

        return $code;
    }

    private function courtRate(int $courtId, string $date, string $startTime): float
    {
        $day = (int) date('w', strtotime($date));
        $rule = DB::table('court_pricing_rules')
            ->where('court_id', $courtId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->where(function ($query) use ($day) {
                $query->whereNull('day_of_week')->orWhere('day_of_week', $day);
            })
            ->where(function ($query) use ($startTime) {
                $query->whereNull('start_time')->orWhere('start_time', '<=', $startTime);
            })
            ->where(function ($query) use ($startTime) {
                $query->whereNull('end_time')->orWhere('end_time', '>=', $startTime);
            })
            ->orderBy('priority')
            ->first();

        if (! $rule) {
            return 600.0;
        }

        return round((float) $rule->base_price * (1 + ((float) $rule->peak_surcharge_percentage / 100)), 2);
    }

    private function courtIsOpen(int $courtId, string $date, string $startTime, string $endTime): bool
    {
        if (!$this->isWithinOperatingHours($startTime, $endTime)) {
            return false;
        }

        $day = (int) date('w', strtotime($date));
        $schedule = DB::table('court_schedules')
            ->where('court_id', $courtId)
            ->where('day_of_week', $day)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        if ($schedule) {
            if (!$schedule->is_available) {
                return false;
            }
            if ($schedule->break_start_time && $schedule->break_end_time) {
                $isLunchBreak = ($schedule->break_start_time === '12:00:00' && $schedule->break_end_time === '12:30:00');
                if (!$isLunchBreak || filter_var(SystemSetting::value('enable_lunch_break', true), FILTER_VALIDATE_BOOLEAN)) {
                    return ! ($startTime < $schedule->break_end_time && $endTime > $schedule->break_start_time);
                }
            }
        }

        return true;
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

    public static function releaseExpiredReservations()
    {
        $threshold = now();
        $reservations = Reservation::query()
            ->whereIn('status', ['pending_payment', 'payment_verification'])
            ->where('payment_status', 'unpaid')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $threshold)
            ->get();

        if ($reservations->isEmpty()) {
            return;
        }

        $system = User::query()->role(User::ROLE_SUPER_ADMIN)->first();
        $inventory = app(\App\Services\InventoryService::class);
        $audit = app(\App\Services\AuditService::class);
        $notifications = app(\App\Services\NotificationService::class);

        foreach ($reservations as $reservation) {
            $reservation->update([
                'status' => 'cancelled',
                'is_active' => false,
            ]);

            if ($system) {
                $inventory->restoreReservedFor($reservation, $system, 'Auto-released after hold expired.');
                $audit->log('reservation.expired', 'reservations', $reservation->id, $system, [
                    'reservation_code' => $reservation->reservation_code,
                    'expires_at' => $reservation->expires_at,
                ]);
            } else {
                $fallback = User::query()->role(User::ROLE_ADMIN)->first();
                if ($fallback) {
                    $inventory->restoreReservedFor($reservation, $fallback, 'Auto-released after hold expired.');
                }
            }

            $notifications->notify(
                $reservation->user_id,
                'reservation',
                'Reservation '.$reservation->reservation_code.' expired',
                'Your unpaid booking was released. Book again any time.',
                $reservation,
            );
        }
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

    private function getPriceBreakdown(int $courtId, string $date, string $startTime, string $endTime): array
    {
        $hours = $this->hoursBetween($date, $startTime, $endTime);
        $startSecs = strtotime($date.' '.$startTime);
        
        $breakdown = [];
        $total = 0.0;
        for ($i = 0; $i < $hours; $i++) {
            $slotStart = date('H:i:00', $startSecs + ($i * 3600));
            $slotRate = $this->getSlotPrice($courtId, $date, $slotStart);
            $label = date('g A', $startSecs + ($i * 3600));
            $breakdown[] = [
                'label' => $label,
                'rate' => $slotRate,
            ];
            $total += $slotRate;
        }
        return [
            'items' => $breakdown,
            'total' => $total,
        ];
    }

    private function courtIsBooked(int $courtId, string $date, string $startTime, string $endTime): bool
    {
        $end = $endTime === '00:00:00' ? '24:00:00' : $endTime;

        return DB::table('reservations')
            ->where('court_id', $courtId)
            ->where('reservation_date', $date)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['cancelled', 'no_show', 'refunded'])
            ->where('start_time', '<', $end)
            ->whereRaw("(CASE WHEN end_time = '00:00:00' THEN '24:00:00' ELSE end_time END) > ?", [$startTime])
            ->exists();
    }

    private function courtIsUnderMaintenance(int $courtId, string $date, string $startTime, string $endTime): bool
    {
        $endDateTime = $endTime === '00:00:00'
            ? date('Y-m-d 00:00:00', strtotime($date.' +1 day'))
            : $date.' '.$endTime;

        return DB::table('court_maintenance')
            ->where('court_id', $courtId)
            ->where('start_datetime', '<', $endDateTime)
            ->where('end_datetime', '>', $date.' '.$startTime)
            ->exists();
    }

    private function scopeByUser($query, string $reservationAlias, User $user)
    {
        $query->whereIn($reservationAlias.'.location_id', $this->activeLocationIds());

        if ($this->canSeeAll($user)) {
            return $query;
        }

        if ($user->hasAnyRole(['location_manager', 'staff'])) {
            $locationId = DB::table('staff_profiles')->where('user_id', $user->id)->value('assigned_location_id');

            if ($locationId) {
                return $query->where($reservationAlias.'.location_id', $locationId);
            }
        }

        return $query->where($reservationAlias.'.user_id', $user->id);
    }

    private function reviewablePaymentQuery(User $user)
    {
        $query = DB::table('payments as p')
            ->join('reservations as r', 'r.id', '=', 'p.reservation_id')
            ->where('p.status', 'pending')
            ->where('p.payment_method', 'gcash')
            ->whereIn('r.location_id', $this->activeLocationIds())
            ->whereNull('r.deleted_at');

        if (! $this->canSeeAll($user)) {
            $locationId = DB::table('staff_profiles')->where('user_id', $user->id)->value('assigned_location_id');

            if ($locationId) {
                $query->where('r.location_id', $locationId);
            }
        }

        return $query;
    }

    private function canReviewPayments(User $user): bool
    {
        if ($this->canSeeAll($user)) {
            return true;
        }

        if (! $user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF])) {
            return false;
        }

        return DB::table('staff_profiles')
            ->where('user_id', $user->id)
            ->where('can_confirm_payments', true)
            ->exists();
    }

    private function canSeeAll(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }

    private function canManageOperations(User $user): bool
    {
        return $this->canSeeAll($user) || $user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]);
    }

    private function ownerGcashNumber(): string
    {
        return DB::table('system_settings')->where('setting_key', 'owner_gcash_number')->value('setting_value') ?: '09123456789';
    }

    private function settingHour(string $key, int $default): int
    {
        $value = (string) SystemSetting::value($key, sprintf('%02d:00', $default));

        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $default;
        }

        return (int) substr($value, 0, 2);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function audit(string $action, string $entityType, ?int $entityId, User $user, array $values): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => null,
            'new_values' => json_encode($values),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'response_status' => 200,
            'execution_time_ms' => null,
            'created_at' => now(),
        ]);
    }

    private function money(int|float|null $amount): string
    {
        return 'PHP '.number_format((float) $amount, 2);
    }

    private function percent(int|float $value, int|float $total): int
    {
        return (int) round(($value / max(1, $total)) * 100);
    }

    private function timeRange(string $start, string $end): string
    {
        return date('H:i', strtotime($start)).' - '.date('H:i', strtotime($end));
    }

    private function statusProgress(string $status): int
    {
        return match ($status) {
            'verified', 'confirmed', 'checked_in', 'ongoing', 'approved' => 90,
            'completed', 'refunded' => 100,
            'pending', 'pending_payment', 'payment_verification', 'pending_verification' => 45,
            'cancelled', 'no_show', 'rejected', 'hidden' => 20,
            default => 65,
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'verified', 'confirmed', 'checked_in', 'completed', 'approved' => 'success',
            'pending', 'pending_payment', 'payment_verification', 'pending_verification' => 'warning',
            'refunded' => 'info',
            'cancelled', 'no_show', 'rejected', 'hidden' => 'danger',
            default => 'secondary',
        };
    }

    private function reservationStatus(string $status, string $paymentStatus): string
    {
        return match (true) {
            $status === 'payment_verification' => 'Payment verification',
            $status === 'pending_payment' => 'Pending payment',
            $status === 'checked_in' => 'Checked in',
            $paymentStatus === 'paid' && $status === 'confirmed' => 'Confirmed and paid',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
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

    private function courtHasOpenPlay(int $locationId, string $date, string $startTime, string $endTime): bool
    {
        $openPlay = DB::table('open_play_events')
            ->where('location_id', $locationId)
            ->whereDate('event_date', $date)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (! $openPlay) {
            return false;
        }

        $opStart = $openPlay->start_time;
        $opEnd = $openPlay->end_time;

        return $startTime < $opEnd && $endTime > $opStart;
    }
}
