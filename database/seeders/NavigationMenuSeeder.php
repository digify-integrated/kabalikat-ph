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

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $navigationMenus = [
            [
                'navigation_menu_name' => 'Apps',
                'navigation_menu_icon' => null,
                'app_id' => 1,
                'app_name' => 'Settings',
                'parent_navigation_menu_id' => null,
                'parent_navigation_menu_name' => null,
                'database_table' => 'app',
                'order_sequence' => 1,
            ],
        ];

        DB::table('navigation_menu')->insert(
            array_map(fn ($row) => $row + $defaults, $navigationMenus)
        );
    }
}
