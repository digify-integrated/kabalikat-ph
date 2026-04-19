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
            // Settings App
            // Apps ID: 1
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
            // Company ID: 2
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
            // Role ID: 3
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
            // User Account ID: 4
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

            // Configurations ID: 5
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

            // Configurations -> Data Classification  ID: 6
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
            // Configurations -> Data Classification -> File Type ID: 7
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
            // Configurations -> Data Classification -> File Extension ID: 8
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

            // Configurations -> Localization ID: 9
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
            // Configurations -> Localization -> Country ID: 10
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
            // Configurations -> Localization -> State ID: 11
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
            // Configurations -> Localization -> City ID: 12
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
            // Configurations -> Localization -> Currency ID: 13
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

            // Settings ID: 14
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
            // Setting -> Navigation Menu ID: 15
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
            // Setting -> System Action ID: 16
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
            // Setting -> Upload Setting ID: 17
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

            // Inventory App
            // Inventory Dashboard ID: 18
            [
                'navigation_menu_name'          => 'Dashboard',
                'navigation_menu_icon'          => 'ki-outline ki-category',
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 1
            ],

            // Products ID: 19
            [
                'navigation_menu_name'          => 'Products',
                'navigation_menu_icon'          => 'ki-outline ki-lots-shopping',
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'product',
                'order_sequence'                => 2
            ],

            // Inventory ID: 20
            [
                'navigation_menu_name'          => 'Inventory',
                'navigation_menu_icon'          => 'ki-outline ki-shop',
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 3
            ],

            // Inventory -> Batch Tracking ID: 21
            [
                'navigation_menu_name'          => 'Batch Tracking',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 20,
                'parent_navigation_menu_name'   => 'Inventory',
                'database_table'                => 'batch_tracking',
                'order_sequence'                => 1
            ],

            // Inventory -> Stock Adjustments ID: 22
            [
                'navigation_menu_name'          => 'Stock Adjustments',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 20,
                'parent_navigation_menu_name'   => 'Inventory',
                'database_table'                => 'stock_adjustment',
                'order_sequence'                => 2
            ],

            // Inventory -> Stock Transfer ID: 23
            [
                'navigation_menu_name'          => 'Stock Transfer',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 20,
                'parent_navigation_menu_name'   => 'Inventory',
                'database_table'                => 'stock_transfer',
                'order_sequence'                => 3
            ],

            // Purchase Order ID: 24
            [
                'navigation_menu_name'          => 'Purchase Order',
                'navigation_menu_icon'          => 'ki-outline ki-purchase',
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'purchase_order',
                'order_sequence'                => 4
            ],

            // Configurations -> Suppliers ID: 25
            [
                'navigation_menu_name'          => 'Suppliers',
                'navigation_menu_icon'          => 'ki-outline ki-parcel-tracking',
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'supplier',
                'order_sequence'                => 5
            ],

            // Warehouse ID: 26
            [
                'navigation_menu_name'          => 'Warehouse',
                'navigation_menu_icon'          => 'ki-outline ki-parcel',
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => 'warehouse',
                'order_sequence'                => 6
            ],

            // Configurations ID: 27
            [
                'navigation_menu_name'          => 'Configurations',
                'navigation_menu_icon'          => 'ki-outline ki-wrench',
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => null,
                'parent_navigation_menu_name'   => null,
                'database_table'                => null,
                'order_sequence'                => 7
            ],

            // Configurations -> Attribute ID: 28
            [
                'navigation_menu_name'          => 'Attribute',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 27,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => 'attribute',
                'order_sequence'                => 1
            ],

            // Configurations -> Product Category ID: 29
            [
                'navigation_menu_name'          => 'Product Category',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 27,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => 'product_category',
                'order_sequence'                => 2
            ],

            // Configurations -> Stock Adjustment Reason ID: 30
            [
                'navigation_menu_name'          => 'Stock Adjustment Reason',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 27,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => 'stock_adjustment_reason',
                'order_sequence'                => 3
            ],

            // Configurations -> Units ID: 31
            [
                'navigation_menu_name'          => 'Unit Management',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 27,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => null,
                'order_sequence'                => 5
            ],

            // Configurations -> Unit Management -> Units ID: 32
            [
                'navigation_menu_name'          => 'Units',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 31,
                'parent_navigation_menu_name'   => 'Unit Management',
                'database_table'                => 'unit',
                'order_sequence'                => 1
            ],

            // Configurations -> Unit Management -> Unit Conversion ID: 33
            [
                'navigation_menu_name'          => 'Unit Conversion',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 31,
                'parent_navigation_menu_name'   => 'Unit Management',
                'database_table'                => 'unit_type',
                'order_sequence'                => 2
            ],

            // Configurations -> Unit Management ->Unit Type ID: 34
            [
                'navigation_menu_name'          => 'Unit Type',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 31,
                'parent_navigation_menu_name'   => 'Unit Management',
                'database_table'                => 'unit_type',
                'order_sequence'                => 3
            ],

            // Configurations -> Warehouse Type ID: 35
            [
                'navigation_menu_name'          => 'Warehouse Type',
                'navigation_menu_icon'          => null,
                'app_id'                        => 4,
                'app_name'                      => 'Inventory',
                'parent_navigation_menu_id'     => 27,
                'parent_navigation_menu_name'   => 'Configurations',
                'database_table'                => 'warehouse_type',
                'order_sequence'                => 6
            ],
        ];

        DB::table('navigation_menu')->insert(
            array_map(fn ($row) => $row + $defaults, $navigationMenus)
        );
    }
}
