<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $apps = [
            [
                'app_name'              => 'Settings',
                'app_description'       => 'Centralized management hub for comprehensive organizational oversight and control.',
                'app_version'           => '1.0.0',
                'app_logo'              => 'app/1/settings.png',
                'navigation_menu_id'    => 1,
                'navigation_menu_name'  => 'App Module',
                'order_sequence'        => 100,
            ],
            [
                'app_name'              => 'Employee',
                'app_description'       => 'Centralize employee information.',
                'app_version'           => '1.0.0',
                'app_logo'              => 'app/2/employees.png',
                'navigation_menu_id'    => 1,
                'navigation_menu_name'  => 'App Module',
                'order_sequence'        => 5,
            ],
            [
                'app_name'              => 'Point of Sale',
                'app_description'       => 'Handle checkouts and payments for shops and restaurants.',
                'app_version'           => '1.0.0',
                'app_logo'              => 'app/3/pos.png',
                'navigation_menu_id'    => 1,
                'navigation_menu_name'  => 'App Module',
                'order_sequence'        => 5,
            ],
            [
                'app_name'              => 'Inventory',
                'app_description'       => 'Manage your stocks and logistics activities.',
                'app_version'           => '1.0.0',
                'app_logo'              => 'app/4/inventory.png',
                'navigation_menu_id'    => 1,
                'navigation_menu_name'  => 'App Module',
                'order_sequence'        => 5,
            ],
        ];

        DB::table('app')->insert(
            array_map(fn ($row) => $row + $defaults, $apps)
        );
    }
}
