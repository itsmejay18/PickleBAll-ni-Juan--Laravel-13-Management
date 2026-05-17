<?php

namespace App\Http\Controllers;

use App\Models\CheckOutLog;
use App\Models\Reservation;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckOutController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly InventoryService $inventory,
        private readonly NotificationService $notifications,
    ) {
    }

    public function store(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeStaff($user, $reservation);

        if (! in_array($reservation->status, ['checked_in', 'ongoing'], true)) {
            return back()->with('error', 'Only checked-in or ongoing reservations can be checked out.');
        }

        if ($reservation->checkOutLog()->exists()) {
            return back()->with('error', 'This reservation is already checked out.');
        }

        $validated = $request->validate([
            'damage_charges' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'late_charges' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'feedback_from_customer' => ['nullable', 'string', 'max:1000'],
            'customer_paid_additional' => ['nullable', 'boolean'],
        ]);

        $damage = (float) ($validated['damage_charges'] ?? 0);
        $late = (float) ($validated['late_charges'] ?? 0);
        $extra = round($damage + $late, 2);

        DB::transaction(function () use ($reservation, $user, $validated, $damage, $late, $extra) {
            CheckOutLog::query()->create([
                'reservation_id' => $reservation->id,
                'checked_out_by' => $user->id,
                'checked_out_at' => now(),
                'actual_end_time' => now()->format('H:i:s'),
                'equipment_returned' => $reservation->equipment->map(fn ($e) => [
                    'equipment_type_id' => $e->equipment_type_id,
                    'quantity' => $e->quantity,
                ])->all(),
                'damage_charges' => $damage,
                'late_charges' => $late,
                'total_additional_charges' => $extra,
                'customer_paid_additional' => $request->boolean('customer_paid_additional'),
                'payment_collected_by' => $extra > 0 && $request->boolean('customer_paid_additional') ? $user->id : null,
                'feedback_from_customer' => $validated['feedback_from_customer'] ?? null,
                'rating_reminder_sent' => false,
                'created_at' => now(),
            ]);

            $reservation->update([
                'status' => 'completed',
            ]);

            // Restore inventory: actual restore happens when items returned
            $this->inventory->restoreReservedFor($reservation, $user, 'Equipment returned at check-out.');

            $this->audit->log('reservation.checked_out', 'reservations', $reservation->id, $user, [
                'reservation_code' => $reservation->reservation_code,
                'damage_charges' => $damage,
                'late_charges' => $late,
            ]);
        });

        $this->notifications->notify(
            $reservation->user_id,
            'reservation',
            'Session completed: '.$reservation->reservation_code,
            'Thanks for playing. Tap your booking to leave a rating.',
            $reservation,
            ['extra_charges' => $extra],
        );

        return back()->with('status', 'Session '.$reservation->reservation_code.' closed. Extra charges: PHP '.number_format($extra, 2).'.');
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
