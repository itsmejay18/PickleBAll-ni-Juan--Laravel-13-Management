<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'staff', 'location_manager', 'end_user'] as $r) {
        Role::findOrCreate($r, 'web');
    }
});

test('admin can create a staff user with branch assignment and permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $locationId = DB::table('locations')->insertGetId([
        'name' => 'Branch A',
        'slug' => 'branch-a',
        'branch_code' => 'BR-A',
        'address_line1' => '1 Court Street',
        'city' => 'Digos',
        'country' => 'Philippines',
        'operating_hours' => json_encode([]),
        'timezone' => 'Asia/Manila',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post('/users', [
        'role' => 'staff',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@example.com',
        'mobile_number' => '09171234567',
        'password' => 'SuperSecret#1',
        'password_confirmation' => 'SuperSecret#1',
        'employee_id' => 'EMP-001',
        'position' => 'Front Desk',
        'hire_date' => now()->toDateString(),
        'assigned_location_id' => $locationId,
        'can_confirm_payments' => '1',
        'is_active' => '1',
    ]);

    $response->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'maria.santos@example.com')->firstOrFail();
    expect($user->hasRole('staff'))->toBeTrue();
    $this->assertDatabaseHas('staff_profiles', [
        'user_id' => $user->id,
        'employee_id' => 'EMP-001',
        'assigned_location_id' => $locationId,
        'can_confirm_payments' => 1,
    ]);
});

test('non-super-admin cannot promote a user to super admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $response = $this->actingAs($admin)->post('/users', [
        'role' => 'super_admin',
        'first_name' => 'Up',
        'last_name' => 'Grade',
        'email' => 'super@example.com',
        'mobile_number' => '09181234567',
        'password' => 'SuperSecret#1',
        'password_confirmation' => 'SuperSecret#1',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'super@example.com']);
});

test('admin cannot delete their own account from user management', function () {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $response = $this->actingAs($admin)->delete('/users/'.$admin->id);
    $response->assertForbidden();
    $this->assertNotSoftDeleted($admin);
});

test('toggling active also clears the lockout', function () {
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $target = User::factory()->create([
        'login_attempts' => 5,
        'locked_until' => now()->addHour(),
    ]);
    $target->assignRole(User::ROLE_STAFF);

    $response = $this->actingAs($admin)->post('/users/'.$target->id.'/toggle-active');
    $response->assertSessionHasNoErrors();

    $target->refresh();
    expect($target->is_active)->toBeFalse();
    expect($target->locked_until)->toBeNull();
    expect($target->login_attempts)->toBe(0);
});

test('end users cannot reach the user management module', function () {
    $customer = User::factory()->create();
    $customer->assignRole(User::ROLE_END_USER);

    $response = $this->actingAs($customer)->post('/users', [
        'role' => 'end_user',
        'first_name' => 'X',
        'last_name' => 'Y',
        'email' => 'x@y.com',
        'mobile_number' => '09150000000',
        'password' => 'whatever-123',
        'password_confirmation' => 'whatever-123',
    ]);

    $response->assertForbidden();
});
