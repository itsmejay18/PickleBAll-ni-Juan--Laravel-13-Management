<?php

use App\Mail\ReservationReceiptMail;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'staff', 'location_manager', 'end_user'] as $r) {
        Role::findOrCreate($r, 'web');
    }

    // Set defaults for XPayLink system settings
    SystemSetting::set('xpaylink_enabled', 'false');
    SystemSetting::set('xpaylink_public_key', '');
    SystemSetting::set('xpaylink_secret_key', '');
    SystemSetting::set('xpaylink_endpoint', 'https://synthwave.space/api/create-session.php');
});

test('super admin can update xpaylink settings successfully', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $response = $this->actingAs($superAdmin)->post('/admin/settings/gcash', [
        'owner_gcash_number' => '09123456789',
        'xpaylink_enabled' => true,
        'xpaylink_public_key' => 'pk_test_public_key',
        'xpaylink_secret_key' => 'sk_test_secret_key',
        'xpaylink_endpoint' => 'https://synthwave.space/api/create-session.php',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('modules.show', 'payments'));

    expect(SystemSetting::value('xpaylink_enabled'))->toBe(true);
    expect(SystemSetting::value('xpaylink_public_key'))->toBe('pk_test_public_key');
    expect(SystemSetting::value('xpaylink_secret_key'))->toBe('sk_test_secret_key');
    expect(SystemSetting::value('xpaylink_endpoint'))->toBe('https://synthwave.space/api/create-session.php');
});

test('end user cannot update xpaylink settings', function () {
    $user = User::factory()->create();
    $user->assignRole('end_user');

    $response = $this->actingAs($user)->post('/admin/settings/gcash', [
        'owner_gcash_number' => '09123456789',
        'xpaylink_enabled' => true,
        'xpaylink_public_key' => 'pk_test_public_key',
        'xpaylink_secret_key' => 'sk_test_secret_key',
    ]);

    $response->assertStatus(403);
});

test('redirect fails if xpaylink is disabled', function () {
    $user = User::factory()->create();
    $user->assignRole('end_user');

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Branch A',
        'slug' => 'branch-a',
        'branch_code' => 'PBJ-A',
        'address_line1' => 'Test Address',
        'city' => 'Test City',
        'operating_hours' => json_encode([]),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '1',
        'court_name' => 'Court 1',
        'court_type' => 'indoor',
        'surface_type' => 'concrete',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservationId = DB::table('reservations')->insertGetId([
        'reservation_code' => 'PBJ-TEST-123',
        'user_id' => $user->id,
        'court_id' => $courtId,
        'location_id' => $locationId,
        'reservation_date' => now()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'court_price_per_hour' => 500,
        'court_subtotal' => 500,
        'grand_total' => 500,
        'reservation_type' => 'online',
        'status' => 'pending_payment',
        'payment_status' => 'unpaid',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservation = Reservation::find($reservationId);

    $response = $this->actingAs($user)->post(route('payments.xpaylink.redirect', $reservation->id));
    $response->assertSessionHas('error', 'Automatic payments are not configured properly. Please contact the administrator.');
});

test('redirect success with active config and API mock redirecting to payment_url', function () {
    $user = User::factory()->create();
    $user->assignRole('end_user');

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Branch B',
        'slug' => 'branch-b',
        'branch_code' => 'PBJ-B',
        'address_line1' => 'Test Address',
        'city' => 'Test City',
        'operating_hours' => json_encode([]),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '2',
        'court_name' => 'Court 2',
        'court_type' => 'indoor',
        'surface_type' => 'concrete',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservationId = DB::table('reservations')->insertGetId([
        'reservation_code' => 'PBJ-XPAY-REDIRECT',
        'user_id' => $user->id,
        'court_id' => $courtId,
        'location_id' => $locationId,
        'reservation_date' => now()->toDateString(),
        'start_time' => '12:00:00',
        'end_time' => '13:00:00',
        'court_price_per_hour' => 500,
        'court_subtotal' => 500,
        'grand_total' => 500,
        'reservation_type' => 'online',
        'status' => 'pending_payment',
        'payment_status' => 'unpaid',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $reservation = Reservation::find($reservationId);

    // Setup active config
    SystemSetting::set('xpaylink_enabled', 'true');
    SystemSetting::set('xpaylink_public_key', 'pk_test_123');
    SystemSetting::set('xpaylink_secret_key', 'sk_test_123');

    // Mock API
    Http::fake([
        'https://synthwave.space/api/create-session.php' => Http::response([
            'success' => true,
            'session_id' => 'PS-MOCK-SESSION-456',
            'payment_url' => 'https://synthwave.space/pay.php?sid=PS-MOCK-SESSION-456',
        ], 200),
    ]);

    $response = $this->actingAs($user)->post(route('payments.xpaylink.redirect', $reservation->id));

    $response->assertRedirect('https://synthwave.space/pay.php?sid=PS-MOCK-SESSION-456');

    // Verify payment record was created
    $this->assertDatabaseHas('payments', [
        'reservation_id' => $reservation->id,
        'payment_reference' => 'XPAY-PBJ-XPAY-REDIRECT',
        'xpaylink_session_id' => 'PS-MOCK-SESSION-456',
        'status' => 'pending',
    ]);
});

test('webhook returns 401 for invalid or missing signatures', function () {
    SystemSetting::set('xpaylink_secret_key', 'sk_test_secret_key');

    $payload = [
        'event' => 'payment.paid',
        'status' => 'paid',
        'session_id' => 'PS-123',
    ];

    $response = $this->postJson(route('payments.xpaylink.webhook'), $payload, [
        'X-PayLink-Signature' => 'invalid-signature-here',
    ]);

    $response->assertStatus(401);
});

test('webhook successfully confirms payment and triggers events on valid signature', function () {
    Mail::fake();

    $user = User::factory()->create();
    $user->assignRole('end_user');

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Branch C',
        'slug' => 'branch-c',
        'branch_code' => 'PBJ-C',
        'address_line1' => 'Test Address',
        'city' => 'Test City',
        'operating_hours' => json_encode([]),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => '3',
        'court_name' => 'Court 3',
        'court_type' => 'indoor',
        'surface_type' => 'concrete',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservationId = DB::table('reservations')->insertGetId([
        'reservation_code' => 'PBJ-XPAY-WEBHOOK',
        'user_id' => $user->id,
        'court_id' => $courtId,
        'location_id' => $locationId,
        'reservation_date' => now()->toDateString(),
        'start_time' => '14:00:00',
        'end_time' => '15:00:00',
        'court_price_per_hour' => 600,
        'court_subtotal' => 600,
        'grand_total' => 600,
        'reservation_type' => 'online',
        'status' => 'pending_payment',
        'payment_status' => 'unpaid',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservation = Reservation::find($reservationId);

    // Setup active config
    SystemSetting::set('xpaylink_secret_key', 'sk_test_webhook_secret');

    // Build signed payload
    $payload = [
        'event' => 'payment.paid',
        'status' => 'paid',
        'session_id' => 'PS-XPAYLINK-WEBHOOK-999',
        'external_bill_id' => 'PBJ-XPAY-WEBHOOK',
        'customer_name' => 'Juan Dela Cruz',
        'customer_paid' => '615.00',
        'merchant_amount' => '600.00',
        'paylink_admin_fee' => '15.00',
        'amount' => '615.00',
        'sender_gcash_number_masked' => '0912***6789',
        'match_source' => 'auto_match',
        'matched_at' => now()->toDateTimeString(),
    ];

    $rawJson = json_encode($payload);
    $signature = hash_hmac('sha256', $rawJson, 'sk_test_webhook_secret');

    $response = $this->postJson(route('payments.xpaylink.webhook'), $payload, [
        'X-PayLink-Signature' => $signature,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    // Check reservation status in DB
    $updatedReservation = Reservation::find($reservationId);
    expect($updatedReservation->status)->toBe('confirmed');
    expect($updatedReservation->payment_status)->toBe('paid');

    // Verify payment was verified in DB
    $this->assertDatabaseHas('payments', [
        'reservation_id' => $reservation->id,
        'xpaylink_session_id' => 'PS-XPAYLINK-WEBHOOK-999',
        'status' => 'verified',
    ]);

    // Assert mail was queued
    Mail::assertQueued(ReservationReceiptMail::class, function ($mail) use ($reservationId) {
        return $mail->reservation->id === $reservationId;
    });

    // Assert notification was created
    $this->assertDatabaseHas('notification_logs', [
        'user_id' => $user->id,
        'reservation_id' => $reservation->id,
        'subject' => 'Payment approved for PBJ-XPAY-WEBHOOK',
    ]);
});
