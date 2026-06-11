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

        // C5 fix: do not fall back to first user — skip audit/inventory if no super admin
        $system = User::query()->role(User::ROLE_SUPER_ADMIN)->first();

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
            } else {
                // Still restore inventory even without an audit actor
                $fallback = User::query()->role(User::ROLE_ADMIN)->first();
                if ($fallback) {
                    $inventory->restoreReservedFor($reservation, $fallback, 'Auto-released after hold expired.');
                }
                $this->warn('No super_admin found — audit skipped for '.$reservation->reservation_code);
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
