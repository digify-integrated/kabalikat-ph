<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $warehouseType = [
            ['warehouse_type_name' => 'Distribution Center'],
            ['warehouse_type_name' => 'Fulfillment Center'],
            ['warehouse_type_name' => 'Cold Storage'],
            ['warehouse_type_name' => 'Bulk Storage'],
            ['warehouse_type_name' => 'Transit Warehouse'],
            ['warehouse_type_name' => 'Cross-Docking'],
            ['warehouse_type_name' => 'Raw Materials Warehouse'],
            ['warehouse_type_name' => 'Finished Goods Warehouse'],
            ['warehouse_type_name' => 'Return/Reverse Logistics'],
            ['warehouse_type_name' => 'Bonded Warehouse'],
            ['warehouse_type_name' => 'Retail Warehouse'],
            ['warehouse_type_name' => 'Automated Warehouse'],
        ];

        DB::table('warehouse_type')->insert(
            array_map(fn ($row) => $row + $defaults, $warehouseType)
        );
    }
}
