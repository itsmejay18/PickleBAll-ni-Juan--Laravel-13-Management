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
                'role_name' => 'Super Admin',
                'role_slug' => User::ROLE_SUPER_ADMIN,
                'description' => 'System owner with unrestricted platform access.',
                'priority_level' => 100,
            ],
            [
                'role_name' => 'Admin',
                'role_slug' => User::ROLE_ADMIN,
                'description' => 'Administrator with business management access.',
                'priority_level' => 80,
            ],
            [
                'role_name' => 'Location Manager',
                'role_slug' => User::ROLE_LOCATION_MANAGER,
                'description' => 'Manager for assigned branch operations.',
                'priority_level' => 60,
            ],
            [
                'role_name' => 'Staff',
                'role_slug' => User::ROLE_STAFF,
                'description' => 'Facility staff who can manage daily operations.',
                'priority_level' => 40,
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

        $roleSlugs = array_column($roles, 'role_slug');
        $superAdminRoleId = Role::query()->where('name', User::ROLE_SUPER_ADMIN)->value('id');
        $legacyOwnerRoleId = Role::query()->where('name', 'owner_admin')->value('id');

        if ($superAdminRoleId && $legacyOwnerRoleId) {
            DB::table('model_has_roles')
                ->where('role_id', $legacyOwnerRoleId)
                ->update(['role_id' => $superAdminRoleId]);
        }

        Role::query()
            ->whereNotIn('name', $roleSlugs)
            ->whereNotIn('id', DB::table('model_has_roles')->select('role_id'))
            ->delete();

        DB::table('user_role_types')
            ->whereNotIn('role_slug', $roleSlugs)
            ->whereNotIn('id', DB::table('user_roles')->select('role_id'))
            ->delete();
    }
}
