<?php

use App\Models\Reservation;
use App\Models\User;
use App\Http\Controllers\ModulePageController;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

function pbjSeedTimeoutScenario(): array
{
    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Pickle Ballan ni Juan',
        'slug' => 'pickle-ballan-ni-juan-timeout',
        'branch_code' => 'PBJ-TO',
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

test('reservation expires and cancels after 3 minutes if unpaid', function () {
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    [$locationId, $courtId] = pbjSeedTimeoutScenario();

    $user = User::factory()->create();

    $payload = [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '09:00',
    ];

    $response = $this->actingAs($user)->post('/bookings', $payload);
    $response->assertSessionHasNoErrors();

    $reservation = Reservation::where('user_id', $user->id)->first();
    expect($reservation)->not->toBeNull();
    expect($reservation->status)->toBe('pending_payment');
    expect($reservation->payment_status)->toBe('unpaid');
    expect($reservation->expires_at)->not->toBeNull();

    // Force expiration back in time
    $reservation->expires_at = now()->subMinutes(1);
    $reservation->save();

    // Trigger releaseExpiredReservations
    ModulePageController::releaseExpiredReservations();

    $reservation->refresh();
    expect($reservation->status)->toBe('cancelled');
    expect($reservation->is_active)->toBeFalsy();
});

test('verified paid reservation does not expire', function () {
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    [$locationId, $courtId] = pbjSeedTimeoutScenario();

    $user = User::factory()->create();

    $payload = [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ];

    $response = $this->actingAs($user)->post('/bookings', $payload);
    $response->assertSessionHasNoErrors();

    $reservation = Reservation::where('user_id', $user->id)->where('start_time', '09:00:00')->first();
    expect($reservation)->not->toBeNull();

    // Mark as paid/confirmed
    $reservation->status = 'confirmed';
    $reservation->payment_status = 'paid';
    $reservation->expires_at = now()->subMinutes(1);
    $reservation->save();

    // Trigger releaseExpiredReservations
    ModulePageController::releaseExpiredReservations();

    $reservation->refresh();
    expect($reservation->status)->toBe('confirmed');
    expect($reservation->payment_status)->toBe('paid');
});

test('booking hold timeout is dynamic and active pending bookings limit rejects subsequent requests', function () {
    Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    [$locationId, $courtId] = pbjSeedTimeoutScenario();

    // 1. Change dynamic timeout to 12 minutes, and limit to 1
    \App\Models\SystemSetting::set('booking_timeout_minutes', '12');
    \App\Models\SystemSetting::set('max_pending_bookings_limit', '1');

    $user = User::factory()->create();

    $payload1 = [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '09:00',
    ];

    // First booking succeeds
    $response = $this->actingAs($user)->post('/bookings', $payload1);
    $response->assertSessionHasNoErrors();

    $reservation1 = Reservation::where('user_id', $user->id)->first();
    expect($reservation1)->not->toBeNull();
    // Expiration should be exactly 12 minutes from now
    $diffMinutes = round(now()->diffInMinutes($reservation1->expires_at));
    expect($diffMinutes)->toBe(12.0);

    // 2. Second booking should fail due to spam prevention limit (1 pending max)
    $payload2 = [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ];

    $response2 = $this->actingAs($user)->post('/bookings', $payload2);
    $response2->assertSessionHas('error', function ($msg) {
        return str_contains($msg, 'maximum limit');
    });

    // 3. Staff/Admin should bypass limit
    Role::findOrCreate(User::ROLE_STAFF, 'web');
    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_STAFF);

    // Give the staff member a pending booking
    $this->actingAs($staff)->post('/bookings', $payload1)->assertSessionHasNoErrors();
    // Try to book a second one - should succeed since staff has no limit restrictions
    $this->actingAs($staff)->post('/bookings', $payload2)->assertSessionHasNoErrors();
});
