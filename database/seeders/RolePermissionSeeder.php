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
        ];

        DB::table('role_permission')->insert(
            array_map(fn ($row) => $row + $defaults, $rolePermissions)
        );
    }
}
