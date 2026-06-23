<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

test('public user can check live court availability and booked/maintenance slots are blocked', function () {
    $user = User::factory()->create();

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Public Test Hub',
        'slug' => 'public-test-hub',
        'branch_code' => 'PTH01',
        'address_line1' => 'Public Court Way',
        'city' => 'Taguig',
        'province' => 'Metro Manila',
        'country' => 'Philippines',
        'latitude' => 14.5,
        'longitude' => 121.0,
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '1',
        'court_name' => 'Court 1',
        'court_type' => 'indoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $testDate = now()->addDay()->toDateString();
    $dayOfWeek = (int) date('w', strtotime($testDate));

    DB::table('court_schedules')->insert([
        'court_id' => $courtId,
        'day_of_week' => $dayOfWeek,
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

    // 1. Check initially as a guest (guest should be able to query court availability)
    $response = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'date',
        'location_id',
        'courts' => [
            '*' => [
                'court_id',
                'court_name',
                'court_number',
                'slots' => [
                    '*' => [
                        'start',
                        'end',
                        'label',
                        'available',
                        'reason',
                    ],
                ],
            ],
        ],
    ]);

    // Check that slots are available initially
    $json = $response->json();
    $slots = $json['courts'][0]['slots'];

    // Find the 08:00 slot
    $slot08 = collect($slots)->firstWhere('start', '08:00');
    expect($slot08['available'])->toBeTrue();
    expect($slot08['reason'])->toBe('Open');

    // 2. Book the court for 08:00 - 09:00
    DB::table('reservations')->insert([
        'reservation_code' => 'PBJ-TEST-12345',
        'user_id' => $user->id,
        'court_id' => $courtId,
        'location_id' => $locationId,
        'reservation_date' => $testDate,
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'court_price_per_hour' => 600.0,
        'court_subtotal' => 600.0,
        'grand_total' => 600.0,
        'reservation_type' => 'online',
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Query availability again
    $response = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");
    $response->assertStatus(200);
    $json = $response->json();
    $slots = $json['courts'][0]['slots'];

    // 08:00 slot should now be booked
    $slot08 = collect($slots)->firstWhere('start', '08:00');
    expect($slot08['available'])->toBeFalse();
    expect($slot08['reason'])->toBe('Booked');

    // 10:00 slot should still be open
    $slot10 = collect($slots)->firstWhere('start', '10:00');
    expect($slot10['available'])->toBeTrue();

    // 3. Put court under maintenance for 10:00 - 11:00
    DB::table('court_maintenance')->insert([
        'court_id' => $courtId,
        'title' => 'Routine Cleanup',
        'maintenance_type' => 'regular',
        'start_datetime' => "{$testDate} 10:00:00",
        'end_datetime' => "{$testDate} 11:00:00",
        'is_all_day' => false,
        'recurring_weekly' => false,
        'created_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Query availability again
    $response = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");
    $response->assertStatus(200);
    $json = $response->json();
    $slots = $json['courts'][0]['slots'];

    // 10:00 slot should now be under maintenance
    $slot10 = collect($slots)->firstWhere('start', '10:00');
    expect($slot10['available'])->toBeFalse();
    expect($slot10['reason'])->toBe('Maintenance');
});

test('welcome page returns status 200', function () {
    // Seed a location so that route() generation inside the locations list loop is executed
    DB::table('locations')->insert([
        'name' => 'BGC Smash Hub',
        'slug' => 'bgc-smash-hub',
        'branch_code' => 'BGC01',
        'address_line1' => '2F Rally Building, 9th Avenue',
        'city' => 'Taguig',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/');
    $response->assertStatus(200);
});

test('operating hours edge cases generate slots correctly', function () {
    $admin = User::factory()->create();
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Edge Case Hub',
        'slug' => 'edge-case-hub',
        'branch_code' => 'ECH01',
        'address_line1' => 'Edge Case Way',
        'city' => 'Taguig',
        'province' => 'Metro Manila',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '1',
        'court_name' => 'Court 1',
        'court_type' => 'indoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $testDate = now()->addDay()->toDateString();
    $dayOfWeek = (int) date('w', strtotime($testDate));

    DB::table('court_schedules')->insert([
        'court_id' => $courtId,
        'day_of_week' => $dayOfWeek,
        'open_time' => '00:00:00',
        'close_time' => '23:59:00',
        'is_available' => true,
        'effective_from' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 1. Edge Case: Open From 12:00 AM (00:00) to 12:00 PM (12:00)
    $this->actingAs($admin)->post('/admin/settings/public-site', [
        'public_playing_open_time' => '12:00 AM',
        'public_playing_close_time' => '12:00 PM',
    ])->assertRedirect();

    $response = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");
    $response->assertStatus(200);
    $slots = $response->json()['courts'][0]['slots'];
    expect(collect($slots)->last()['end'])->toBe('12:00');
    expect(collect($slots)->first()['start'])->toBe('00:00');

    // 2. Edge Case: Open From 12:00 PM (12:00) to 12:00 AM (00:00 / midnight)
    $this->actingAs($admin)->post('/admin/settings/public-site', [
        'public_playing_open_time' => '12:00 PM',
        'public_playing_close_time' => '12:00 AM',
    ])->assertRedirect();

    $response = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");
    $response->assertStatus(200);
    $slots = $response->json()['courts'][0]['slots'];
    expect(collect($slots)->first()['start'])->toBe('12:00');
    expect(collect($slots)->last()['end'])->toBe('00:00');

    // 3. Edge Case: Crossing Midnight (Open until 1:00 AM next day)
    $this->actingAs($admin)->post('/admin/settings/public-site', [
        'public_playing_open_time' => '05:00',
        'public_playing_close_time' => '01:00 AM',
    ])->assertRedirect();

    $response = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");
    $response->assertStatus(200);
    $slots = $response->json()['courts'][0]['slots'];
    expect(collect($slots)->first()['start'])->toBe('05:00');
    expect(collect($slots)->last()['end'])->toBe('01:00');
});

test('toggling lunch break setting enables/disables noon slot availability', function () {
    $admin = User::factory()->create();
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Break Toggle Hub',
        'slug' => 'break-toggle-hub',
        'branch_code' => 'BTH-TOG',
        'address_line1' => 'Break Toggle Way',
        'city' => 'Taguig',
        'province' => 'Metro Manila',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '1',
        'court_name' => 'Court 1',
        'court_type' => 'indoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $testDate = now()->addDay()->toDateString();
    $dayOfWeek = (int) date('w', strtotime($testDate));

    DB::table('court_schedules')->insert([
        'court_id' => $courtId,
        'day_of_week' => $dayOfWeek,
        'open_time' => '05:00:00',
        'close_time' => '22:00:00',
        'break_start_time' => '12:00:00',
        'break_end_time' => '12:30:00',
        'is_available' => true,
        'effective_from' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 1. By default, lunch break is enabled (so 12 PM slot is Closed)
    $this->actingAs($admin)->post('/admin/settings/public-site', [
        'public_playing_open_time' => '05:00',
        'public_playing_close_time' => '22:00',
        'enable_lunch_break' => 'true',
    ])->assertRedirect();

    $response = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");
    $response->assertStatus(200);
    $slotsEnabled = $response->json()['courts'][0]['slots'];
    $slot12 = collect($slotsEnabled)->firstWhere('start', '12:00');
    expect($slot12['available'])->toBeFalse();
    expect($slot12['reason'])->toBe('Closed');

    // 2. Disable lunch break (12 PM slot should become Open)
    $this->actingAs($admin)->post('/admin/settings/public-site', [
        'public_playing_open_time' => '05:00',
        'public_playing_close_time' => '22:00',
    ])->assertRedirect();

    $responseDisabled = $this->getJson("/court-availability?location_id={$locationId}&date={$testDate}");
    $responseDisabled->assertStatus(200);
    $slotsDisabled = $responseDisabled->json()['courts'][0]['slots'];
    $slot12Disabled = collect($slotsDisabled)->firstWhere('start', '12:00');
    expect($slot12Disabled['available'])->toBeTrue();
    expect($slot12Disabled['reason'])->toBe('Open');
});
