<?php

use App\Models\Reservation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'staff', 'location_manager', 'end_user'] as $r) {
        Role::findOrCreate($r, 'web');
    }
});

test('notifyStaffAndAdmins sends in-app notifications to all admins and location-scoped staff', function () {
    // 1. Create locations
    $location1Id = DB::table('locations')->insertGetId([
        'name' => 'Location 1',
        'slug' => 'location-1',
        'branch_code' => 'L1',
        'address_line1' => '123 St',
        'city' => 'Manila',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $location2Id = DB::table('locations')->insertGetId([
        'name' => 'Location 2',
        'slug' => 'location-2',
        'branch_code' => 'L2',
        'address_line1' => '456 St',
        'city' => 'Cebu',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 2. Create users
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $staffLocation1 = User::factory()->create();
    $staffLocation1->assignRole(User::ROLE_STAFF);
    DB::table('staff_profiles')->insert([
        'user_id' => $staffLocation1->id,
        'assigned_location_id' => $location1Id,
        'employee_id' => 'EMP-01',
        'position' => 'Staff 1',
        'first_name' => 'StaffOne',
        'last_name' => 'LocOne',
        'hire_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $staffLocation2 = User::factory()->create();
    $staffLocation2->assignRole(User::ROLE_STAFF);
    DB::table('staff_profiles')->insert([
        'user_id' => $staffLocation2->id,
        'assigned_location_id' => $location2Id,
        'employee_id' => 'EMP-02',
        'position' => 'Staff 2',
        'first_name' => 'StaffTwo',
        'last_name' => 'LocTwo',
        'hire_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 3. Create reservation at Location 1
    $courtId = DB::table('courts')->insertGetId([
        'location_id' => $location1Id,
        'court_number' => '1',
        'court_name' => 'Court 1',
        'court_type' => 'indoor',
        'surface_type' => 'acrylic',
        'is_active' => true,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservationId = DB::table('reservations')->insertGetId([
        'reservation_code' => 'RES-TEST123',
        'user_id' => User::factory()->create()->id,
        'court_id' => $courtId,
        'location_id' => $location1Id,
        'reservation_date' => now()->toDateString(),
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
        'court_price_per_hour' => 500,
        'court_subtotal' => 500,
        'equipment_total' => 0,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 500,
        'reservation_type' => 'online',
        'status' => 'pending_payment',
        'payment_status' => 'unpaid',
        'is_active' => true,
        'created_ip' => '127.0.0.1',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reservation = Reservation::find($reservationId);

    // 4. Trigger notifyStaffAndAdmins
    app(NotificationService::class)->notifyStaffAndAdmins(
        'Test Subject',
        'Test Message',
        $reservation
    );

    // 5. Verify who received the notifications in the database
    $this->assertDatabaseHas('notification_logs', [
        'user_id' => $superAdmin->id,
        'subject' => 'Test Subject',
        'channel' => 'admin',
    ]);

    $this->assertDatabaseHas('notification_logs', [
        'user_id' => $admin->id,
        'subject' => 'Test Subject',
        'channel' => 'admin',
    ]);

    $this->assertDatabaseHas('notification_logs', [
        'user_id' => $staffLocation1->id,
        'subject' => 'Test Subject',
        'channel' => 'staff',
    ]);

    $this->assertDatabaseMissing('notification_logs', [
        'user_id' => $staffLocation2->id,
        'subject' => 'Test Subject',
    ]);
});
