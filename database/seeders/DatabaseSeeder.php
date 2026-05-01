<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $users = [
            [
                'email' => 'superadmin@example.com',
                'mobile_number' => '09999999991',
                'role' => User::ROLE_SUPER_ADMIN,
            ],
            [
                'email' => 'admin@example.com',
                'mobile_number' => '09999999992',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'email' => 'manager@example.com',
                'mobile_number' => '09999999993',
                'role' => User::ROLE_LOCATION_MANAGER,
            ],
            [
                'email' => 'staff@example.com',
                'mobile_number' => '09999999994',
                'role' => User::ROLE_STAFF,
            ],
            [
                'email' => 'user@example.com',
                'mobile_number' => '09999999995',
                'role' => User::ROLE_END_USER,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::withTrashed()
                ->where('email', $userData['email'])
                ->orWhere('mobile_number', $userData['mobile_number'])
                ->first() ?? new User();

            $user->forceFill([
                'email' => $userData['email'],
                'mobile_number' => $userData['mobile_number'],
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'deleted_at' => null,
            ])->save();

            $user->syncRoles([$userData['role']]);
        }
    }
}
