<?php

namespace App\Services;

use App\Models\EquipmentInventory;
use App\Models\InventoryTransaction;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Restore reserved inventory when a reservation is cancelled or
     * payment is rejected. Idempotent against already-returned items.
     */
    public function restoreReservedFor(Reservation $reservation, User $performer, string $note): void
    {
        $reservation->loadMissing('equipment');

        DB::transaction(function () use ($reservation, $performer, $note) {
            foreach ($reservation->equipment as $line) {
                if ($line->is_returned) {
                    continue;
                }

                $inventory = EquipmentInventory::query()
                    ->where('location_id', $reservation->location_id)
                    ->where('equipment_type_id', $line->equipment_type_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory) {
                    continue;
                }

                $previousAvailable = (int) $inventory->available_quantity;
                $previousReserved = (int) $inventory->reserved_quantity;
                $quantity = (int) $line->quantity;

                $damaged = (int) ($inventory->damaged_quantity ?? 0);
                $lost = (int) ($inventory->lost_quantity ?? 0);
                $maintenance = (int) ($inventory->under_maintenance_quantity ?? 0);
                $physicalStock = max(0, (int) $inventory->total_quantity - $damaged - $lost - $maintenance);

                $newAvailable = max(0, min($physicalStock, $previousAvailable + $quantity));
                $newReserved = max(0, min($physicalStock, $previousReserved - $quantity));

                $inventory->update([
                    'available_quantity' => $newAvailable,
                    'reserved_quantity' => $newReserved,
                ]);

                // I11 fix: use 'reservation_released' for cancellations/expiry, not 'check_in'
                InventoryTransaction::query()->create([
                    'location_id' => $reservation->location_id,
                    'equipment_type_id' => $line->equipment_type_id,
                    'reservation_id' => $reservation->id,
                    'transaction_type' => 'reservation_released',
                    'quantity' => $quantity,
                    'previous_available' => $previousAvailable,
                    'new_available' => $newAvailable,
                    'notes' => $note,
                    'performed_by' => $performer->id,
                    'created_at' => now(),
                ]);

                $line->update(['is_returned' => true, 'returned_at' => now()]);
            }
        });
    }
}
