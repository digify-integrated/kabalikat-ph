<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => ['nullable', 'integer'],
            'reference_number' => ['required', 'string'],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouse', 'id')],
            'remarks' => ['nullable', 'string'],
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

        $warehouseId = (int) $validated['warehouse_id'];

        $warehouseName = (string) Warehouse::query()
            ->whereKey($warehouseId)
            ->value('warehouse_name');

        $payload = [
            'reference_number' => $validated['reference_number'],
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'remarks' => $validated['remarks'],
            'last_log_by' => Auth::id(),
        ];

        $purchaseOrderId = $validated['purchase_order_id'] ?? null;

        if ($purchaseOrderId && PurchaseOrder::query()->whereKey($purchaseOrderId)->exists()) {
            $purchaseOrder = PurchaseOrder::query()->findOrFail($purchaseOrderId);
            $purchaseOrder->update($payload);
        } else {
            $purchaseOrder = PurchaseOrder::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $purchaseOrder->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The purchase order has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function forApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('purchase_order', 'id')],
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
            $purchaseOrder = PurchaseOrder::query()
            ->select(['id', 'purchase_order_status'])
            ->findOrFail($detailId);

            if ($purchaseOrder->purchase_order_status !== 'Draft') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not "Draft" status',
                ]);
            }

            $purchaseOrder->update([
                'purchase_order_status' => 'For Approval',
                'for_approval_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The purchase order has been submitted for approval successfully',
            'redirect_link' => $link,
        ]);
    }

    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('purchase_order', 'id')],
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
            $purchaseOrder = PurchaseOrder::query()
                ->select(['id', 'purchase_order_status'])
                ->findOrFail($detailId);

            if ($purchaseOrder->purchase_order_status !== 'For Approval' && $purchaseOrder->purchase_order_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not "For Approval" or "Draft" status',
                ]);
            }

            $purchaseOrder->update([
                'purchase_order_status' => 'Cancelled',
                'cancellation_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The purchase order has been cancelled successfully',
            'redirect_link' => $link,
        ]);
    }

    public function setToDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('purchase_order', 'id')],
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
            $purchaseOrder = PurchaseOrder::query()
                ->select(['id', 'purchase_order_status'])
                ->findOrFail($detailId);

            if ($purchaseOrder->purchase_order_status !== 'For Approval') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not in "For Approval" status',
                ]);
            }

            $purchaseOrder->update([
                'purchase_order_status' => 'Draft',
                'set_to_draft_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The purchase order has been set to draft successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', Rule::exists('purchase_order', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $detailId = (int) $validator->validated()['detailId'];

        $batch = PurchaseOrder::with(['items.product', 'warehouse'])
            ->findOrFail($detailId);

        if ($batch->purchase_order_status !== 'For Approval') {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order is not in For Approval status'
            ]);
        }

        DB::transaction(function () use ($batch) {

            $batch->update([
                'purchase_order_status' => 'Approved',
                'approved_date' => now(),
            ]);

            $warehouseId   = $batch->warehouse_id;
            $warehouseName = $batch->warehouse->warehouse_name ?? null;

            foreach ($batch->items as $item) {

                $productId   = $item->product_id;
                $productName = $item->product->product_name ?? $item->product_name;
                $reorderLevel = $item->product->reorder_level ?? 0;

                $lot = InventoryLot::firstOrCreate(
                    [
                        'product_id'      => $productId,
                        'batch_number'    => $item->batch_number,
                        'cost_per_unit'   => $item->cost_per_unit,
                        'expiration_date' => $item->expiration_date,
                    ],
                    [
                        'product_name'  => $productName,
                        'received_date' => $item->received_date,
                        'last_log_by'   => auth()->id(),
                    ]
                );

                $stock = StockLevel::firstOrNew([
                    'product_id'       => $productId,
                    'warehouse_id'     => $warehouseId,
                    'inventory_lot_id' => $lot->id,
                ]);

                $stock->product_name   = $productName;
                $stock->warehouse_name = $warehouseName;
                $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                $stock->last_log_by = auth()->id();

                $stock->stock_status = match (true) {
                    $stock->quantity == 0 => 'Out of Stock',
                    $stock->quantity <= $reorderLevel => 'Low Stock',
                    default => 'In Stock',
                };

                $stock->save();

                StockMovement::create([
                    'product_id'        => $productId,
                    'product_name'      => $productName,
                    'warehouse_id'      => $warehouseId,
                    'warehouse_name'    => $warehouseName,
                    'inventory_lot_id'  => $lot->id,
                    'movement_type'     => 'IN',
                    'quantity'          => $item->quantity,
                    'reference_type'    => 'Purchase Order',
                    'reference_number'  => $batch->reference_number,
                    'remarks'           => 'Stock received via batch approval',
                    'last_log_by'       => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved successfully',
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('purchase_order', 'id')],
        ]);

        DB::transaction(function () use ($validated) {

            $batches = PurchaseOrder::query()
                ->with(['items.product', 'warehouse'])
                ->whereIn('id', $validated['selected_id'])
                ->where('purchase_order_status', 'For Approval')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                $batch->update([
                    'purchase_order_status' => 'Approved',
                    'approved_date' => now(),
                ]);

                $warehouseId   = $batch->warehouse_id;
                $warehouseName = $batch->warehouse->warehouse_name ?? null;

                foreach ($batch->items as $item) {

                    $productId   = $item->product_id;
                    $productName = $item->product->product_name ?? $item->product_name;
                    $reorderLevel = $item->product->reorder_level ?? 0;

                    $lot = InventoryLot::firstOrCreate(
                        [
                            'product_id'      => $productId,
                            'batch_number'    => $item->batch_number,
                            'cost_per_unit'   => $item->cost_per_unit,
                            'expiration_date' => $item->expiration_date,
                        ],
                        [
                            'product_name'  => $productName,
                            'received_date' => $item->received_date,
                            'last_log_by'   => auth()->id(),
                        ]
                    );

                    $stock = StockLevel::firstOrNew([
                        'product_id'       => $productId,
                        'warehouse_id'     => $warehouseId,
                        'inventory_lot_id' => $lot->id,
                    ]);

                    $stock->product_name   = $productName;
                    $stock->warehouse_name = $warehouseName;
                    $stock->quantity = ($stock->quantity ?? 0) + $item->quantity;
                    $stock->last_log_by = auth()->id();

                    $stock->stock_status = match (true) {
                        $stock->quantity == 0 => 'Out of Stock',
                        $stock->quantity <= $reorderLevel => 'Low Stock',
                        default => 'In Stock',
                    };

                    $stock->save();

                    StockMovement::create([
                        'product_id'        => $productId,
                        'product_name'      => $productName,
                        'warehouse_id'      => $warehouseId,
                        'warehouse_name'    => $warehouseName,
                        'inventory_lot_id'  => $lot->id,
                        'movement_type'     => 'IN',
                        'quantity'          => $item->quantity,
                        'reference_type'    => 'Purchase Order',
                        'reference_number'  => $batch->reference_number,
                        'remarks'           => 'Stock received via batch approval',
                        'last_log_by'       => auth()->id(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Selected purchase orderes have been approved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('purchase_order', 'id')],
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
            $purchaseOrder = PurchaseOrder::query()->select(['id'])->findOrFail($detailId);

            $purchaseOrder->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The purchase order has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('purchase_order', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            PurchaseOrder::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected purchase orders have been deleted successfully',
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

        $purchaseOrder = DB::table('purchase_order')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$purchaseOrder) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Purchase order not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'referenceNumber' => $purchaseOrder->reference_number ?? null,
            'warehouseId' => $purchaseOrder->warehouse_id ?? null,
            'remarks' => $purchaseOrder->remarks ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterBySupplier = $request->input('filter_by_supplier');
        $filterByWarehouse = $request->input('filter_by_warehouse');
        $filterByOrderDate = $request->input('filter_by_order_date');
        $filterByExpectedDeliveryDate = $request->input('filter_by_expected_delivery_date');
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

        $orderRange = $parseRange($filterByOrderDate);
        $deliveryRange = $parseRange($filterByExpectedDeliveryDate);

        $stockLevels = DB::table('purchase_order')
            ->when(!empty($filterByProduct), fn($q) =>
                $q->whereIn('supplier_id', (array) $filterBySupplier)
            )
            ->when(!empty($filterByWarehouse), fn($q) =>
                $q->whereIn('warehouse_id', (array) $filterByWarehouse)
            )
            ->when($orderRange, fn($q) =>
                $q->whereBetween('order_date', $orderRange)
            )
            ->when($deliveryRange, fn($q) =>
                $q->whereBetween('expected_delivery_date', $deliveryRange)
            )
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('po_status', (array) $filterByStatus)
            )
            ->orderBy('reference_number')
            ->get();

        $response = $stockLevels->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockLevelId = $row->id;
            $referenceNumber = $row->reference_number;
            $supplierName = $row->supplier_name;
            $warehouseName = $row->warehouse_name;
            $poStatus = $row->po_status;

            $orderDate = $row->order_date
                ? date('M d, Y', strtotime($row->expiration_date))
                : null;
            $expectedDeliveryDate = $row->expected_delivery_date
                ? date('M d, Y', strtotime($row->received_date))
                : 'No received date';

            $statusClass = match ($poStatus) {
                'Draft' => 'badge badge-secondary',
                'For Approval' => 'badge badge-warning',
                'Approved' => 'badge badge-success',
                'On-Process' => 'badge badge-warning',
                'Complete' => 'badge badge-success',
                'Cancelled' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$poStatus.'</span>';

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
                'REFERENCE_NUMBER' => $referenceNumber,
                'SUPPLIER' => $supplierName,
                'WAREHOUSE' => $warehouseName,
                'STATUS' => $statusBadge,
                'ORDER_DATE' => $orderDate,
                'EXPECTED_DELIVERY_DATE' => $expectedDeliveryDate,
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

        $purchaseOrders = DB::table('purchase_order')
            ->select(['id', 'purchase_order_name', 'purchase_order'])
            ->orderBy('purchase_order_name')
            ->get();

        $response = $response->concat(
            $purchaseOrders->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->purchase_order_name . ' (.' . $row->purchase_order . ')',
            ])
        )->values();

        return response()->json($response);
    }
}
