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
                'navigation_menu_name'          => 'Apps',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'app',
                'order_sequence'                => 1,
            ],
            [
                'navigation_menu_name'          => 'Settings',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 80,
            ],
            [
                'navigation_menu_name'          => 'Users & Companies',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 21,
            ],
            [
                'navigation_menu_name'          => 'User Account',
                'navigation_menu_icon'          => 'ki-outline ki-user',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 3,
                'parent_navigation_menu_name'   => 'Users & Companies',
                'database_table'                => 'users',
                'order_sequence'                => 1,
            ],
            [
                'navigation_menu_name'          => 'Company',
                'navigation_menu_icon'          => 'ki-outline ki-shop',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 3,
                'parent_navigation_menu_name'   => 'Users & Companies',
                'database_table'                => 'company',
                'order_sequence'                => 2
            ],
            [
                'navigation_menu_name'          => 'Role',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'role',
                'order_sequence'                => 2
            ],
            [
                'navigation_menu_name'          => 'User Interface',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 16
            ],
            [
                'navigation_menu_name'          => 'Navigation Menu',
                'navigation_menu_icon'          => 'ki-outline ki-data',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 7,
                'parent_navigation_menu_name'   => 'User Interface',
                'database_table'                => 'menu_item',
                'order_sequence'                => 2
            ],
            [
                'navigation_menu_name'          => 'System Action',
                'navigation_menu_icon'          => 'ki-outline ki-key-square',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 7,
                'parent_navigation_menu_name'   => 'User Interface',
                'database_table'                => 'system_action',
                'order_sequence'                => 3
            ],
            [
                'navigation_menu_name'          => 'Configurations',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 50
            ],
            [
                'navigation_menu_name'          => 'Localization',
                'navigation_menu_icon'          => 'ki-outline ki-compass',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 10,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => null,
                'order_sequence'                => 50
            ],
            [
                'navigation_menu_name'          => 'Country',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 11,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'country',
                'order_sequence'                => 3
            ],
            [
                'navigation_menu_name'          => 'State',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 11,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'state',
                'order_sequence'                => 19
            ],
            [
                'navigation_menu_name'          => 'City',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 11,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'city',
                'order_sequence'                => 3
            ],
            [
                'navigation_menu_name'          => 'Currency',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 11,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'currency',
                'order_sequence'                => 3
            ],
            [
                'navigation_menu_name'          => 'Nationality',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 11,
                'parent_navigation_menu_name'   => 'Localization',
                'database_table'                => 'nationality',
                'order_sequence'                => 20
            ],
            [
                'navigation_menu_name'          => 'Data Classification',
                'navigation_menu_icon'          => 'ki-outline ki-file-up',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 10,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => null,
                'order_sequence'                => 4
            ],
            [
                'navigation_menu_name'          => 'File Type',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 17,
                'parent_navigation_menu_name'   => 'Data Classification',
                'database_table'                => 'file_type',
                'order_sequence'                => 6
            ],
            [
                'navigation_menu_name'          => 'File Extension',
                'navigation_menu_icon'          => null,
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 17,
                'parent_navigation_menu_name'   => 'Data Classification',
                'database_table'                => 'file_extension',
                'order_sequence'                => 6
            ],
            [
                'navigation_menu_name'          => 'Upload Setting',
                'navigation_menu_icon'          => 'ki-outline ki-exit-up',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 2,
                'parent_navigation_menu_name'   => 'Settings',
                'database_table'                => 'upload_setting',
                'order_sequence'                => 21
            ],
            [
                'navigation_menu_name'          => 'Notification Setting',
                'navigation_menu_icon'          => 'ki-outline ki-notification',
                'app_id'                        => 1,
                'app_name'                      => 'Settings',
                'parent_navigation_menu_id'     => 2,
                'parent_navigation_menu_name'   => 'Settings',
                'database_table'                => 'notification_setting',
                'order_sequence'                => 14
            ],
        ];

        DB::table('navigation_menu')->insert(
            array_map(fn ($row) => $row + $defaults, $navigationMenus)
        );
    }
}
