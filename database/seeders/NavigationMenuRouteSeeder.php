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
            // App
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
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Company
            [
                'navigation_menu_id'    => 2,
                'route_type'            => 'index',
                'view_file'             => 'pages.company.index',
                'js_file'               => 'company/index',
            ],
            [
                'navigation_menu_id'    => 2,
                'route_type'            => 'new',
                'view_file'             => 'pages.company.new',
                'js_file'               => 'company/new',
            ],
            [
                'navigation_menu_id'    => 2,
                'route_type'            => 'details',
                'view_file'             => 'pages.company.details',
                'js_file'               => 'company/details',
            ],
            [
                'navigation_menu_id'    => 2,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Role
            [
                'navigation_menu_id'    => 3,
                'route_type'            => 'index',
                'view_file'             => 'pages.role.index',
                'js_file'               => 'role/index',
            ],
            [
                'navigation_menu_id'    => 3,
                'route_type'            => 'new',
                'view_file'             => 'pages.role.new',
                'js_file'               => 'role/new',
            ],
            [
                'navigation_menu_id'    => 3,
                'route_type'            => 'details',
                'view_file'             => 'pages.role.details',
                'js_file'               => 'role/details',
            ],
            [
                'navigation_menu_id'    => 3,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // User Account            
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
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // File Type 
            [
                'navigation_menu_id'    => 7,
                'route_type'            => 'index',
                'view_file'             => 'pages.file-type.index',
                'js_file'               => 'file-type/index',
            ],
            [
                'navigation_menu_id'    => 7,
                'route_type'            => 'new',
                'view_file'             => 'pages.file-type.new',
                'js_file'               => 'file-type/new',
            ],
            [
                'navigation_menu_id'    => 7,
                'route_type'            => 'details',
                'view_file'             => 'pages.file-type.details',
                'js_file'               => 'file-type/details',
            ],
            [
                'navigation_menu_id'    => 7,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // File Extension
            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'index',
                'view_file'             => 'pages.file-extension.index',
                'js_file'               => 'file-extension/index',
            ],
            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'new',
                'view_file'             => 'pages.file-extension.new',
                'js_file'               => 'file-extension/new',
            ],
            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'details',
                'view_file'             => 'pages.file-extension.details',
                'js_file'               => 'file-extension/details',
            ],
            [
                'navigation_menu_id'    => 8,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Country
            [
                'navigation_menu_id'    => 10,
                'route_type'            => 'index',
                'view_file'             => 'pages.country.index',
                'js_file'               => 'country/index',
            ],
            [
                'navigation_menu_id'    => 10,
                'route_type'            => 'new',
                'view_file'             => 'pages.country.new',
                'js_file'               => 'country/new',
            ],
            [
                'navigation_menu_id'    => 10,
                'route_type'            => 'details',
                'view_file'             => 'pages.country.details',
                'js_file'               => 'country/details',
            ],
            [
                'navigation_menu_id'    => 10,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // State 
            [
                'navigation_menu_id'    => 11,
                'route_type'            => 'index',
                'view_file'             => 'pages.state.index',
                'js_file'               => 'state/index',
            ],
            [
                'navigation_menu_id'    => 11,
                'route_type'            => 'new',
                'view_file'             => 'pages.state.new',
                'js_file'               => 'state/new',
            ],
            [
                'navigation_menu_id'    => 11,
                'route_type'            => 'details',
                'view_file'             => 'pages.state.details',
                'js_file'               => 'state/details',
            ],
            [
                'navigation_menu_id'    => 11,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // City
            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'index',
                'view_file'             => 'pages.city.index',
                'js_file'               => 'city/index',
            ],
            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'new',
                'view_file'             => 'pages.city.new',
                'js_file'               => 'city/new',
            ],
            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'details',
                'view_file'             => 'pages.city.details',
                'js_file'               => 'city/details',
            ],
            [
                'navigation_menu_id'    => 12,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Currency
            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'index',
                'view_file'             => 'pages.currency.index',
                'js_file'               => 'currency/index',
            ],
            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'new',
                'view_file'             => 'pages.currency.new',
                'js_file'               => 'currency/new',
            ],
            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'details',
                'view_file'             => 'pages.currency.details',
                'js_file'               => 'currency/details',
            ],
            [
                'navigation_menu_id'    => 13,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Navigation Menu
            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'index',
                'view_file'             => 'pages.navigation-menu.index',
                'js_file'               => 'navigation-menu/index',
            ],
            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'new',
                'view_file'             => 'pages.navigation-menu.new',
                'js_file'               => 'navigation-menu/new',
            ],
            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'details',
                'view_file'             => 'pages.navigation-menu.details',
                'js_file'               => 'navigation-menu/details',
            ],
            [
                'navigation_menu_id'    => 15,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],


            // System Action
            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'index',
                'view_file'             => 'pages.system-action.index',
                'js_file'               => 'system-action/index',
            ],
            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'new',
                'view_file'             => 'pages.system-action.new',
                'js_file'               => 'system-action/new',
            ],
            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'details',
                'view_file'             => 'pages.system-action.details',
                'js_file'               => 'system-action/details',
            ],
            [
                'navigation_menu_id'    => 16,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Upload Setting
            [
                'navigation_menu_id'    => 17,
                'route_type'            => 'index',
                'view_file'             => 'pages.upload-setting.index',
                'js_file'               => 'upload-setting/index',
            ],
            [
                'navigation_menu_id'    => 17,
                'route_type'            => 'new',
                'view_file'             => 'pages.upload-setting.new',
                'js_file'               => 'upload-setting/new',
            ],
            [
                'navigation_menu_id'    => 17,
                'route_type'            => 'details',
                'view_file'             => 'pages.upload-setting.details',
                'js_file'               => 'upload-setting/details',
            ],
            [
                'navigation_menu_id'    => 17,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            /* 
            ------------------------------------------------------------------------------------
                Inventory App
            ------------------------------------------------------------------------------------    
            */

            // Inventory Dashboard
            [
                'navigation_menu_id'    => 18,
                'route_type'            => 'index',
                'view_file'             => 'pages.inventory-dashboard.index',
                'js_file'               => 'inventory-dashboard/index',
            ],

            // Products
            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'index',
                'view_file'             => 'pages.products.index',
                'js_file'               => 'products/index',
            ],
            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'new',
                'view_file'             => 'pages.products.new',
                'js_file'               => 'products/new',
            ],
            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'details',
                'view_file'             => 'pages.products.details',
                'js_file'               => 'products/details',
            ],
            [
                'navigation_menu_id'    => 19,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Batch Tracking
            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'index',
                'view_file'             => 'pages.batch-tracking.index',
                'js_file'               => 'batch-tracking/index',
            ],
            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'new',
                'view_file'             => 'pages.batch-tracking.new',
                'js_file'               => 'batch-tracking/new',
            ],
            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'details',
                'view_file'             => 'pages.batch-tracking.details',
                'js_file'               => 'batch-tracking/details',
            ],
            [
                'navigation_menu_id'    => 21,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Stock Adjustments
            [
                'navigation_menu_id'    => 22,
                'route_type'            => 'index',
                'view_file'             => 'pages.stock-adjustments.index',
                'js_file'               => 'stock-adjustments/index',
            ],
            [
                'navigation_menu_id'    => 22,
                'route_type'            => 'new',
                'view_file'             => 'pages.stock-adjustments.new',
                'js_file'               => 'stock-adjustments/new',
            ],
            [
                'navigation_menu_id'    => 22,
                'route_type'            => 'details',
                'view_file'             => 'pages.stock-adjustments.details',
                'js_file'               => 'stock-adjustments/details',
            ],
            [
                'navigation_menu_id'    => 22,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Stock Transfer
            [
                'navigation_menu_id'    => 23,
                'route_type'            => 'index',
                'view_file'             => 'pages.stock-transfer.index',
                'js_file'               => 'stock-transfer/index',
            ],
            [
                'navigation_menu_id'    => 23,
                'route_type'            => 'new',
                'view_file'             => 'pages.stock-transfer.new',
                'js_file'               => 'stock-transfer/new',
            ],
            [
                'navigation_menu_id'    => 23,
                'route_type'            => 'details',
                'view_file'             => 'pages.stock-transfer.details',
                'js_file'               => 'stock-transfer/details',
            ],
            [
                'navigation_menu_id'    => 23,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Purchase Order
            [
                'navigation_menu_id'    => 24,
                'route_type'            => 'index',
                'view_file'             => 'pages.purchase-order.index',
                'js_file'               => 'purchase-order/index',
            ],
            [
                'navigation_menu_id'    => 24,
                'route_type'            => 'new',
                'view_file'             => 'pages.purchase-order.new',
                'js_file'               => 'purchase-order/new',
            ],
            [
                'navigation_menu_id'    => 24,
                'route_type'            => 'details',
                'view_file'             => 'pages.purchase-order.details',
                'js_file'               => 'purchase-order/details',
            ],
            [
                'navigation_menu_id'    => 24,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Product Attribute
            [
                'navigation_menu_id'    => 26,
                'route_type'            => 'index',
                'view_file'             => 'pages.product-attribute.index',
                'js_file'               => 'product-attribute/index',
            ],
            [
                'navigation_menu_id'    => 26,
                'route_type'            => 'new',
                'view_file'             => 'pages.product-attribute.new',
                'js_file'               => 'product-attribute/new',
            ],
            [
                'navigation_menu_id'    => 26,
                'route_type'            => 'details',
                'view_file'             => 'pages.product-attribute.details',
                'js_file'               => 'product-attribute/details',
            ],
            [
                'navigation_menu_id'    => 26,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Product Category
            [
                'navigation_menu_id'    => 27,
                'route_type'            => 'index',
                'view_file'             => 'pages.product-category.index',
                'js_file'               => 'product-category/index',
            ],
            [
                'navigation_menu_id'    => 27,
                'route_type'            => 'new',
                'view_file'             => 'pages.product-category.new',
                'js_file'               => 'product-category/new',
            ],
            [
                'navigation_menu_id'    => 27,
                'route_type'            => 'details',
                'view_file'             => 'pages.product-category.details',
                'js_file'               => 'product-category/details',
            ],
            [
                'navigation_menu_id'    => 27,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Stock Adjustment Reason
            [
                'navigation_menu_id'    => 28,
                'route_type'            => 'index',
                'view_file'             => 'pages.stock-adjustment-reason.index',
                'js_file'               => 'stock-adjustment-reason/index',
            ],
            [
                'navigation_menu_id'    => 28,
                'route_type'            => 'new',
                'view_file'             => 'pages.stock-adjustment-reason.new',
                'js_file'               => 'stock-adjustment-reason/new',
            ],
            [
                'navigation_menu_id'    => 28,
                'route_type'            => 'details',
                'view_file'             => 'pages.stock-adjustment-reason.details',
                'js_file'               => 'stock-adjustment-reason/details',
            ],
            [
                'navigation_menu_id'    => 28,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Suppliers
            [
                'navigation_menu_id'    => 29,
                'route_type'            => 'index',
                'view_file'             => 'pages.suppliers.index',
                'js_file'               => 'suppliers/index',
            ],
            [
                'navigation_menu_id'    => 29,
                'route_type'            => 'new',
                'view_file'             => 'pages.suppliers.new',
                'js_file'               => 'suppliers/new',
            ],
            [
                'navigation_menu_id'    => 29,
                'route_type'            => 'details',
                'view_file'             => 'pages.suppliers.details',
                'js_file'               => 'suppliers/details',
            ],
            [
                'navigation_menu_id'    => 29,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Units
            [
                'navigation_menu_id'    => 30,
                'route_type'            => 'index',
                'view_file'             => 'pages.units.index',
                'js_file'               => 'units/index',
            ],
            [
                'navigation_menu_id'    => 30,
                'route_type'            => 'new',
                'view_file'             => 'pages.units.new',
                'js_file'               => 'units/new',
            ],
            [
                'navigation_menu_id'    => 30,
                'route_type'            => 'details',
                'view_file'             => 'pages.units.details',
                'js_file'               => 'units/details',
            ],
            [
                'navigation_menu_id'    => 30,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Warehouse
            [
                'navigation_menu_id'    => 31,
                'route_type'            => 'index',
                'view_file'             => 'pages.warehouse.index',
                'js_file'               => 'warehouse/index',
            ],
            [
                'navigation_menu_id'    => 31,
                'route_type'            => 'new',
                'view_file'             => 'pages.warehouse.new',
                'js_file'               => 'warehouse/new',
            ],
            [
                'navigation_menu_id'    => 31,
                'route_type'            => 'details',
                'view_file'             => 'pages.warehouse.details',
                'js_file'               => 'warehouse/details',
            ],
            [
                'navigation_menu_id'    => 31,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],

            // Warehouse Type
            [
                'navigation_menu_id'    => 32,
                'route_type'            => 'index',
                'view_file'             => 'pages.warehouse-type.index',
                'js_file'               => 'warehouse-type/index',
            ],
            [
                'navigation_menu_id'    => 32,
                'route_type'            => 'new',
                'view_file'             => 'pages.warehouse-type.new',
                'js_file'               => 'warehouse-type/new',
            ],
            [
                'navigation_menu_id'    => 32,
                'route_type'            => 'details',
                'view_file'             => 'pages.warehouse-type.details',
                'js_file'               => 'warehouse-type/details',
            ],
            [
                'navigation_menu_id'    => 32,
                'route_type'            => 'import',
                'view_file'             => 'pages.import.index',
                'js_file'               => 'import/import',
            ],
        ];

        DB::table('navigation_menu_route')->insert(
            array_map(fn ($row) => $row + $defaults, $navigationMenuRoutes)
        );
    }
}
