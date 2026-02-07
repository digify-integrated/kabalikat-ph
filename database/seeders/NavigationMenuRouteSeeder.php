<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationMenuRouteSeeder extends Seeder
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

        $navigationMenuRoutes = [
            [
                'navigation_menu_id' => 1,
                'route_type' => 'index',
                'view_file'=> 'pages.app.index',
                'js_file' => 'app',
            ],
            [
                'navigation_menu_id' => 1,
                'route_type' => 'new',
                'view_file'=> 'pages.app.new',
                'js_file' => 'app-new',
            ],
            [
                'navigation_menu_id' => 1,
                'route_type' => 'new',
                'view_file'=> 'pages.app.details',
                'js_file' => 'app-details',
            ],
        ];

        DB::table('navigation_menu_route')->insert(
            array_map(fn ($row) => $row + $defaults, $navigationMenuRoutes)
        );
    }
}
