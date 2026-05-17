<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

function pbjSeedCourtScenario(): array
{
    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Pickle Ballan ni Juan',
        'slug' => 'pickle-ballan-ni-juan-conflict',
        'branch_code' => 'PBJ-CFT',
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

    return [$locationId, $courtId];
}

test('a second booking for the same slot is rejected with a conflict error', function () {
    [$locationId, $courtId] = pbjSeedCourtScenario();

    $a = User::factory()->create();
    $b = User::factory()->create();

    $payload = [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '10:00',
    ];

    $first = $this->actingAs($a)->post('/bookings', $payload);
    $first->assertSessionHasNoErrors();

    expect(DB::table('reservations')->count())->toBe(1);

    $second = $this->actingAs($b)->from('/modules/book-court')->post('/bookings', $payload);
    $second->assertRedirect();

    // The pre-transaction check (courtIsBooked) catches this and returns the friendlier
    // "already has a booking" flash. Either error message is acceptable - both prevent
    // the second insert.
    expect(DB::table('reservations')->count())->toBe(1);
    expect(session('error'))->toContain('booking');
});
