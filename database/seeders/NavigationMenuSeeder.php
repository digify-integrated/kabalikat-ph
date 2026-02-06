<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NavigationMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'App Module',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Settings',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 80,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Users & Companies',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 21,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'User Account',
            'navigation_menu_icon' => 'ki-outline ki-user',
            'app_id' => 1,
            'parent_navigation_menu_id' => 3,
            'order_sequence' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Company',
            'navigation_menu_icon' => 'ki-outline ki-shop',
            'app_id' => 1,
            'parent_navigation_menu_id' => 3,
            'order_sequence' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Role',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'User Interface',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 16,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Navigation Menu',
            'navigation_menu_icon' => 'ki-outline ki-data',
            'app_id' => 1,
            'parent_navigation_menu_id' => 7,
            'order_sequence' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'System Action',
            'navigation_menu_icon' => 'ki-outline ki-key-square',
            'app_id' => 1,
            'parent_navigation_menu_id' => 7,
            'order_sequence' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Account Settings',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Configurations',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 50,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Localization',
            'navigation_menu_icon' => 'ki-outline ki-compass',
            'app_id' => 1,
            'parent_navigation_menu_id' => 11,
            'order_sequence' => 12,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Country',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => 12,
            'order_sequence' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'State',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => 12,
            'order_sequence' => 19,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'City',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => 12,
            'order_sequence' => 21,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Currency',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => null,
            'order_sequence' => 3,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Nationality',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' =>12,
            'order_sequence' => 20,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Data Classification',
            'navigation_menu_icon' => 'ki-outline ki-file-up',
            'app_id' => 1,
            'parent_navigation_menu_id' => 11,
            'order_sequence' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'File Type',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => 18,
            'order_sequence' => 6,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'File Extension',
            'navigation_menu_icon' => null,
            'app_id' => 1,
            'parent_navigation_menu_id' => 18,
            'order_sequence' => 6,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Upload Setting',
            'navigation_menu_icon' => 'ki-outline ki-exit-up',
            'app_id' => 1,
            'parent_navigation_menu_id' => 2,
            'order_sequence' => 21,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_menu')->insert([
            'navigation_menu_name' => 'Notification Setting',
            'navigation_menu_icon' => 'ki-outline ki-notification',
            'app_id' => 1,
            'parent_navigation_menu_id' => 2,
            'order_sequence' => 14,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
