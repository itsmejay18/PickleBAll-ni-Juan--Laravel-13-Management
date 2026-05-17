<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('customer can create a court booking with equipment rental', function () {
    $user = User::factory()->create();

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Pickle Ballan ni Juan',
        'slug' => 'pickle-ballan-ni-juan',
        'branch_code' => 'PBJ01',
        'address_line1' => 'Main court yard',
        'city' => 'Digos City',
        'province' => 'Davao del Sur',
        'country' => 'Philippines',
        'latitude' => 6.77025,
        'longitude' => 125.2115287,
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => 'A',
        'court_name' => 'Court A',
        'court_type' => 'outdoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('court_schedules')->insert([
        'court_id' => $courtId,
        'day_of_week' => (int) now()->addDay()->format('w'),
        'open_time' => '06:00:00',
        'close_time' => '22:00:00',
        'is_available' => true,
        'effective_from' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('court_pricing_rules')->insert([
        'court_id' => $courtId,
        'rule_name' => 'Standard Rate',
        'base_price' => 600,
        'minimum_hours' => 1,
        'maximum_hours' => 4,
        'priority' => 1,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $equipmentTypeId = DB::table('equipment_types')->insertGetId([
        'name' => 'Pickleball Paddle Rental',
        'slug' => 'pickleball-paddle-rental',
        'rental_price_per_unit' => 120,
        'deposit_amount' => 300,
        'late_fee_per_hour' => 20,
        'is_available_for_rent' => true,
        'requires_deposit' => true,
        'max_rental_quantity_per_booking' => 4,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('equipment_inventory')->insert([
        'location_id' => $locationId,
        'equipment_type_id' => $equipmentTypeId,
        'total_quantity' => 10,
        'available_quantity' => 10,
        'reserved_quantity' => 0,
        'damaged_quantity' => 0,
        'lost_quantity' => 0,
        'under_maintenance_quantity' => 0,
        'reorder_point' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)->post('/bookings', [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '10:00',
        'equipment' => [
            $equipmentTypeId => 2,
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('modules.show', 'payments'));

    $reservation = DB::table('reservations')->where('user_id', $user->id)->first();

    expect($reservation)->not->toBeNull();
    expect((float) $reservation->court_subtotal)->toBe(1200.0);
    expect((float) $reservation->equipment_total)->toBe(240.0);
    expect((float) $reservation->grand_total)->toBe(1440.0);

    $this->assertDatabaseHas('reservation_equipment', [
        'reservation_id' => $reservation->id,
        'equipment_type_id' => $equipmentTypeId,
        'quantity' => 2,
    ]);

    $this->assertDatabaseHas('equipment_inventory', [
        'location_id' => $locationId,
        'equipment_type_id' => $equipmentTypeId,
        'available_quantity' => 8,
        'reserved_quantity' => 2,
    ]);
});
