<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class MarkNoShowReservations extends Command
{
    protected $signature = 'reservations:mark-no-shows';

    protected $description = 'Flag confirmed reservations whose end time has passed without a check-in.';

    public function handle(AuditService $audit, InventoryService $inventory, NotificationService $notifications): int
    {
        $today = now()->toDateString();

        // W10 fix: exclude payment_verification — only target confirmed+paid reservations
        $candidates = Reservation::query()
            ->whereNull('deleted_at')
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->whereDoesntHave('checkInLog')
            ->where(function ($query) use ($today) {
                $query->where('reservation_date', '<', $today)
                    ->orWhere(function ($q) use ($today) {
                        $q->where('reservation_date', $today)
                            ->where('end_time', '<=', now()->format('H:i:s'));
                    });
            })
            ->get();

        // C5 fix: do not fall back to first user — skip audit if no super admin
        $system = User::query()->role(User::ROLE_SUPER_ADMIN)->first();
        $count = 0;

        foreach ($candidates as $reservation) {
            $reservation->update(['status' => 'no_show', 'is_active' => false]);

            // W8 fix: restore reserved equipment inventory on no-show
            if ($system) {
                $inventory->restoreReservedFor($reservation, $system, 'Equipment released after no-show.');

                $audit->log('reservation.no_show', 'reservations', $reservation->id, $system, [
                    'reservation_code' => $reservation->reservation_code,
                ]);
            }

            $notifications->notify(
                $reservation->user_id,
                'reservation',
                'No-show recorded for '.$reservation->reservation_code,
                'You missed your booking window. Repeated no-shows may affect future bookings.',
                $reservation,
            );

            $count++;
        }

        $this->info("Marked {$count} reservation(s) as no-show.");

        return self::SUCCESS;
    }
}
