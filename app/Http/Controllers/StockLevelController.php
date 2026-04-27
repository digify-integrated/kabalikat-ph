<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockLevelController extends Controller
{
    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByProduct = $request->input('filter_by_product');
        $filterByWarehouse = $request->input('filter_by_warehouse');
        $filterByExpirationDate = $request->input('filter_by_expiration_date');
        $filterByReceivedDate = $request->input('filter_by_received_date');
        $filterByStatus = $request->input('filter_by_status');

        $parseRange = function ($range) {
            if (!$range) return null;

            $dates = explode(' - ', $range);

            if (count($dates) !== 2) return null;

            return [
                Carbon::createFromFormat('m/d/Y', trim($dates[0]))->startOfDay(),
                Carbon::createFromFormat('m/d/Y', trim($dates[1]))->endOfDay(),
            ];
        };

        $expirationRange = $parseRange($filterByExpirationDate);
        $receivedRange = $parseRange($filterByReceivedDate);

        $stockLevels = DB::table('stock_level')
            ->when(!empty($filterByProduct), fn($q) =>
                $q->whereIn('product_id', (array) $filterByProduct)
            )
            ->when(!empty($filterByWarehouse), fn($q) =>
                $q->whereIn('warehouse_id', (array) $filterByWarehouse)
            )
            ->when($expirationRange, fn($q) =>
                $q->whereBetween('expiration_date', $expirationRange)
            )
            ->when($receivedRange, fn($q) =>
                $q->whereBetween('received_date', $receivedRange)
            )
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('batch_status', (array) $filterByStatus)
            )
            ->orderBy('product_name')
            ->get();

        $response = $stockLevels->map(function ($row) {
            $productName = $row->product_name;
            $warehouseName = $row->warehouse_name;
            $stockStatus = $row->stock_status;
            $quantity = $row->quantity;
            $batchNumber = $row->batch_number;
            $costPerUnit = $row->cost_per_unit;
            $expiration_date = $row->expiration_date
            ? date('M d, Y', strtotime($row->expiration_date))
            : 'No expiry';
            $received_date = $row->received_date
            ? date('M d, Y', strtotime($row->received_date))
            : 'No received date';

            $statusClass = match ($stockStatus) {
                'Low Stock' => 'badge badge-warning',
                'In Stock' => 'badge badge-success',
                'Out of Stock' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$stockStatus.'</span>';

           if ($row->expiration_date) {
                $expDate = Carbon::parse($row->expiration_date);
                $today = Carbon::today();

                $formattedDate = $expDate->format('M d, Y');

                if ($expDate->isPast()) {
                    $daysExpired = $expDate->diffInDays($today);
                    $expiration_date = '
                        '.$formattedDate.'<br>
                        <small class="text-danger">(Expired '.$daysExpired.' day'.($daysExpired > 1 ? 's' : '').' ago)</small>
                    ';
                } elseif ($expDate->isFuture()) {
                    $daysRemaining = $today->diffInDays($expDate);
                    $expiration_date = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expiring in '.$daysRemaining.' day'.($daysRemaining > 1 ? 's' : '').')</small>
                    ';
                } else {
                    $expiration_date = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expires today)</small>
                    ';
                }
            } else {
                $expiration_date = 'No expiry';
            }

            return [
                'PRODUCT' => $productName,
                'WAREHOUSE' => $warehouseName,
                'QUANTITY' => number_format($quantity, 2),
                'COST_PER_UNIT' => number_format($costPerUnit, 2),
                'TOTAL_VALUE' => number_format(($quantity * $costPerUnit), 2),
                'BATCH_NUMBER' => $batchNumber,
                'RECEIVED_DATE' => $received_date,
                'EXPIRATION_DATE' => $expiration_date,
                'STATUS' => $statusBadge,
            ];
        })->values();

        return response()->json($response);
    }
}
