<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles in both Spatie and business lookup tables.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            [
                'role_name' => 'Owner/Admin',
                'role_slug' => User::ROLE_OWNER_ADMIN,
                'description' => 'Business owner or administrator with full management access.',
                'priority_level' => 100,
            ],
            [
                'role_name' => 'Staff',
                'role_slug' => User::ROLE_STAFF,
                'description' => 'Facility staff who can manage daily operations.',
                'priority_level' => 50,
            ],
            [
                'role_name' => 'End User',
                'role_slug' => User::ROLE_END_USER,
                'description' => 'Customer account for booking courts and equipment.',
                'priority_level' => 10,
            ],
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role['role_slug'], 'web');

            DB::table('user_role_types')->updateOrInsert(
                ['role_slug' => $role['role_slug']],
                [
                    'role_name' => $role['role_name'],
                    'description' => $role['description'],
                    'priority_level' => $role['priority_level'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        DB::table('user_role_types')
            ->whereNotIn('role_slug', array_column($roles, 'role_slug'))
            ->whereNotIn('id', DB::table('user_roles')->select('role_id'))
            ->delete();
    }
}
