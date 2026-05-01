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
                'email' => 'owner@example.com',
                'mobile_number' => '09999999991',
                'role' => User::ROLE_OWNER_ADMIN,
            ],
            [
                'email' => 'staff@example.com',
                'mobile_number' => '09999999992',
                'role' => User::ROLE_STAFF,
            ],
            [
                'email' => 'user@example.com',
                'mobile_number' => '09999999993',
                'role' => User::ROLE_END_USER,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'mobile_number' => $userData['mobile_number'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'mobile_verified_at' => now(),
                ],
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
