<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockTransferReasonSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'created_at' => now(),
            'updated_at' => now(),
        ];

       $stockTransferReason = [
            ['stock_transfer_reason_name' => 'Stock Count Variance'],
            ['stock_transfer_reason_name' => 'Rebalancing Stock Levels'],
            ['stock_transfer_reason_name' => 'Overstock Redistribution'],
            ['stock_transfer_reason_name' => 'Understock Replenishment'],
            ['stock_transfer_reason_name' => 'Damaged Goods Segregation'],
            ['stock_transfer_reason_name' => 'Expired/Near Expiry Handling'],
            ['stock_transfer_reason_name' => 'Quality Control Inspection'],
            ['stock_transfer_reason_name' => 'Returns Processing'],
            ['stock_transfer_reason_name' => 'Customer Order Fulfillment'],
            ['stock_transfer_reason_name' => 'Inter-Branch Transfer'],
            ['stock_transfer_reason_name' => 'Warehouse Reorganization'],
            ['stock_transfer_reason_name' => 'Bin Location Optimization'],
            ['stock_transfer_reason_name' => 'Production Supply Transfer'],
            ['stock_transfer_reason_name' => 'Finished Goods Allocation'],
            ['stock_transfer_reason_name' => 'Stock Reservation Allocation'],
            ['stock_transfer_reason_name' => 'Stock Adjustment Correction'],
            ['stock_transfer_reason_name' => 'Cycle Count Adjustment'],
            ['stock_transfer_reason_name' => 'Theft/Loss Adjustment'],
            ['stock_transfer_reason_name' => 'Promotional Stock Allocation'],
            ['stock_transfer_reason_name' => 'Seasonal Stock Movement'],
            ['stock_transfer_reason_name' => 'Consignment Stock Transfer'],
            ['stock_transfer_reason_name' => 'Internal Use Allocation'],
            ['stock_transfer_reason_name' => 'Repair/Maintenance Transfer'],
            ['stock_transfer_reason_name' => 'System Data Correction'],
            ['stock_transfer_reason_name' => 'Stock Consolidation'],
            ['stock_transfer_reason_name' => 'Other'],
        ];

        DB::table('stock_transfer_reason')->insert(
            array_map(fn ($row) => $row + $defaults, $stockTransferReason)
        );
    }
}
