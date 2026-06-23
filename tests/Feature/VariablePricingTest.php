<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

function pbjSeedPricingScenario(): array
{
    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Pickle Ballan ni Juan',
        'slug' => 'pickle-ballan-ni-juan-pricing',
        'branch_code' => 'PBJ-PRC',
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

    // 1. Default Standard Rule (Null times)
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

    // 2. Custom Peak Rate Rule (17:00 to 21:00) with 25% surcharge
    DB::table('court_pricing_rules')->insert([
        'court_id' => $courtId,
        'rule_name' => 'Evening Peak',
        'day_of_week' => (int) now()->addDay()->format('w'),
        'start_time' => '17:00:00',
        'end_time' => '21:00:00',
        'base_price' => 600,
        'peak_surcharge_percentage' => 25.00,
        'minimum_hours' => 1,
        'maximum_hours' => 4,
        'priority' => 2,
        'is_active' => true,
        'effective_from' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$locationId, $courtId];
}

test('custom rate window pricing rules match slot times correctly', function () {
    [$locationId, $courtId] = pbjSeedPricingScenario();

    $user = User::factory()->create();

    // 1. Calculate price for standard time (14:00 - 15:00, 1 hour)
    // Should be standard rate = 600
    $response = $this->actingAs($user)->postJson('/bookings/calculate-price', [
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '14:00',
        'duration' => 1,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'total' => 600,
        'items' => [
            ['label' => '2 PM', 'rate' => 600]
        ]
    ]);

    // 2. Calculate price for peak time (18:00 - 19:00, 1 hour)
    // Should be 600 * 1.25 = 750
    $responsePeak = $this->actingAs($user)->postJson('/bookings/calculate-price', [
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '18:00',
        'duration' => 1,
    ]);

    $responsePeak->assertStatus(200);
    $responsePeak->assertJson([
        'total' => 750,
        'items' => [
            ['label' => '6 PM', 'rate' => 750]
        ]
    ]);
});

test('multi-hour bookings correctly sum slot-by-slot rates across windows', function () {
    [$locationId, $courtId] = pbjSeedPricingScenario();

    $user = User::factory()->create();

    // Calculate price from 16:00 to 18:00 (2 hours)
    // Slot 1: 16:00-17:00 (standard rate = 600)
    // Slot 2: 17:00-18:00 (peak rate = 750)
    // Total should be 1350
    $response = $this->actingAs($user)->postJson('/bookings/calculate-price', [
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '16:00',
        'duration' => 2,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'total' => 1350,
        'items' => [
            ['label' => '4 PM', 'rate' => 600],
            ['label' => '5 PM', 'rate' => 750],
        ]
    ]);

    // Now proceed with booking store for these slots
    $bookingResponse = $this->actingAs($user)->post('/bookings', [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '16:00',
        'end_time' => '18:00',
    ]);

    $bookingResponse->assertSessionHasNoErrors();
    $reservation = DB::table('reservations')->where('user_id', $user->id)->first();
    expect($reservation)->not->toBeNull();
    expect((float) $reservation->court_subtotal)->toBe(1350.0);
    expect((float) $reservation->grand_total)->toBe(1350.0);
});
