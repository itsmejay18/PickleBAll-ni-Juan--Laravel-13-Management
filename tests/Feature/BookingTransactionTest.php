<?php

use App\Models\Reservation;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

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
    $reservation = DB::table('reservations')->where('user_id', $user->id)->first();
    expect($reservation)->not->toBeNull();
    $response->assertRedirect(route('bookings.pay', $reservation->reservation_code));

    // Follow redirect to payment page
    $payResponse = $this->actingAs($user)->get(route('bookings.pay', $reservation->reservation_code));
    $payResponse->assertStatus(200);

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

test('equipment is available for booking at different times or other days, but blocked for overlapping times', function () {
    \App\Models\SystemSetting::set('max_pending_bookings_limit', 5);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Test Location',
        'slug' => 'test-location',
        'branch_code' => 'TL01',
        'address_line1' => '123 Test St',
        'city' => 'Manila',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => 'B',
        'court_name' => 'Court B',
        'court_type' => 'outdoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create a second court at the same location to allow concurrent bookings (so we can test overlapping equipment)
    $court2Id = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => 'C',
        'court_name' => 'Court C',
        'court_type' => 'outdoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('court_schedules')->insert([
        [
            'court_id' => $courtId,
            'day_of_week' => (int) now()->addDay()->format('w'),
            'open_time' => '06:00:00',
            'close_time' => '22:00:00',
            'is_available' => true,
            'effective_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'court_id' => $court2Id,
            'day_of_week' => (int) now()->addDay()->format('w'),
            'open_time' => '06:00:00',
            'close_time' => '22:00:00',
            'is_available' => true,
            'effective_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DB::table('court_pricing_rules')->insert([
        [
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
        ],
        [
            'court_id' => $court2Id,
            'rule_name' => 'Standard Rate',
            'base_price' => 600,
            'minimum_hours' => 1,
            'maximum_hours' => 4,
            'priority' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $equipmentTypeId = DB::table('equipment_types')->insertGetId([
        'name' => 'Test Paddle',
        'slug' => 'test-paddle',
        'rental_price_per_unit' => 100,
        'deposit_amount' => 0,
        'is_available_for_rent' => true,
        'requires_deposit' => false,
        'max_rental_quantity_per_booking' => 10,
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
        'reorder_point' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $bookingDate = now()->addDay()->toDateString();

    // 1. First user books today 8-9am, renting 8 paddles. This succeeds.
    $response1 = $this->actingAs($user1)->post('/bookings', [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => $bookingDate,
        'start_time' => '08:00',
        'end_time' => '09:00',
        'equipment' => [
            $equipmentTypeId => 8,
        ],
    ]);
    $response1->assertSessionHasNoErrors();

    // 2. Second user books today 9-10am (different slot), renting 8 paddles.
    // Under static availability, this would fail (since available_quantity in DB is 2).
    // Under slot-based, it should succeed!
    $response2 = $this->actingAs($user2)->post('/bookings', [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => $bookingDate,
        'start_time' => '09:00',
        'end_time' => '10:00',
        'equipment' => [
            $equipmentTypeId => 8,
        ],
    ]);
    $response2->assertSessionHasNoErrors();

    // 3. Third booking attempts to rent 4 paddles at the SAME time as user1 (8-9am) on court 2.
    // Since 8 paddles are already in use, and total stock is 10, renting 4 should FAIL (only 2 left).
    $response3 = $this->actingAs($user2)->post('/bookings', [
        'location_id' => $locationId,
        'court_id' => $court2Id,
        'reservation_date' => $bookingDate,
        'start_time' => '08:00',
        'end_time' => '09:00',
        'equipment' => [
            $equipmentTypeId => 4,
        ],
    ]);
    $response3->assertSessionHas('error');
    expect(session('error'))->toContain('only has 2 available for the selected slot');
});

test('inventory service clamps available and reserved quantity to prevent unsigned database errors', function () {
    $user = User::factory()->create();
    $performer = User::factory()->create();
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $performer->assignRole(User::ROLE_SUPER_ADMIN);

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Test Clamping Location',
        'slug' => 'test-clamping-location',
        'branch_code' => 'TCL01',
        'address_line1' => '123 Test St',
        'city' => 'Manila',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => 'D',
        'court_name' => 'Court D',
        'court_type' => 'outdoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $equipmentTypeId = DB::table('equipment_types')->insertGetId([
        'name' => 'Clamping Paddle',
        'slug' => 'clamping-paddle',
        'rental_price_per_unit' => 100,
        'deposit_amount' => 0,
        'is_available_for_rent' => true,
        'requires_deposit' => false,
        'max_rental_quantity_per_booking' => 10,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Setup inventory with physical stock = 10, but available = 10, reserved = 0
    $inventoryId = DB::table('equipment_inventory')->insertGetId([
        'location_id' => $locationId,
        'equipment_type_id' => $equipmentTypeId,
        'total_quantity' => 10,
        'available_quantity' => 10,
        'reserved_quantity' => 0,
        'damaged_quantity' => 0,
        'lost_quantity' => 0,
        'under_maintenance_quantity' => 0,
        'reorder_point' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservationId = DB::table('reservations')->insertGetId([
        'reservation_code' => 'TESTCLAMP123',
        'user_id' => $user->id,
        'court_id' => $courtId,
        'location_id' => $locationId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'court_price_per_hour' => 600,
        'court_subtotal' => 600,
        'equipment_total' => 200,
        'grand_total' => 800,
        'reservation_type' => 'online',
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('reservation_equipment')->insert([
        'reservation_id' => $reservationId,
        'equipment_type_id' => $equipmentTypeId,
        'quantity' => 2,
        'price_per_unit' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservation = Reservation::find($reservationId);

    // Call restoreReservedFor when reserved_quantity is 0 (should be clamped to 0 rather than underflowing unsigned)
    $service = new InventoryService;
    $service->restoreReservedFor($reservation, $performer, 'Test restore clamping');

    $inventory = DB::table('equipment_inventory')->where('id', $inventoryId)->first();
    expect($inventory->available_quantity)->toBe(10); // clamped to total_quantity
    expect($inventory->reserved_quantity)->toBe(0); // clamped to 0
});
