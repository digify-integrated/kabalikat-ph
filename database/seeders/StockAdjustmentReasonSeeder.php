<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockAdjustmentReasonSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $stockAdjustmentReason = [
            ['stock_adjustment_reason_name' => 'Stock Count Variance'],
            ['stock_adjustment_reason_name' => 'Cycle Count Adjustment'],
            ['stock_adjustment_reason_name' => 'Physical Count Correction'],
            ['stock_adjustment_reason_name' => 'Damaged Goods'],
            ['stock_adjustment_reason_name' => 'Expired Items'],
            ['stock_adjustment_reason_name' => 'Obsolete Inventory'],
            ['stock_adjustment_reason_name' => 'Shrinkage'],
            ['stock_adjustment_reason_name' => 'Theft/Loss'],
            ['stock_adjustment_reason_name' => 'Data Entry Error'],
            ['stock_adjustment_reason_name' => 'System Error Correction'],
            ['stock_adjustment_reason_name' => 'Wrong Item Received'],
            ['stock_adjustment_reason_name' => 'Wrong Item Issued'],
            ['stock_adjustment_reason_name' => 'Supplier Short Shipment'],
            ['stock_adjustment_reason_name' => 'Supplier Over Shipment'],
            ['stock_adjustment_reason_name' => 'Return to Supplier'],
            ['stock_adjustment_reason_name' => 'Customer Return'],
            ['stock_adjustment_reason_name' => 'Sales Return Adjustment'],
            ['stock_adjustment_reason_name' => 'Internal Use'],
            ['stock_adjustment_reason_name' => 'Production Usage'],
            ['stock_adjustment_reason_name' => 'Sample/Testing'],
            ['stock_adjustment_reason_name' => 'Stock Transfer Adjustment'],
            ['stock_adjustment_reason_name' => 'Warehouse Transfer Discrepancy'],
            ['stock_adjustment_reason_name' => 'Stock Write-Off'],
            ['stock_adjustment_reason_name' => 'Inventory Reclassification'],
            ['stock_adjustment_reason_name' => 'Opening Balance Adjustment'],
            ['stock_adjustment_reason_name' => 'Closing Adjustment'],
            ['stock_adjustment_reason_name' => 'Other'],
        ];

        DB::table('stock_adjustment_reason')->insert(
            array_map(fn ($row) => $row + $defaults, $stockAdjustmentReason)
        );
    }
}
