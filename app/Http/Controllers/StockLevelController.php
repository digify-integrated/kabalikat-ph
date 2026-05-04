<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockLevelController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_level_id' => ['nullable', 'integer'],
            'product_id' => ['required', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric'],
            'cost_per_unit' => ['required', 'numeric', 'min:0.01'],
            'expiration_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $productId = (int) $validated['product_id'];
        $warehouseId = (int) $validated['warehouse_id'];

        $validated['expiration_date'] = !empty($validated['expiration_date'])
            ? Carbon::parse($validated['expiration_date'])->format('Y-m-d')
            : null;

        $validated['received_date'] = !empty($validated['received_date'])
            ? Carbon::parse($validated['received_date'])->format('Y-m-d')
            : null;

        $product = Product::query()
            ->whereKey($productId)
            ->first(['product_name', 'reorder_level']);

        $productName = (string) $product->product_name;
        $reorderLevel = $product->reorder_level;

        $warehouseName = (string) Warehouse::query()
            ->whereKey($warehouseId)
            ->value('warehouse_name');

        $stockStatus = match (true) {
            $validated['quantity'] == 0 => 'Out of Stock',
            $validated['quantity'] <= $reorderLevel => 'Low Stock',
            default => 'In Stock',
        };

        $payload = [
            'product_id' => $productId,
            'product_name' => $productName,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'stock_status' => $stockStatus,
            'quantity' => $validated['quantity'],
            'cost_per_unit' => $validated['cost_per_unit'],
            'expiration_date' => $validated['expiration_date'],
            'received_date' => $validated['received_date'],
            'last_log_by' => Auth::id(),
        ];

        $stockLevelId = $validated['stock_level_id'] ?? null;

        if ($stockLevelId && StockLevel::query()->whereKey($stockLevelId)->exists()) {
            $stockLevel = StockLevel::query()->findOrFail($stockLevelId);
            $stockLevel->update($payload);
        } else {
            $stockLevel = StockLevel::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $stockLevel->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock level has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_level', 'id')],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('detailId') ?? 'Validation failed',
            ]);
        }

        $detailId = (int) $validator->validated()['detailId'];

        DB::transaction(function () use ($detailId) {
            $stockLevel = StockLevel::query()->select(['id'])->findOrFail($detailId);

            $stockLevel->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock level has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_level', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            StockLevel::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected stock levels have been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'notExist' => false,
                'message' => $validator->errors()->first('detailId') ?? 'Validation failed',
            ]);
        }

        $validated = $validator->validated();

        $stockLevel = DB::table('stock_level')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$stockLevel) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Batch tracking not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'productId' => $stockLevel->product_id ?? null,
            'warehouseId' => $stockLevel->warehouse_id ?? null,
            'quantity' => $stockLevel->quantity ?? 0.01,
            'costPerUnit' => $stockLevel->cost_per_unit ?? 0.01,
            'expirationDate' => $stockLevel->expiration_date
            ? date('M d, Y', strtotime($stockLevel->expiration_date))
            : '',
            'receivedDate' => $stockLevel->received_date
            ? date('M d, Y', strtotime($stockLevel->received_date))
            : '',
        ]);
    }

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
            ->join('inventory_lot', 'stock_level.inventory_lot_id', '=', 'inventory_lot.id')
            ->when(!empty($filterByProduct), fn($q) =>
                $q->whereIn('stock_level.product_id', (array) $filterByProduct)
            )
            ->when(!empty($filterByWarehouse), fn($q) =>
                $q->whereIn('stock_level.warehouse_id', (array) $filterByWarehouse)
            )
            ->when($expirationRange, fn($q) =>
                $q->whereBetween('inventory_lot.expiration_date', $expirationRange)
            )
            ->when($receivedRange, fn($q) =>
                $q->whereBetween('inventory_lot.received_date', $receivedRange)
            )
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('stock_level.stock_status', (array) $filterByStatus)
            )
            ->orderBy('stock_level.product_name')
            ->get();

        $response = $stockLevels->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockLevelId = $row->id;
            $productName = $row->product_name;
            $warehouseName = $row->warehouse_name;
            $stockStatus = $row->stock_status;
            $quantity = $row->quantity;
            $inventoryLotId = $row->inventory_lot_id;

            $inventoryLot = InventoryLot::whereKey($inventoryLotId)
            ->select(['batch_number', 'expiration_date', 'received_date', 'cost_per_unit'])
            ->first();

            $batchNumber = $inventoryLot->batch_number;
            $costPerUnit = $inventoryLot->cost_per_unit;
            $expirationDate = $inventoryLot->expiration_date
                ? date('M d, Y', strtotime($inventoryLot->expiration_date))
                : null;
            $receivedDate = $inventoryLot->received_date
                ? date('M d, Y', strtotime($inventoryLot->received_date))
                : 'No received date';

            $statusClass = match ($stockStatus) {
                'Out of Stock' => 'badge badge-danger',
                'Low Stock' => 'badge badge-warning',
                'In Stock' => 'badge badge-success',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$stockStatus.'</span>';

           if (!empty($expirationDate)) {
                $expDate = Carbon::parse($expirationDate);
                $today = Carbon::today();

                $formattedDate = $expDate->format('M d, Y');

                if ($expDate->isPast()) {
                    $daysExpired = $expDate->diffInDays($today);
                    $expirationDate = '
                        '.$formattedDate.'<br>
                        <small class="text-danger">(Expired '.$daysExpired.' day'.($daysExpired > 1 ? 's' : '').' ago)</small>
                    ';
                } elseif ($expDate->isFuture()) {
                    $daysRemaining = $today->diffInDays($expDate);
                    $expirationDate = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expiring in '.$daysRemaining.' day'.($daysRemaining > 1 ? 's' : '').')</small>
                    ';
                } else {
                    $expirationDate = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expires today)</small>
                    ';
                }
            } else {
                $expirationDate = 'No expiry';
            }

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stockLevelId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stockLevelId.'">
                    </div>
                ',
                'PRODUCT' => $productName,
                'WAREHOUSE' => $warehouseName,
                'BATCH_NUMBER' => $batchNumber,
                'QUANTITY' => number_format($quantity, 2),
                'COST_PER_UNIT' => number_format($costPerUnit, 2),
                'STOCK_VALUE' => number_format(($quantity * $costPerUnit), 2),
                'STATUS' => $statusBadge,
                'EXPIRATION_DATE' => $expirationDate,
                'RECEIVED_DATE' => $receivedDate,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }

    public function generateOptions(Request $request)
    {
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $stockLevels = DB::table('stock_level')
            ->select(['id', 'product_name', 'warehouse_name', 'quantity'])
            ->orderBy('product_name')
            ->get();

        $response = $response->concat(
            $stockLevels->map(fn ($row) => [
                'id'   => $row->id,
                'text' => "
                            <div>
                                <strong>{$row->product_name}</strong><br/>
                                <small>Warehouse: {$row->warehouse_name} </small><br/>
                                <small>Quantity: " . number_format($row->quantity, 2) . "</small>
                            </div>
                        ",
            ])
        )->values();

        return response()->json($response);
    }

    public function generateWarehouseOptions(Request $request)
    {
        $warehouseID = $request->input('warehouse_id', false);
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $stockLevels = DB::table('stock_level')
            ->select(['id', 'product_name', 'warehouse_name', 'quantity'])
            ->where('warehouse_id', $warehouseID)
            ->where('quantity', '>', 0)
            ->orderBy('product_name')
            ->get();

        $response = $response->concat(
            $stockLevels->map(fn ($row) => [
                'id'   => $row->id,
                'text' => "
                            <div>
                                <strong>{$row->product_name}</strong><br/>
                                <small>Warehouse: {$row->warehouse_name} </small><br/>
                                <small>Quantity: " . number_format($row->quantity, 2) . "</small>
                            </div>
                        ",
            ])
        )->values();

        return response()->json($response);
    }
}
