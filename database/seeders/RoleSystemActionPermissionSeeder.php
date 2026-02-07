<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSystemActionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $roleSystemActionPermissions = [
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'system_action_id' => 1,
                'system_action_name' => 'Activate User Account',
                'system_action_access' => true,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'system_action_id' => 2,
                'system_action_name' => 'Deactivate User Account',
                'system_action_access' => true,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'system_action_id' => 3,
                'system_action_name' => 'Update Role User Account',
                'system_action_access' => true,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'system_action_id' => 4,
                'system_action_name' => 'Update Role Access',
                'system_action_access' => true,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'system_action_id' => 5,
                'system_action_name' => 'Update Role System Action Access',
                'system_action_access' => true,
            ],
        ];

        DB::table('role_system_action_permission')->insert(
            array_map(fn ($row) => $row + $defaults, $roleSystemActionPermissions)
        );
    }
}
