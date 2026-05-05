<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByProduct = $request->input('filter_by_product');
        $filterByWarehouse = $request->input('filter_by_warehouse');
        $filterByMovementDate = $request->input('filter_by_movement_date');
        $filterByMovementType = $request->input('filter_by_movement_type');

        $parseRange = function ($range) {
            if (!$range) return null;

            $dates = explode(' - ', $range);

            if (count($dates) !== 2) return null;

            return [
                Carbon::createFromFormat('m/d/Y', trim($dates[0]))->startOfDay(),
                Carbon::createFromFormat('m/d/Y', trim($dates[1]))->endOfDay(),
            ];
        };

        $movementDateRange = $parseRange($filterByMovementDate);

        $stockMovements = DB::table('stock_movement')
            ->when(!empty($filterByProduct), fn($q) =>
                $q->whereIn('product_id', (array) $filterByProduct)
            )
            ->when(!empty($filterByWarehouse), fn($q) =>
                $q->whereIn('warehouse_id', (array) $filterByWarehouse)
            )
            ->when($movementDateRange, fn($q) =>
                $q->whereBetween('created_at', $movementDateRange)
            )
            ->when(!empty($filterByMovementType), fn($q) =>
                $q->whereIn('movement_type', (array) $filterByMovementType)
            )
            ->orderBy('product_name')
            ->get();

        $response = $stockMovements->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockMovementId = $row->id;
            $productName = $row->product_name;
            $warehouseName = $row->warehouse_name;
            $movementType = $row->movement_type;
            $quantity = $row->quantity;
            $referenceType = $row->reference_type;
            $referenceNumber = $row->reference_number;
            $createdAt = $row->created_at;
            $remarks = $row->remarks;

            $movementDate = Carbon::parse($createdAt);

            $formattedDate = $movementDate->format('M d, Y H:i:s a');
           
            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stockMovementId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stockMovementId.'">
                    </div>
                ',
                'PRODUCT' => '<div class="d-flex flex-column">
                                <span class="fw-bold">'.$productName.'</span>
                                <small class="text-muted">'.$warehouseName.'</small>
                            </div>',
                'MOVEMENT_TYPE' => $this->getMovementBadge($movementType),
                'QUANTITY' => $this->formatQuantity($movementType, $quantity),
                'REFERENCE_NO' => '<div class="d-flex flex-column">
                                        <span class="fw-semibold">'.$referenceNumber.'</span>
                                        <small class="text-muted">'.$referenceType.'</small>
                                    </div>',
                'MOVEMENT_DATE' => '<div class="d-flex flex-column">
                                        <span>'.$movementDate->format('M d, Y').'</span>
                                        <small class="text-muted">'.$movementDate->format('h:i A').'</small>
                                    </div>',
                'REMARKS' => $remarks,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }

    private function getMovementBadge($type)
    {
        $map = [
            'IN' => ['class' => 'badge-light-success', 'icon' => 'ki-black-down', 'label' => 'Stock In'],
            'OUT' => ['class' => 'badge-light-danger', 'icon' => 'ki-black-up', 'label' => 'Stock Out'],
            'TRANSFER_IN' => ['class' => 'badge-light-primary', 'icon' => 'ki-arrow-circle-left', 'label' => 'Transfer In'],
            'TRANSFER_OUT' => ['class' => 'badge-light-warning', 'icon' => 'ki-arrow-circle-right', 'label' => 'Transfer Out'],
            'ADJUSTMENT' => ['class' => 'badge-light-info', 'icon' => 'ki-setting-4', 'label' => 'Adjustment'],
            'SALE' => ['class' => 'badge-light-danger', 'icon' => 'ki-handcart', 'label' => 'Sale'],
            'RETURN' => ['class' => 'badge-light-success', 'icon' => 'ki-arrows-circle', 'label' => 'Return'],
        ];

        $item = $map[$type] ?? ['class' => 'badge-light-secondary', 'icon' => 'bi-question', 'label' => $type];

        return '<span class="badge '.$item['class'].'">
                    <i class="ki-outline '.$item['icon'].' me-1"></i>'.$item['label'].'
                </span>';
    }

    private function formatQuantity($type, $qty)
    {
        $isPositive = in_array($type, ['IN', 'TRANSFER_IN', 'RETURN']);

        $class = $isPositive ? 'text-success fw-bold' : 'text-danger fw-bold';
        $sign = $isPositive ? '+' : '-';

        return '<span class="'.$class.'">'.$sign. ' ' .number_format($qty, 2).'</span>';
    }
}
