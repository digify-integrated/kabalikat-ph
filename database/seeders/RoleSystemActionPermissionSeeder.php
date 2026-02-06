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

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 1,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 2,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 3,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 4,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 5,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 6,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 7,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 8,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 9,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_system_action_permission')->insert([
            'role_id' => 1,
            'system_action_id' => 10,
            'system_action_access' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
