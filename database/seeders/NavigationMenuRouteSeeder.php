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
                'navigation_menu_id'    => 1,
                'route_type'            => 'index',
                'view_file'             => 'pages.app.index',
                'js_file'               => 'app/index',
            ],
            [
                'navigation_menu_id'    => 1,
                'route_type'            => 'new',
                'view_file'             => 'pages.app.new',
                'js_file'               => 'app/new',
            ],
            [
                'navigation_menu_id'    => 1,
                'route_type'            => 'details',
                'view_file'             => 'pages.app.details',
                'js_file'               => 'app/details',
            ],
            [
                'navigation_menu_id'    => 1,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],
            
            [
                'navigation_menu_id'    => 4,
                'route_type'            => 'index',
                'view_file'             => 'pages.user-account.index',
                'js_file'               => 'user-account/index',
            ],
            [
                'navigation_menu_id'    => 4,
                'route_type'            => 'new',
                'view_file'             => 'pages.user-account.new',
                'js_file'               => 'user-account/new',
            ],
            [
                'navigation_menu_id'    => 4,
                'route_type'            => 'details',
                'view_file'             => 'pages.user-account.details',
                'js_file'               => 'user-account/details',
            ],
            [
                'navigation_menu_id'    => 4,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 5,
                'route_type'            => 'index',
                'view_file'             => 'pages.company.index',
                'js_file'               => 'company/index',
            ],
            [
                'navigation_menu_id'    => 5,
                'route_type'            => 'new',
                'view_file'             => 'pages.company.new',
                'js_file'               => 'company/new',
            ],
            [
                'navigation_menu_id'    => 5,
                'route_type'            => 'details',
                'view_file'             => 'pages.company.details',
                'js_file'               => 'company/details',
            ],
            [
                'navigation_menu_id'    => 5,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 6,
                'route_type'            => 'index',
                'view_file'             => 'pages.role.index',
                'js_file'               => 'role/index',
            ],
            [
                'navigation_menu_id'    => 6,
                'route_type'            => 'new',
                'view_file'             => 'pages.role.new',
                'js_file'               => 'role/new',
            ],
            [
                'navigation_menu_id'    => 6,
                'route_type'            => 'details',
                'view_file'             => 'pages.role.details',
                'js_file'               => 'role/details',
            ],
            [
                'navigation_menu_id'    => 6,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'index',
                'view_file'             => 'pages.navigation-menu.index',
                'js_file'               => 'navigation-menu/index',
            ],
            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'new',
                'view_file'             => 'pages.navigation-menu.new',
                'js_file'               => 'navigation-menu/new',
            ],
            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'details',
                'view_file'             => 'pages.navigation-menu.details',
                'js_file'               => 'navigation-menu/details',
            ],
            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 9,
                'route_type'            => 'index',
                'view_file'             => 'pages.system-action.index',
                'js_file'               => 'system-action/index',
            ],
            [
                'navigation_menu_id'    => 9,
                'route_type'            => 'new',
                'view_file'             => 'pages.system-action.new',
                'js_file'               => 'system-action/new',
            ],
            [
                'navigation_menu_id'    => 9,
                'route_type'            => 'details',
                'view_file'             => 'pages.system-action.details',
                'js_file'               => 'system-action/details',
            ],
            [
                'navigation_menu_id'    => 9,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'index',
                'view_file'             => 'pages.country.index',
                'js_file'               => 'country/index',
            ],
            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'new',
                'view_file'             => 'pages.country.new',
                'js_file'               => 'country/new',
            ],
            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'details',
                'view_file'             => 'pages.country.details',
                'js_file'               => 'country/details',
            ],
            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'index',
                'view_file'             => 'pages.state.index',
                'js_file'               => 'state/index',
            ],
            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'new',
                'view_file'             => 'pages.state.new',
                'js_file'               => 'state/new',
            ],
            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'details',
                'view_file'             => 'pages.state.details',
                'js_file'               => 'state/details',
            ],
            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 14,
                'route_type'            => 'index',
                'view_file'             => 'pages.city.index',
                'js_file'               => 'city/index',
            ],
            [
                'navigation_menu_id'    => 14,
                'route_type'            => 'new',
                'view_file'             => 'pages.city.new',
                'js_file'               => 'city/new',
            ],
            [
                'navigation_menu_id'    => 14,
                'route_type'            => 'details',
                'view_file'             => 'pages.city.details',
                'js_file'               => 'city/details',
            ],
            [
                'navigation_menu_id'    => 14,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'index',
                'view_file'             => 'pages.currency.index',
                'js_file'               => 'currency/index',
            ],
            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'new',
                'view_file'             => 'pages.currency.new',
                'js_file'               => 'currency/new',
            ],
            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'details',
                'view_file'             => 'pages.currency.details',
                'js_file'               => 'currency/details',
            ],
            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'index',
                'view_file'             => 'pages.nationality.index',
                'js_file'               => 'nationality/index',
            ],
            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'new',
                'view_file'             => 'pages.nationality.new',
                'js_file'               => 'nationality/new',
            ],
            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'details',
                'view_file'             => 'pages.nationality.details',
                'js_file'               => 'nationality/details',
            ],
            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 18,
                'route_type'            => 'index',
                'view_file'             => 'pages.file-type.index',
                'js_file'               => 'file-type/index',
            ],
            [
                'navigation_menu_id'    => 18,
                'route_type'            => 'new',
                'view_file'             => 'pages.file-type.new',
                'js_file'               => 'file-type/new',
            ],
            [
                'navigation_menu_id'    => 18,
                'route_type'            => 'details',
                'view_file'             => 'pages.file-type.details',
                'js_file'               => 'file-type/details',
            ],
            [
                'navigation_menu_id'    => 18,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'index',
                'view_file'             => 'pages.file-extension.index',
                'js_file'               => 'file-extension/index',
            ],
            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'new',
                'view_file'             => 'pages.file-extension.new',
                'js_file'               => 'file-extension/new',
            ],
            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'details',
                'view_file'             => 'pages.file-extension.details',
                'js_file'               => 'file-extension/details',
            ],
            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 20,
                'route_type'            => 'index',
                'view_file'             => 'pages.upload-setting.index',
                'js_file'               => 'upload-setting/index',
            ],
            [
                'navigation_menu_id'    => 20,
                'route_type'            => 'new',
                'view_file'             => 'pages.upload-setting.new',
                'js_file'               => 'upload-setting/new',
            ],
            [
                'navigation_menu_id'    => 20,
                'route_type'            => 'details',
                'view_file'             => 'pages.upload-setting.details',
                'js_file'               => 'upload-setting/details',
            ],
            [
                'navigation_menu_id'    => 20,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],

            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'index',
                'view_file'             => 'pages.notification-setting.index',
                'js_file'               => 'notification-setting/index',
            ],
            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'new',
                'view_file'             => 'pages.notification-setting.new',
                'js_file'               => 'notification-setting/new',
            ],
            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'details',
                'view_file'             => 'pages.notification-setting.details',
                'js_file'               => 'notification-setting/details',
            ],
            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'import',
                'view_file'             => 'index',
                'js_file'               => 'import/import',
            ],
        ];

        DB::table('navigation_menu_route')->insert(
            array_map(fn ($row) => $row + $defaults, $navigationMenuRoutes)
        );
    }
}
