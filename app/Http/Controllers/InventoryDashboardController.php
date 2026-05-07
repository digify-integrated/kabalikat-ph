<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryDashboardController extends Controller
{
    public function fetchDetails(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Out Of Stock Products
        |--------------------------------------------------------------------------
        | Get ACTIVE products where ALL stock_level quantities are 0
        */
        $outOfStockCount = DB::table('stock_level')
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) = 0')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 2. Expired Items
        |--------------------------------------------------------------------------
        | expiration_date already expired
        | quantity > 0
        */
        $expiredItemsCount = DB::table('stock_level')
            ->join('inventory_lot', 'stock_level.inventory_lot_id', '=', 'inventory_lot.id')
            ->whereDate('inventory_lot.expiration_date', '<', Carbon::today())
            ->where('stock_level.quantity', '>', 0)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3. Low Stock
        |--------------------------------------------------------------------------
        */
        $lowStockCount = DB::table('stock_level')
            ->where('stock_status', 'Low Stock')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 4. Expiring Soon
        |--------------------------------------------------------------------------
        | expiring within next 30 days
        | quantity > 0
        */
        $expiringSoonCount = DB::table('stock_level')
            ->join('inventory_lot', 'stock_level.inventory_lot_id', '=', 'inventory_lot.id')
            ->whereBetween('inventory_lot.expiration_date', [
                Carbon::today(),
                Carbon::today()->addDays(30)
            ])
            ->where('stock_level.quantity', '>', 0)
            ->count();

        return response()->json([
            'success' => true,
            'notExist' => false,

            'outOfStockCount' => $this->formatCount($outOfStockCount),
            'expiredItemsCount' => $this->formatCount($expiredItemsCount),
            'lowStockCount' => $this->formatCount($lowStockCount),
            'expiringSoonCount' => $this->formatCount($expiringSoonCount),
        ]);
    }

    private function formatCount($count)
    {
        if ($count >= 1000000) {
            return round($count / 1000000, 1) . 'M';
        }

        if ($count >= 1000) {
            return round($count / 1000, 1) . 'k';
        }

        return (string) $count;
    }
}
