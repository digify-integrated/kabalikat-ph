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
            // 1
            [
                'navigation_menu_name'          => 'Apps',
                'navigation_menu_icon'          => 'ki-outline ki-abstract-26',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'app',
                'order_sequence'                => 1,
            ],
            // 2
            [
                'navigation_menu_name'          => 'Company',
                'navigation_menu_icon'          => 'ki-outline ki-shop',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'company',
                'order_sequence'                => 2,
            ],
            // 3
            [
                'navigation_menu_name'          => 'Role',
                'navigation_menu_icon'          => 'ki-outline ki-security-user',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'role',
                'order_sequence'                => 2
            ],
            // 4
            [
                'navigation_menu_name'          => 'User Account',
                'navigation_menu_icon'          => 'ki-outline ki-user',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'users',
                'order_sequence'                => 1,
            ],

            // Configurations 5
            [
                'navigation_menu_name'          => 'Configurations',
                'navigation_menu_icon'          => 'ki-outline ki-wrench',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 50
            ],

            // Configurations -> Data Classification 6
            [
                'navigation_menu_name'          => 'Data Classification',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 5,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => null,
                'order_sequence'                => 4
            ],
            // 7
            [
                'navigation_menu_name'          => 'File Type',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 6,
                'parent_navigation_menu_name'   => 'Data Classification',
                'database_table'                => 'file_type',
                'order_sequence'                => 6
            ],
            // 8
            [
                'navigation_menu_name'          => 'File Extension',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 6,
                'parent_navigation_menu_name'   => 'Data Classification',
                'database_table'                => 'file_extension',
                'order_sequence'                => 6
            ],

            // Configurations -> Localization 9
            [
                'navigation_menu_name'          => 'Localization',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 5,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => null,
                'order_sequence'                => 50
            ],
            // 10
            [
                'navigation_menu_name'          => 'Country',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 9,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'country',
                'order_sequence'                => 3
            ],
            // 11
            [
                'navigation_menu_name'          => 'State',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 9,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'state',
                'order_sequence'                => 19
            ],
            // 12
            [
                'navigation_menu_name'          => 'City',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 9,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'city',
                'order_sequence'                => 3
            ],
            // 13
            [
                'navigation_menu_name'          => 'Currency',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 9,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'currency',
                'order_sequence'                => 3
            ],

            // Settings 14
            [
                'navigation_menu_name'          => 'Settings',
                'navigation_menu_icon'          => 'ki-outline ki-setting-2',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 80,
            ],
            // 15
            [
                'navigation_menu_name'          => 'Navigation Menu',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 14,
                'parent_navigation_menu_name'   => 'Settings',
                'database_table'                => 'navigation_menu',
                'order_sequence'                => 2
            ],
            // 16
            [
                'navigation_menu_name'          => 'System Action',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 14,
                'parent_navigation_menu_name'   => 'Settings',
                'database_table'                => 'system_action',
                'order_sequence'                => 3
            ],
            // 17
            [
                'navigation_menu_name'          => 'Upload Setting',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 14,
                'parent_navigation_menu_name'   => 'Settings',
                'database_table'                => 'upload_setting',
                'order_sequence'                => 21
            ],
        ];

        DB::table('navigation_menu')->insert(
            array_map(fn ($row) => $row + $defaults, $navigationMenus)
        );
    }
}
