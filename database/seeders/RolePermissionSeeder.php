<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
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

        $rolePermissions = [
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'navigation_menu_id' => 1,
                'navigation_menu_name' => 'Apps',
                'read_access' => true,
                'write_access' => true,
                'create_access' => true,
                'delete_access' => true,
                'import_access' => true,
                'export_access' => true,
                'logs_access' => true,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'navigation_menu_id' => 2,
                'navigation_menu_name' => 'Settings',
                'read_access' => true,
                'write_access' => false,
                'create_access' => false,
                'delete_access' => false,
                'import_access' => false,
                'export_access' => false,
                'logs_access' => false,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'navigation_menu_id' => 3,
                'navigation_menu_name' => 'Users & Companies',
                'read_access' => true,
                'write_access' => false,
                'create_access' => false,
                'delete_access' => false,
                'import_access' => false,
                'export_access' => false,
                'logs_access' => false,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'navigation_menu_id' => 4,
                'navigation_menu_name' => 'User Account',
                'read_access' => true,
                'write_access' => true,
                'create_access' => true,
                'delete_access' => true,
                'import_access' => true,
                'export_access' => true,
                'logs_access' => true,
            ],
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'navigation_menu_id' => 5,
                'navigation_menu_name' => 'Company',
                'read_access' => true,
                'write_access' => true,
                'create_access' => true,
                'delete_access' => true,
                'import_access' => true,
                'export_access' => true,
                'logs_access' => true,
            ],
        ];

        DB::table('role_permission')->insert(
            array_map(fn ($row) => $row + $defaults, $rolePermissions)
        );
    }
}
