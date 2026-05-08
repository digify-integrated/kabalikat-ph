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
        $outOfStockCount = DB::table('product')

            ->leftJoin('stock_level', function ($join) {

                $join->on(
                    'stock_level.product_id',
                    '=',
                    'product.id'
                )

                ->where('stock_level.quantity', '>', 0);
            })

            ->leftJoin(
                'inventory_lot',
                'inventory_lot.id',
                '=',
                'stock_level.inventory_lot_id'
            )

            ->select('product.id')

            ->groupBy('product.id')

            ->havingRaw("
                SUM(
                    CASE
                        WHEN inventory_lot.expiration_date IS NULL
                            OR inventory_lot.expiration_date >= CURDATE()
                        THEN stock_level.quantity
                        ELSE 0
                    END
                ) <= 0
            ")

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
            ->join(
                'inventory_lot',
                'inventory_lot.id',
                '=',
                'stock_level.inventory_lot_id'
            )
            ->where('stock_level.stock_status', 'Low Stock')
            ->where('stock_level.quantity', '>', 0)
            ->where(function ($query) {
                $query->whereNull('inventory_lot.expiration_date')
                    ->orWhereDate('inventory_lot.expiration_date', '>=', now());
            })
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

    public function generateOutOfStockTable(Request $request)
    {
        $rows = DB::table('product')

            ->leftJoin('stock_level', function ($join) {

                $join->on(
                    'stock_level.product_id',
                    '=',
                    'product.id'
                )

                ->where('stock_level.quantity', '>', 0);
            })

            ->leftJoin(
                'inventory_lot',
                'inventory_lot.id',
                '=',
                'stock_level.inventory_lot_id'
            )

            ->select(
                'product.product_name as PRODUCT'
            )

            ->groupBy(
                'product.id',
                'product.product_name'
            )

            ->havingRaw("
                SUM(
                    CASE
                        WHEN inventory_lot.expiration_date IS NULL
                            OR inventory_lot.expiration_date >= CURDATE()
                        THEN stock_level.quantity
                        ELSE 0
                    END
                ) <= 0
            ")

            ->orderBy('product.product_name')
            ->limit(10)
            ->get();

        return $this->formatTableResponse($rows);
    }

    public function generateExpiredStockTable(Request $request)
    {
        $rows = DB::table('stock_level')

            ->join(
                'inventory_lot',
                'inventory_lot.id',
                '=',
                'stock_level.inventory_lot_id'
            )

            ->select(
                'stock_level.product_name',
                'stock_level.warehouse_name',
                'inventory_lot.batch_number',
                'stock_level.quantity',
                'inventory_lot.expiration_date'
            )

            ->where('stock_level.quantity', '>', 0)

            ->whereNotNull('inventory_lot.expiration_date')

            ->whereDate(
                'inventory_lot.expiration_date',
                '<',
                now()
            )

            ->orderBy('inventory_lot.expiration_date')
            ->limit(10)
            ->get()

            ->map(function ($row) {

                $expirationDate = $row->expiration_date;

                /*
                |--------------------------------------------------------------------------
                | Format Expiration Date
                |--------------------------------------------------------------------------
                */

                if (!empty($expirationDate)) {

                    $expDate = Carbon::parse($expirationDate);
                    $today = Carbon::today();

                    $formattedDate = $expDate->format('M d, Y');

                    if ($expDate->isPast()) {

                        $daysExpired = $expDate->diffInDays($today);

                        $expirationDate = '
                            '.$formattedDate.'<br>
                            <small class="text-danger">
                                (Expired '.$daysExpired.' day'.($daysExpired > 1 ? 's' : '').' ago)
                            </small>
                        ';

                    } elseif ($expDate->isFuture()) {

                        $daysRemaining = $today->diffInDays($expDate);

                        $expirationDate = '
                            '.$formattedDate.'<br>
                            <small class="text-warning">
                                (Expiring in '.$daysRemaining.' day'.($daysRemaining > 1 ? 's' : '').')
                            </small>
                        ';

                    } else {

                        $expirationDate = '
                            '.$formattedDate.'<br>
                            <small class="text-warning">
                                (Expires today)
                            </small>
                        ';
                    }

                } else {

                    $expirationDate = 'No expiry';
                }

                return [
                    'PRODUCT' => '
                        <div class="d-flex flex-column">
                            <span class="fw-bold">'.$row->product_name.'</span>
                            <small class="text-muted">'.$row->warehouse_name.'</small>
                        </div>
                    ',

                    'BATCH_NUMBER' => $row->batch_number ?? '-',

                    'QUANTITY' => number_format($row->quantity, 2),

                    'EXPIRATION_DATE' => $expirationDate,
                ];
            });

        return $this->formatTableResponse($rows);
    }

    public function generateLowStockTable(Request $request)
    {
        $rows = DB::table('stock_level')

            ->join(
                'product',
                'product.id',
                '=',
                'stock_level.product_id'
            )

            ->join(
                'inventory_lot',
                'inventory_lot.id',
                '=',
                'stock_level.inventory_lot_id'
            )

            ->select(
                DB::raw("
                    CONCAT(
                        '<div class=\"d-flex flex-column\">',
                            '<span class=\"fw-bold\">',
                                stock_level.product_name,
                            '</span>',
                            '<small class=\"text-muted\">',
                                stock_level.warehouse_name,
                            '</small>',
                        '</div>'
                    ) as PRODUCT
                "),
                'stock_level.quantity as QUANTITY',
                'product.reorder_level as REORDER_LEVEL'
            )

            ->where('stock_level.stock_status', 'Low Stock')

            ->where('stock_level.quantity', '>', 0)

            ->where(function ($query) {

                $query->whereNull('inventory_lot.expiration_date')

                    ->orWhereDate(
                        'inventory_lot.expiration_date',
                        '>=',
                        now()
                    );
            })

            ->orderBy('stock_level.quantity')
            ->limit(10)
            ->get();

        return $this->formatTableResponse($rows);
    }

    public function generateNearExpiryTable(Request $request)
    {
        $rows = DB::table('stock_level')

            ->join(
                'inventory_lot',
                'inventory_lot.id',
                '=',
                'stock_level.inventory_lot_id'
            )

            ->select(
                'stock_level.product_name',
                'stock_level.warehouse_name',
                'inventory_lot.batch_number',
                'stock_level.quantity',
                'inventory_lot.expiration_date'
            )

            ->where('stock_level.quantity', '>', 0)

            ->whereNotNull('inventory_lot.expiration_date')

            ->whereDate(
                'inventory_lot.expiration_date',
                '>=',
                now()
            )

            ->whereDate(
                'inventory_lot.expiration_date',
                '<=',
                now()->addDays(30)
            )

            ->orderBy('inventory_lot.expiration_date')
            ->limit(10)
            ->get()

            ->map(function ($row) {

                $expirationDate = $row->expiration_date;

                /*
                |--------------------------------------------------------------------------
                | Format Expiration Date
                |--------------------------------------------------------------------------
                */

                if (!empty($expirationDate)) {

                    $expDate = Carbon::parse($expirationDate);
                    $today = Carbon::today();

                    $formattedDate = $expDate->format('M d, Y');

                    if ($expDate->isPast()) {

                        $daysExpired = $expDate->diffInDays($today);

                        $expirationDate = '
                            '.$formattedDate.'<br>
                            <small class="text-danger">
                                (Expired '.$daysExpired.' day'.($daysExpired > 1 ? 's' : '').' ago)
                            </small>
                        ';

                    } elseif ($expDate->isFuture()) {

                        $daysRemaining = $today->diffInDays($expDate);

                        $expirationDate = '
                            '.$formattedDate.'<br>
                            <small class="text-warning">
                                (Expiring in '.$daysRemaining.' day'.($daysRemaining > 1 ? 's' : '').')
                            </small>
                        ';

                    } else {

                        $expirationDate = '
                            '.$formattedDate.'<br>
                            <small class="text-warning">
                                (Expires today)
                            </small>
                        ';
                    }

                } else {

                    $expirationDate = 'No expiry';
                }

                return [
                    'PRODUCT' => '
                        <div class="d-flex flex-column">
                            <span class="fw-bold">'.$row->product_name.'</span>
                            <small class="text-muted">'.$row->warehouse_name.'</small>
                        </div>
                    ',

                    'BATCH_NUMBER' => $row->batch_number ?? '-',

                    'QUANTITY' => number_format($row->quantity, 2),

                    'EXPIRATION_DATE' => $expirationDate,
                ];
            });

        return $this->formatTableResponse($rows);
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

    private function formatTableResponse($rows)
    {
        return response()->json(
            collect($rows)->values()
        );
    }
}
