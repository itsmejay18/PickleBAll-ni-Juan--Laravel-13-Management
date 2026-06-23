<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'staff', 'location_manager', 'end_user'] as $r) {
        Role::findOrCreate($r, 'web');
    }
});

test('admin can access rates management module and view courts and equipment lists', function () {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $response = $this->actingAs($admin)->get('/modules/rates');

    $response->assertStatus(200);
    $response->assertViewHas('module', 'rates');
    $response->assertViewHas('page');
    
    $pageData = $response->viewData('page');
    expect($pageData)->toHaveKey('courts');
    expect($pageData)->toHaveKey('equipment');
    expect($pageData)->toHaveKey('locationOptions');
});

test('admin can add, update and delete a pricing rule', function () {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Branch A',
        'slug' => 'branch-a-pricing',
        'branch_code' => 'BR-A-PRC',
        'address_line1' => '1 Court Street',
        'city' => 'Digos',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $locationId,
        'court_number' => 'Z',
        'court_name' => 'Court Z',
        'court_type' => 'outdoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 1. Add pricing rule
    $addResponse = $this->actingAs($admin)->postJson("/courts/{$courtId}/rates", [
        'day_of_week' => 5,
        'start_time' => '18:00',
        'end_time' => '22:00',
        'base_price' => 750,
        'effective_to' => '2026-12-31',
    ]);

    $addResponse->assertStatus(200);
    $addResponse->assertJson(['success' => true]);
    $rateId = $addResponse->json('id');

    $this->assertDatabaseHas('court_pricing_rules', [
        'id' => $rateId,
        'court_id' => $courtId,
        'day_of_week' => 5,
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
        'base_price' => 750.00,
        'effective_to' => '2026-12-31',
    ]);

    // 2. Update pricing rule
    $updateResponse = $this->actingAs($admin)->putJson("/courts/rates/{$rateId}", [
        'day_of_week' => 6,
        'start_time' => '19:00',
        'end_time' => '23:00',
        'base_price' => 800,
        'effective_to' => '2027-01-01',
    ]);

    $updateResponse->assertStatus(200);
    $updateResponse->assertJson(['success' => true]);

    $this->assertDatabaseHas('court_pricing_rules', [
        'id' => $rateId,
        'day_of_week' => 6,
        'start_time' => '19:00:00',
        'end_time' => '23:00:00',
        'base_price' => 800.00,
        'effective_to' => '2027-01-01',
    ]);

    // 3. Delete pricing rule
    $deleteResponse = $this->actingAs($admin)->deleteJson("/courts/rates/{$rateId}");
    $deleteResponse->assertStatus(200);
    $deleteResponse->assertJson(['success' => true]);

    $this->assertDatabaseMissing('court_pricing_rules', [
        'id' => $rateId,
    ]);
});
