<?php

namespace App\Console\Commands;

use App\Models\EquipmentInventory;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyLowStock extends Command
{
    protected $signature = 'inventory:notify-low-stock';

    protected $description = 'Push admin/staff notifications when equipment inventory at any branch falls at or below the reorder point.';

    public function handle(NotificationService $notifications): int
    {
        $items = EquipmentInventory::query()
            ->with(['equipmentType', 'location'])
            ->whereColumn('available_quantity', '<=', 'reorder_point')
            ->get();

        $count = 0;
        foreach ($items as $item) {
            if (! $item->location || ! $item->equipmentType) {
                continue;
            }

            $subject = 'Low stock: '.$item->equipmentType->name.' at '.$item->location->name;
            $message = 'Available '.$item->available_quantity.' / reorder point '.$item->reorder_point.'.';

            $notifications->notifyAdmins($subject, $message, null, [
                'equipment_type_id' => $item->equipment_type_id,
                'location_id' => $item->location_id,
            ]);
            $notifications->notifyLocationStaff($item->location_id, $subject, $message, null, [
                'equipment_type_id' => $item->equipment_type_id,
            ]);

            $count++;
        }

        $this->info("Low-stock notifications dispatched for {$count} item(s).");

        return self::SUCCESS;
    }
}
