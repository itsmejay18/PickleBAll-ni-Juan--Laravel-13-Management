<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ExpirePendingReservations extends Command
{
    protected $signature = 'reservations:expire-pending';

    protected $description = 'Auto-cancel pending-payment reservations that exceeded their hold window and restore inventory.';

    public function handle(InventoryService $inventory, AuditService $audit, NotificationService $notifications): int
    {
        $threshold = now();

        $reservations = Reservation::query()
            ->whereIn('status', ['pending_payment', 'payment_verification'])
            ->where('payment_status', 'unpaid')
            ->where(function ($q) use ($threshold) {
                $q->whereNotNull('expires_at')
                    ->where('expires_at', '<=', $threshold);
            })
            ->get();

        $system = User::query()->role(User::ROLE_SUPER_ADMIN)->first() ?? User::query()->first();

        $count = 0;
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
            }

            $notifications->notify(
                $reservation->user_id,
                'reservation',
                'Reservation '.$reservation->reservation_code.' expired',
                'Your unpaid booking was released. Book again any time.',
                $reservation,
            );

            $count++;
        }

        $this->info("Expired {$count} pending reservation(s).");

        return self::SUCCESS;
    }
}
