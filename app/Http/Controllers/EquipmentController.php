<?php

namespace App\Http\Controllers;

use App\Models\EquipmentInventory;
use App\Models\EquipmentType;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function update(Request $request, EquipmentInventory $inventory): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'rental_price_per_unit' => ['required', 'numeric', 'min:0', 'max:99999'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'reorder_point' => ['required', 'integer', 'min:0', 'max:10000'],
            'available_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'lost_quantity' => ['nullable', 'integer', 'min:0'],
            'is_available_for_rent' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($inventory, $validated, $request) {
            $previousAvailable = (int) $inventory->available_quantity;
            $newAvailable = (int) $validated['available_quantity'];
            $delta = $newAvailable - $previousAvailable;

            $type = $inventory->equipmentType;
            $type?->update([
                'rental_price_per_unit' => $validated['rental_price_per_unit'],
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'requires_deposit' => ($validated['deposit_amount'] ?? 0) > 0,
                'is_available_for_rent' => $request->boolean('is_available_for_rent', true),
            ]);

            $newTotal = $newAvailable
                + (int) $inventory->reserved_quantity
                + (int) ($validated['damaged_quantity'] ?? $inventory->damaged_quantity)
                + (int) ($validated['lost_quantity'] ?? $inventory->lost_quantity)
                + (int) $inventory->under_maintenance_quantity;

            $inventory->update([
                'available_quantity' => $newAvailable,
                'damaged_quantity' => $validated['damaged_quantity'] ?? $inventory->damaged_quantity,
                'lost_quantity' => $validated['lost_quantity'] ?? $inventory->lost_quantity,
                'reorder_point' => $validated['reorder_point'],
                'total_quantity' => $newTotal,
                'last_inventory_count_at' => now(),
                'last_inventory_count_by' => $request->user()->id,
            ]);

            if ($delta !== 0) {
                InventoryTransaction::query()->create([
                    'location_id' => $inventory->location_id,
                    'equipment_type_id' => $inventory->equipment_type_id,
                    'reservation_id' => null,
                    'transaction_type' => 'count_adjustment',
                    'quantity' => $delta,
                    'previous_available' => $previousAvailable,
                    'new_available' => $newAvailable,
                    'notes' => 'Manual inventory edit.',
                    'performed_by' => $request->user()->id,
                    'created_at' => now(),
                ]);
            }
        });

        $this->audit->log('equipment.updated', 'equipment_inventory', $inventory->id, $request->user(), $validated);

        return back()->with('status', 'Equipment stock updated.');
    }

    public function destroy(Request $request, EquipmentInventory $inventory): RedirectResponse
    {
        $this->authorizeManage($request);

        if ((int) $inventory->reserved_quantity > 0) {
            return back()->with('error', 'There are still reserved units for this item. Wait until reservations clear.');
        }

        $inventory->delete();

        $this->audit->log('equipment.removed', 'equipment_inventory', $inventory->id, $request->user(), [
            'equipment_type_id' => $inventory->equipment_type_id,
            'location_id' => $inventory->location_id,
        ]);

        return back()->with('status', 'Equipment stock entry removed.');
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        $hasStaffPermission = DB::table('staff_profiles')
            ->where('user_id', $user->id)
            ->where('can_manage_inventory', true)
            ->exists();

        abort_unless(
            $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]) || $hasStaffPermission,
            403,
        );
    }
}
