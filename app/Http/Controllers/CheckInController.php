<?php

namespace App\Http\Controllers;

use App\Models\CheckInLog;
use App\Models\Reservation;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {
    }

    public function store(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeStaff($user, $reservation);

        if ($reservation->payment_status !== 'paid') {
            return back()->with('error', 'Payment must be confirmed before check-in.');
        }

        if (! in_array($reservation->status, ['confirmed', 'pending_payment', 'payment_verification'], true)) {
            return back()->with('error', 'Reservation cannot be checked in (status: '.$reservation->status.').');
        }

        if ($reservation->checkInLog()->exists()) {
            return back()->with('error', 'This reservation is already checked in.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        CheckInLog::query()->create([
            'reservation_id' => $reservation->id,
            'checked_in_by' => $user->id,
            'checked_in_at' => now(),
            'check_in_method' => 'staff_search',
            'actual_start_time' => now()->format('H:i:s'),
            'customer_show_proof' => true,
            'verified_via' => 'digital_receipt',
            'equipment_released' => $reservation->equipment->map(fn ($e) => [
                'equipment_type_id' => $e->equipment_type_id,
                'quantity' => $e->quantity,
            ])->all(),
            'notes' => $validated['notes'] ?? null,
            'created_at' => now(),
        ]);

        $reservation->update(['status' => 'checked_in']);

        $this->audit->log('reservation.checked_in', 'reservations', $reservation->id, $user, [
            'reservation_code' => $reservation->reservation_code,
        ]);

        $this->notifications->notify(
            $reservation->user_id,
            'reservation',
            'Checked in: '.$reservation->reservation_code,
            'Welcome to '.$reservation->location->name.'. Enjoy your session.',
            $reservation,
        );

        return back()->with('status', 'Reservation '.$reservation->reservation_code.' checked in.');
    }

    private function authorizeStaff(User $user, Reservation $reservation): void
    {
        if ($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            return;
        }

        abort_unless($user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]), 403);

        $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
        abort_if($locationId && (int) $reservation->location_id !== (int) $locationId, 403);
    }
}
