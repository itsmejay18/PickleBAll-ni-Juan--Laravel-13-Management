<?php

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

function pbjSeedWalkInScenario(): array
{
    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Pickle Ballan ni Juan',
        'slug' => 'pickle-ballan-ni-juan-walkin',
        'branch_code' => 'PBJ-WI',
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
        'open_time' => '05:00:00',
        'close_time' => '23:59:00',
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

    return [$locationId, $courtId];
}

test('staff can create unpaid walk-in booking and then mark it as paid', function () {
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_SUPER_ADMIN);

    [$locationId, $courtId] = pbjSeedWalkInScenario();

    $payload = [
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '09:00',
        'customer_first_name' => 'Juan',
        'customer_last_name' => 'Dela Cruz',
        'customer_mobile' => '09123456789',
        'create_account' => false,
    ];

    // 1. Create Unpaid Walk-in
    $response = $this->actingAs($staff)->post('/walk-ins', $payload);
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('modules.show', 'walk-ins'));

    $reservation = Reservation::where('court_id', $courtId)->first();
    expect($reservation)->not->toBeNull();
    expect($reservation->status)->toBe('pending_payment');
    expect($reservation->payment_status)->toBe('unpaid');
    expect($reservation->expires_at)->toBeNull(); // Walk-ins don't expire automatically

    // 2. Mark as Paid (Cash)
    $payResponse = $this->actingAs($staff)->post(route('walk-ins.mark-paid', $reservation->id), [
        'payment_method' => 'cash',
        'cash_received' => 600.0,
    ]);
    $payResponse->assertSessionHasNoErrors();
    $payResponse->assertRedirect(route('modules.show', 'walk-ins'));

    $reservation->refresh();
    expect($reservation->status)->toBe('confirmed');
    expect($reservation->payment_status)->toBe('paid');
});

test('staff can create immediate paid cash walk-in booking', function () {
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_SUPER_ADMIN);

    [$locationId, $courtId] = pbjSeedWalkInScenario();

    $payload = [
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'customer_first_name' => 'Pedro',
        'customer_last_name' => 'Penduko',
        'customer_mobile' => '09123456789',
        'create_account' => false,
        'payment_method' => 'cash',
        'cash_received' => 600.0,
    ];

    $response = $this->actingAs($staff)->post('/walk-ins', $payload);
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('modules.show', 'walk-ins'));

    $reservation = Reservation::where('court_id', $courtId)->where('start_time', '10:00:00')->first();
    expect($reservation)->not->toBeNull();
    expect($reservation->status)->toBe('confirmed');
    expect($reservation->payment_status)->toBe('paid');
});

test('staff can create walk-in booking with equipment rentals and updates inventory', function () {
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_SUPER_ADMIN);

    [$locationId, $courtId] = pbjSeedWalkInScenario();

    // Seed equipment type and inventory
    $equipmentTypeId = DB::table('equipment_types')->insertGetId([
        'name' => 'Premium Paddle',
        'slug' => 'premium-paddle-test',
        'rental_price_per_unit' => 100.0,
        'deposit_amount' => 50.0,
        'requires_deposit' => true,
        'max_rental_quantity_per_booking' => 4,
        'is_available_for_rent' => true,
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
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payload = [
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '14:00',
        'end_time' => '15:00',
        'customer_first_name' => 'Test',
        'customer_last_name' => 'User',
        'customer_mobile' => '09123456789',
        'create_account' => false,
        'payment_method' => 'cash',
        'cash_received' => 800.0, // 600 court + 2 * 100 equipment = 800
        'equipment' => [
            $equipmentTypeId => 2,
        ]
    ];

    $response = $this->actingAs($staff)->post('/walk-ins', $payload);
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('modules.show', 'walk-ins'));

    $reservation = Reservation::where('court_id', $courtId)->where('start_time', '14:00:00')->first();
    expect($reservation)->not->toBeNull();
    expect($reservation->court_subtotal)->toBe(600.0);
    expect($reservation->equipment_total)->toBe(200.0);
    expect($reservation->grand_total)->toBe(800.0);
    expect($reservation->status)->toBe('confirmed');
    expect($reservation->payment_status)->toBe('paid');

    // Verify reservation_equipment row was created
    $hasEquipment = DB::table('reservation_equipment')
        ->where('reservation_id', $reservation->id)
        ->where('equipment_type_id', $equipmentTypeId)
        ->where('quantity', 2)
        ->exists();
    expect($hasEquipment)->toBeTrue();

    // Verify inventory was updated
    $inv = DB::table('equipment_inventory')
        ->where('location_id', $locationId)
        ->where('equipment_type_id', $equipmentTypeId)
        ->first();
    expect((int)$inv->available_quantity)->toBe(8);
    expect((int)$inv->reserved_quantity)->toBe(2);
});

