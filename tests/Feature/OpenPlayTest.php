<?php

use App\Models\Court;
use App\Models\OpenPlayEvent;
use App\Models\OpenPlayMatch;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlayRound;
use App\Models\OpenPlaySetting;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'staff', 'location_manager', 'end_user'] as $r) {
        Role::findOrCreate($r, 'web');
    }
});

function setupOpenPlayScenario(): array
{
    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Digos Court Yard',
        'slug' => 'digos-court-yard',
        'branch_code' => 'DCY',
        'address_line1' => 'Main street',
        'city' => 'Digos City',
        'province' => 'Davao del Sur',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $court1Id = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '1',
        'court_name' => 'Court 1',
        'court_type' => 'outdoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $court2Id = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '2',
        'court_name' => 'Court 2',
        'court_type' => 'outdoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('court_schedules')->insert([
        [
            'court_id' => $court1Id,
            'day_of_week' => 5,
            'open_time' => '07:00:00',
            'close_time' => '23:59:00',
            'is_available' => true,
            'effective_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'court_id' => $court2Id,
            'day_of_week' => 5,
            'open_time' => '07:00:00',
            'close_time' => '23:59:00',
            'is_available' => true,
            'effective_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ]);

    DB::table('court_pricing_rules')->insert([
        [
            'court_id' => $court1Id,
            'rule_name' => 'Standard',
            'base_price' => 200,
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
            'rule_name' => 'Standard',
            'base_price' => 200,
            'minimum_hours' => 1,
            'maximum_hours' => 4,
            'priority' => 1,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ]);

    // Force Open Play Setting
    $settings = OpenPlaySetting::current();
    $settings->update([
        'is_enabled' => true,
        'day_of_week' => 5,
        'open_play_start' => '18:00:00',
        'open_play_end' => '22:00:00',
        'attendance_closing_time' => '17:55:00',
        'location_id' => $locationId,
        'entrance_fee' => 150.00,
        'max_slots' => 20,
        'rounds' => 3,
    ]);

    // Fetch the event (upcoming event will lazily create or fetch it based on settings)
    $event = OpenPlayEvent::upcoming($settings);

    return [$locationId, $court1Id, $court2Id, $event, $settings];
}

test('cannot book normal reservation during open play', function () {
    [$locationId, $courtId] = setupOpenPlayScenario();

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    // Attempting to book during Open Play (18:00 - 22:00)
    $payload = [
        'location_id' => $locationId,
        'court_id' => $courtId,
        'reservation_date' => OpenPlayEvent::nextOccurrence(5)->toDateString(),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ];

    $response = $this->actingAs($user)->from('/modules/book-court')->post('/bookings', $payload);
    $response->assertRedirect();
    expect(session('error'))->toContain('Open Play');
});

test('user registration and self check-in', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    // Make entrance fee 0.00 so registration is instant without payment flow
    $settings->update(['entrance_fee' => 0.00]);
    $event->update(['entrance_fee' => 0.00]);
    $event->refresh();

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    // 1. Join Open Play
    $response = $this->actingAs($user)->post(route('open-play.join', $event->id));
    $response->assertRedirect();

    $reg = OpenPlayRegistration::where('open_play_event_id', $event->id)->where('user_id', $user->id)->first();
    expect($reg)->not->toBeNull();
    expect($reg->status)->toBe('registered');

    // 2. Self Check-In
    $response = $this->actingAs($user)->post(route('open-play.self-checkin', $event->id));
    $response->assertRedirect();
    $reg->refresh();
    expect($reg->status)->toBe('checked_in');
});

test('matchmaking selects only checked in players and does fair rotation queue', function () {
    [$locationId, $court1Id, $court2Id, $event] = setupOpenPlayScenario();

    // Create 10 users. 9 are checked in, 1 is not checked in.
    $users = User::factory()->count(10)->create();
    foreach ($users as $i => $u) {
        $u->assignRole(User::ROLE_END_USER);
        $u->update(['rating' => 1000 + ($i * 10)]); // 1000, 1010, ..., 1090
        
        $reg = OpenPlayRegistration::create([
            'open_play_event_id' => $event->id,
            'user_id' => $u->id,
            'slot_number' => $i + 1,
            'status' => $i < 9 ? 'checked_in' : 'registered', // 9 checked in, 1 registered
            'checked_in_at' => $i < 9 ? now()->subMinutes(10 - $i) : null,
            'amount_paid' => 150.00,
            'registered_at' => now(),
        ]);
    }

    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_STAFF);

    // Generate Round 1
    $response = $this->actingAs($staff)->post(route('open-play.matches.generate', $event->id));
    $response->assertRedirect();

    // Capacity check: 2 courts. Each court holds 4 players. Total capacity = 8.
    // 9 are checked in, so 8 should play and 1 should wait.
    // User #9 (who is registered but not checked in) must not play under any circumstances!
    $round1 = OpenPlayRound::where('open_play_event_id', $event->id)->where('round_number', 1)->first();
    expect($round1)->not->toBeNull();

    $playingIds = [];
    foreach ($round1->matches as $m) {
        $playingIds = array_merge($playingIds, $m->playerIds());
    }

    expect(count($playingIds))->toBe(8);
    // User 9 (index 9) is not checked_in and should not be in playingIds
    expect($playingIds)->not->toContain($users[9]->id);

    // Waiting queue check: there should be 1 checked-in user who is waiting.
    // Who checked in last among the 9? User at index 8 (checked_in_at closest to now).
    // Let's verify that User at index 8 is not in playingIds.
    expect($playingIds)->not->toContain($users[8]->id);

    // Complete Round 1 matches
    foreach ($round1->matches as $match) {
        $this->actingAs($staff)->post(route('open-play.submit-result', $match->id), [
            'winner_team' => 1,
        ]);
    }

    // Dynamic round 2 generation:
    // Once all matches of round 1 are submitted, round 2 must be generated automatically.
    $round2 = OpenPlayRound::where('open_play_event_id', $event->id)->where('round_number', 2)->first();
    expect($round2)->not->toBeNull();

    // Check waiting queue rotation in Round 2:
    // User 8 (who waited in Round 1) must play in Round 2 (avoid consecutive wait times).
    $round2PlayingIds = [];
    foreach ($round2->matches as $m) {
        $round2PlayingIds = array_merge($round2PlayingIds, $m->playerIds());
    }
    expect($round2PlayingIds)->toContain($users[8]->id);
});

test('rating updates after match result submission', function () {
    [$locationId, $court1Id, $court2Id, $event] = setupOpenPlayScenario();

    $players = User::factory()->count(4)->create();
    foreach ($players as $i => $p) {
        $p->assignRole(User::ROLE_END_USER);
        $p->update(['rating' => 1000]);

        OpenPlayRegistration::create([
            'open_play_event_id' => $event->id,
            'user_id' => $p->id,
            'slot_number' => $i + 1,
            'status' => 'checked_in',
            'amount_paid' => 150.00,
        ]);
    }

    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_STAFF);

    $this->actingAs($staff)->post(route('open-play.matches.generate', $event->id));

    $match = OpenPlayMatch::where('open_play_event_id', $event->id)->first();
    expect($match)->not->toBeNull();

    // Record Team 1 Win
    $response = $this->actingAs($staff)->post(route('open-play.submit-result', $match->id), [
        'winner_team' => 1,
    ]);
    $response->assertSessionHasNoErrors();

    // Team 1 players should get +15 rating, Team 2 players -15 rating
    expect($match->team1Player1->fresh()->rating)->toBe(1015);
    expect($match->team1Player2->fresh()->rating)->toBe(1015);
    expect($match->team2Player1->fresh()->rating)->toBe(985);
    expect($match->team2Player2->fresh()->rating)->toBe(985);

    // Overwrite: change winner to Team 2
    $response = $this->actingAs($staff)->post(route('open-play.submit-result', $match->id), [
        'winner_team' => 2,
    ]);
    $response->assertSessionHasNoErrors();

    // Team 1 players should now be -15 rating from original (985), Team 2 players +15 from original (1015)
    expect($match->team1Player1->fresh()->rating)->toBe(985);
    expect($match->team1Player2->fresh()->rating)->toBe(985);
    expect($match->team2Player1->fresh()->rating)->toBe(1015);
    expect($match->team2Player2->fresh()->rating)->toBe(1015);
});

test('joining an event with entrance fee results in pending_payment status and redirects to pay route', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $settings->update(['entrance_fee' => 150.00]);
    $event->update(['entrance_fee' => 150.00]);

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    $response = $this->actingAs($user)->post(route('open-play.join', $event->id));
    
    $registration = OpenPlayRegistration::where('open_play_event_id', $event->id)
        ->where('user_id', $user->id)
        ->first();

    expect($registration)->not->toBeNull();
    expect($registration->status)->toBe('pending_payment');
    
    $response->assertRedirect(route('open-play.pay', $registration->id));
});

test('pay registration route redirects to XPayLink payment URL when configured', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $settings->update(['entrance_fee' => 150.00]);
    $event->update(['entrance_fee' => 150.00]);

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    // Create a pending registration
    $registration = OpenPlayRegistration::create([
        'open_play_event_id' => $event->id,
        'user_id' => $user->id,
        'slot_number' => 1,
        'status' => 'pending_payment',
        'amount_paid' => 0,
        'registered_at' => now(),
    ]);

    // Set XPayLink config
    SystemSetting::set('xpaylink_enabled', 'true');
    SystemSetting::set('xpaylink_public_key', 'pk_test_123');
    SystemSetting::set('xpaylink_secret_key', 'sk_test_123');

    // Mock API
    Http::fake([
        'https://synthwave.space/api/create-session.php' => Http::response([
            'success' => true,
            'session_id' => 'PS-MOCK-OPREG-123',
            'payment_url' => 'https://synthwave.space/pay.php?sid=PS-MOCK-OPREG-123',
        ], 200),
    ]);

    $response = $this->actingAs($user)->get(route('open-play.pay', $registration->id));
    $response->assertRedirect('https://synthwave.space/pay.php?sid=PS-MOCK-OPREG-123');
});

test('XPayLink webhook callback with OPREG prefix updates registration to registered and records paid amount', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $settings->update(['entrance_fee' => 150.00]);
    $event->update(['entrance_fee' => 150.00]);

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    // Create a pending registration
    $registration = OpenPlayRegistration::create([
        'open_play_event_id' => $event->id,
        'user_id' => $user->id,
        'slot_number' => 1,
        'status' => 'pending_payment',
        'amount_paid' => 0,
        'registered_at' => now(),
    ]);

    SystemSetting::set('xpaylink_secret_key', 'sk_test_webhook_secret');

    // Build signed payload
    $payload = [
        'event' => 'payment.paid',
        'status' => 'paid',
        'session_id' => 'PS-XPAYLINK-OPREG-123',
        'external_bill_id' => 'OPREG-' . $registration->id,
        'customer_name' => 'Test User',
        'customer_paid' => '150.00',
        'merchant_amount' => '150.00',
        'paylink_admin_fee' => '0.00',
        'amount' => '150.00',
    ];

    $rawJson = json_encode($payload);
    $signature = hash_hmac('sha256', $rawJson, 'sk_test_webhook_secret');

    $response = $this->postJson(route('payments.xpaylink.webhook'), $payload, [
        'X-PayLink-Signature' => $signature,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $registration->refresh();
    expect($registration->status)->toBe('registered');
    expect((float) $registration->amount_paid)->toBe(150.00);
});

test('staff member can register user with cash payment, instantly confirming slot with registered status', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $settings->update(['entrance_fee' => 150.00]);
    $event->update(['entrance_fee' => 150.00]);

    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_STAFF);

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    $response = $this->actingAs($staff)->post(route('open-play.register-cash', $event->id), [
        'user_id' => $user->id,
    ]);

    $response->assertRedirect();
    $registration = OpenPlayRegistration::where('open_play_event_id', $event->id)
        ->where('user_id', $user->id)
        ->first();

    expect($registration)->not->toBeNull();
    expect($registration->status)->toBe('registered');
    expect((float) $registration->amount_paid)->toBe(150.00);
});

test('staff member can confirm pending registration as paid (mark cash paid)', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $settings->update(['entrance_fee' => 150.00]);
    $event->update(['entrance_fee' => 150.00]);

    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_STAFF);

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    // Create a pending registration
    $registration = OpenPlayRegistration::create([
        'open_play_event_id' => $event->id,
        'user_id' => $user->id,
        'slot_number' => 1,
        'status' => 'pending_payment',
        'amount_paid' => 0,
        'registered_at' => now(),
    ]);

    $response = $this->actingAs($staff)->post(route('open-play.mark-cash-paid', $registration->id));

    $response->assertRedirect();
    $registration->refresh();

    expect($registration->status)->toBe('registered');
    expect((float) $registration->amount_paid)->toBe(150.00);
});

test('staff can search for unregistered user accounts by name or email', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $staff = User::factory()->create();
    $staff->assignRole(User::ROLE_STAFF);

    // Create unregistered users
    $user1 = User::factory()->create(['email' => 'juan.cruz@example.com']);
    $user1->assignRole(User::ROLE_END_USER);
    $user1->endUserProfile()->create([
        'first_name' => 'Juan',
        'last_name' => 'Cruz',
        'gender' => 'male',
    ]);

    $user2 = User::factory()->create(['email' => 'pedro.cruz@example.com']);
    $user2->assignRole(User::ROLE_END_USER);
    $user2->endUserProfile()->create([
        'first_name' => 'Pedro',
        'last_name' => 'Cruz',
        'gender' => 'male',
    ]);

    // Request manage page with user search query 'Pedro'
    $response = $this->actingAs($staff)->get(route('open-play.manage', ['user_q' => 'Pedro']));

    $response->assertStatus(200);
    $response->assertViewHas('unregisteredUsers', function ($users) use ($user1, $user2) {
        return $users->contains('id', $user2->id) && !$users->contains('id', $user1->id);
    });
});

test('open play pending payment registration auto-cancels after 3 minutes', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $settings->update(['entrance_fee' => 150.00]);
    $event->update(['entrance_fee' => 150.00]);

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    // Create a pending registration that is 4 minutes old
    $registration = OpenPlayRegistration::create([
        'open_play_event_id' => $event->id,
        'user_id' => $user->id,
        'slot_number' => 1,
        'status' => 'pending_payment',
        'amount_paid' => 0,
        'registered_at' => now()->subMinutes(4),
        'created_at' => now()->subMinutes(4),
    ]);

    // Query upcoming event, which should run the auto-expire clean-up
    $upcomingEvent = OpenPlayEvent::upcoming($settings);

    $registration->refresh();
    expect($registration->status)->toBe('cancelled');
});

test('authenticated user can query registration status', function () {
    [$locationId, $court1Id, $court2Id, $event, $settings] = setupOpenPlayScenario();

    $user = User::factory()->create();
    $user->assignRole(User::ROLE_END_USER);

    $registration = OpenPlayRegistration::create([
        'open_play_event_id' => $event->id,
        'user_id' => $user->id,
        'slot_number' => 1,
        'status' => 'pending_payment',
        'amount_paid' => 0,
        'registered_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('open-play.registration-status', $registration->id));

    $response->assertStatus(200);
    $response->assertJson([
        'id' => $registration->id,
        'status' => 'pending_payment',
    ]);
});

