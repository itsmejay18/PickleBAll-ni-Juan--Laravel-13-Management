<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'staff', 'location_manager', 'end_user'] as $r) {
        Role::findOrCreate($r, 'web');
    }
});

test('super admin can update gcash number and upload qr code image', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $response = $this->actingAs($superAdmin)->post('/admin/settings/gcash', [
        'owner_gcash_number' => '09998887777',
        'owner_gcash_qr' => UploadedFile::fake()->image('my-gcash-qr.png'),
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('modules.show', 'payments'));
    $response->assertSessionHas('status', 'GCash settings updated successfully.');

    // Assert database has updated value
    $this->assertDatabaseHas('system_settings', [
        'setting_key' => 'owner_gcash_number',
        'setting_value' => '09998887777',
    ]);

    $qrSetting = DB::table('system_settings')->where('setting_key', 'owner_gcash_qr_path')->first();
    expect($qrSetting)->not->toBeNull();
    expect($qrSetting->setting_value)->toStartWith('/storage/uploads/');

    // Assert file was uploaded
    $storedPath = str_replace('/storage/', '', $qrSetting->setting_value);
    Storage::disk('public')->assertExists($storedPath);
});

test('end user cannot update gcash settings', function () {
    $user = User::factory()->create();
    $user->assignRole('end_user');

    $response = $this->actingAs($user)->post('/admin/settings/gcash', [
        'owner_gcash_number' => '09998887777',
    ]);

    $response->assertStatus(403);

    // Assert database was NOT updated
    $this->assertDatabaseMissing('system_settings', [
        'setting_key' => 'owner_gcash_number',
        'setting_value' => '09998887777',
    ]);
});
