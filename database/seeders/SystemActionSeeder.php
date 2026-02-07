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

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $systemActions = [
            [
                'system_action_name' => 'Activate User Account',
                'system_action_description' => 'Access to activate the user account.',
            ],
            [
                'system_action_name' => 'Deactivate User Account',
                'system_action_description' => 'Access to deactivate the user account.',
            ],
            [
                'system_action_name' => 'Update Role User Account',
                'system_action_description' => 'Access to update assiend user accounts to role.',
            ],
            [
                'system_action_name' => 'Update Role Access',
                'system_action_description' => 'Access to update role access.',
            ],
            [
                'system_action_name' => 'Update Role System Action Access',
                'system_action_description' => 'Access to update the role system action access.',
            ],
        ];

        DB::table('system_action')->insert(
            array_map(fn ($row) => $row + $defaults, $systemActions)
        );
    }
}
