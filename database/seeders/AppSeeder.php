<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class AppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('app')->insert([
            'app_name'     => 'Settings',
            'app_description'     => 'Centralized management hub for comprehensive organizational oversight and control.',
            'app_logo'            => 'app/1/settings.png',
            'navigation_menu_id'  => 1,
            'order_sequence'      => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('app')->insert([
            'app_name'     => 'Employee',
            'app_description'     => 'Centralize employee information.',
            'app_logo'            => 'app/2/employees.png',
            'navigation_menu_id'  => 1,
            'order_sequence'      => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('app')->insert([
            'app_name'     => 'Point of Sale',
            'app_description'     => 'Handle checkouts and payments for shops and restaurants.',
            'app_logo'            => 'app/3/pos.png',
            'navigation_menu_id'  => 1,
            'order_sequence'      => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('app')->insert([
            'app_name'     => 'Inventory',
            'app_description'     => 'Manage your stocks and logistics activities.',
            'app_logo'            => 'app/4/inventory.png',
            'navigation_menu_id'  => 1,
            'order_sequence'      => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
