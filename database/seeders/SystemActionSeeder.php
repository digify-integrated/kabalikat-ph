<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('system_action')->insert([
            'system_action' => 'Activate User Account',
            'system_action_description' => 'Access to activate the user account.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Deactivate User Account',
            'system_action_description' => 'Access to deactivate the user account.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Add Role User Account',
            'system_action_description' => 'Access to assign roles to user account.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Delete Role User Account',
            'system_action_description' => 'Access to delete roles to user account.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Add Role Access',
            'system_action_description' => 'Access to add role access.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Update Role Access',
            'system_action_description' => 'Access to update role access.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Delete Role Access',
            'system_action_description' => 'Access to delete role access.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Add Role System Action Access',
            'system_action_description' => 'Access to add the role system action access.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Update Role System Action Access',
            'system_action_description' => 'Access to update the role system action access.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('system_action')->insert([
            'system_action' => 'Delete Role System Action Access',
            'system_action_description' => 'Access to delete the role system action access.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
