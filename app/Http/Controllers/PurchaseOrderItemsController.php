<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderCancellations;
use App\Models\PurchaseOrderItems;
use App\Models\PurchaseOrderReceiptItems;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderItemsController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_items_id' => ['nullable', 'integer'],
            'purchase_order_id' => ['required', 'integer', Rule::exists('purchase_order', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('product', 'id')],
            'ordered_quantity' => ['required', 'numeric', 'min: 0.01'],
            'estimated_cost' => ['required', 'numeric', 'min: 0.01'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $productId = $validated['product_id'] ?? null;

        $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

        $payload = [
            'purchase_order_id' => $validated['purchase_order_id'],
            'product_id' => $productId,
            'product_name' => $productName,
            'ordered_quantity' => $validated['ordered_quantity'],
            'remaining_quantity' => $validated['ordered_quantity'],
            'estimated_cost' => $validated['estimated_cost'],
            'last_log_by' => Auth::id(),
        ];

        $purchaseOrderItemsId = $validated['purchase_order_items_id'] ?? null;

        if ($purchaseOrderItemsId && PurchaseOrderItems::query()->whereKey($purchaseOrderItemsId)->exists()) {
            $purchaseOrderItems = PurchaseOrderItems::query()->findOrFail($purchaseOrderItemsId);
            $purchaseOrderItems->update($payload);
        } else {
            $purchaseOrderItems = PurchaseOrderItems::query()->create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'The purchase order item has been saved successfully',
        ]);
    }

    public function receive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_items_id_receive' => ['required', 'integer', Rule::exists('purchase_order_items', 'id')],
            'batch_number' => ['nullable', 'string'],
            'received_quantity' => ['required', 'numeric', 'min:0.01'],
            'cost_per_unit' => ['required', 'numeric', 'min:0.01'],
            'expiration_date' => ['nullable', 'date'],
            'received_date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($validated) {

            $purchaseOrderItem = PurchaseOrderItems::with([
                'purchaseOrder',
                'product'
            ])
            ->lockForUpdate()
            ->findOrFail($validated['purchase_order_items_id_receive']);

            $receivedQty = (float) $validated['received_quantity'];

            /*
            |--------------------------------------------------------------------------
            | Validate Remaining Quantity
            |--------------------------------------------------------------------------
            */

            if ($receivedQty > $purchaseOrderItem->remaining_quantity) {

                return response()->json([
                    'success' => false,
                    'message' => 'Received quantity exceeds remaining quantity'
                ]);
            }

            $purchaseOrder = $purchaseOrderItem->purchaseOrder;

            $warehouseId = $purchaseOrder->warehouse_id;
            $warehouseName = $purchaseOrder->warehouse_name;

            $productId = $purchaseOrderItem->product_id;
            $productName = $purchaseOrderItem->product_name;

            $referenceNumber = $purchaseOrder->reference_number;

            /*
            |--------------------------------------------------------------------------
            | Create Inventory Lot
            |--------------------------------------------------------------------------
            */

            $lot = InventoryLot::firstOrCreate(
                [
                    'product_id'      => $productId,
                    'batch_number'    => $validated['batch_number'] ?? null,
                    'cost_per_unit'   => $validated['cost_per_unit'],
                    'expiration_date' => $validated['expiration_date'] ?? null,
                ],
                [
                    'product_name'  => $productName,
                    'received_date' => $validated['received_date'],
                    'last_log_by'   => Auth::id(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Create / Update Stock Level
            |--------------------------------------------------------------------------
            */

            $stock = StockLevel::firstOrNew([
                'warehouse_id'     => $warehouseId,
                'inventory_lot_id' => $lot->id,
            ]);

            $stock->product_id = $productId;
            $stock->product_name = $productName;
            $stock->warehouse_name = $warehouseName;
            $stock->quantity = ($stock->quantity ?? 0) + $receivedQty;
            $stock->last_log_by = Auth::id();

            $reorderLevel = $purchaseOrderItem->product->reorder_level ?? 0;

            $stock->stock_status = match (true) {
                $stock->quantity <= 0 => 'Out of Stock',
                $stock->quantity <= $reorderLevel => 'Low Stock',
                default => 'In Stock',
            };

            $stock->save();

            /*
            |--------------------------------------------------------------------------
            | Create Stock Movement
            |--------------------------------------------------------------------------
            */

            StockMovement::create([
                'product_id'       => $productId,
                'product_name'     => $productName,
                'warehouse_id'     => $warehouseId,
                'warehouse_name'   => $warehouseName,
                'inventory_lot_id' => $lot->id,
                'movement_type'    => 'IN',
                'quantity'         => $receivedQty,
                'reference_type'   => 'Purchase Order',
                'reference_number' => $referenceNumber,
                'remarks'          => 'Stock received via purchase order',
                'last_log_by'      => Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Receipt Record
            |--------------------------------------------------------------------------
            */

            PurchaseOrderReceiptItems::create([
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_order_items_id' => $purchaseOrderItem->id,
                'product_id' => $productId,
                'product_name' => $productName,
                'batch_number' => $validated['batch_number'] ?? null,
                'cost_per_unit' => $validated['cost_per_unit'],
                'expiration_date' => $validated['expiration_date'] ?? null,
                'received_date' => $validated['received_date'],
                'received_quantity' => $receivedQty,
                'last_log_by' => Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Purchase Order Item
            |--------------------------------------------------------------------------
            */

            $newReceivedQty =
                $purchaseOrderItem->received_quantity + $receivedQty;

            $newRemainingQty =
                $purchaseOrderItem->ordered_quantity
                - $newReceivedQty
                - $purchaseOrderItem->cancelled_quantity;

            $purchaseOrderItem->update([
                'received_quantity' => $newReceivedQty,
                'remaining_quantity' => $newRemainingQty,
                'last_log_by' => Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Complete PO If All Remaining = 0
            |--------------------------------------------------------------------------
            */

            $remainingExists = PurchaseOrderItems::where(
                'purchase_order_id',
                $purchaseOrder->id
            )
            ->where('remaining_quantity', '>', 0)
            ->exists();

            if (!$remainingExists) {

                $purchaseOrder->update([
                    'po_status' => 'Completed',
                    'completed_date' => now(),
                    'last_log_by' => Auth::id(),
                ]);

            } else {

                if ($purchaseOrder->po_status === 'Approved') {

                    $purchaseOrder->update([
                        'po_status' => 'On-Process',
                        'on_process_date' => now(),
                        'last_log_by' => Auth::id(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Items received successfully',
            ]);
        });
    }

    public function cancelReceive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_items_id_cancel' => [
                'required',
                'integer',
                Rule::exists('purchase_order_items', 'id')
            ],

            'cancelled_quantity' => [
                'required',
                'numeric',
                'min:0.01'
            ],

            'reason' => [
                'required',
                'string'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($validated) {

            $purchaseOrderItem = PurchaseOrderItems::with('purchaseOrder')
                ->lockForUpdate()
                ->findOrFail($validated['purchase_order_items_id_cancel']);

            $cancelQty = (float) $validated['cancelled_quantity'];

            /*
            |--------------------------------------------------------------------------
            | Validate Remaining Quantity
            |--------------------------------------------------------------------------
            */

            if ($cancelQty > $purchaseOrderItem->remaining_quantity) {

                return response()->json([
                    'success' => false,
                    'message' => 'Cancelled quantity exceeds remaining quantity'
                ]);
            }

            $purchaseOrder = $purchaseOrderItem->purchaseOrder;

            /*
            |--------------------------------------------------------------------------
            | Create Cancellation Record
            |--------------------------------------------------------------------------
            */

            PurchaseOrderCancellations::create([
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_order_items_id' => $purchaseOrderItem->id,
                'product_id' => $purchaseOrderItem->product_id,
                'product_name' => $purchaseOrderItem->product_name,
                'cancelled_quantity' => $cancelQty,
                'reason' => $validated['reason'],
                'cancelled_date' => now(),
                'last_log_by' => Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Purchase Order Item
            |--------------------------------------------------------------------------
            */

            $newCancelledQty =
                $purchaseOrderItem->cancelled_quantity + $cancelQty;

            $newRemainingQty =
                $purchaseOrderItem->ordered_quantity
                - $purchaseOrderItem->received_quantity
                - $newCancelledQty;

            $purchaseOrderItem->update([
                'cancelled_quantity' => $newCancelledQty,
                'remaining_quantity' => $newRemainingQty,
                'last_log_by' => Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Complete PO If All Remaining = 0
            |--------------------------------------------------------------------------
            */

            $remainingExists = PurchaseOrderItems::where(
                'purchase_order_id',
                $purchaseOrder->id
            )
            ->where('remaining_quantity', '>', 0)
            ->exists();

            if (!$remainingExists) {

                $purchaseOrder->update([
                    'po_status' => 'Completed',
                    'completed_date' => now(),
                    'last_log_by' => Auth::id(),
                ]);

            } else {

                if ($purchaseOrder->po_status === 'Approved') {

                    $purchaseOrder->update([
                        'po_status' => 'On-Process',
                        'on_process_date' => now(),
                        'last_log_by' => Auth::id(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Items cancelled successfully',
            ]);
        });
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('purchase_order_items', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $purchaseOrderItems = PurchaseOrderItems::query()->select(['id'])->findOrFail($referenceId);

            $purchaseOrderItems->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The purchase order item has been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('purchase_order_items', 'id')],
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

        $purchaseOrder = DB::table('purchase_order_items')
            ->where('id', $validated['referenceId'])
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
            'productId' => $purchaseOrder->product_id ?? null,
            'orderedQuantity' => $purchaseOrder->ordered_quantity ?? null,
            'estimatedCost' => $purchaseOrder->estimated_cost ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $purchaseOrderId = (int) $request->input('purchase_order_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $purchaseOrderItems = DB::table('purchase_order_items')
        ->where('purchase_order_id', $purchaseOrderId)
        ->orderBy('product_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $purchaseOrderItems->map(function ($row) use ($writeAccess, $logsAccess)  {
            $purchaseOrderItemsId = $row->id;
            $purchaseOrderId = $row->purchase_order_id;
            $productName = $row->product_name;
            $orderedQuantity = $row->ordered_quantity;
            $receivedQuantity = $row->received_quantity;
            $cancelledQuantity = $row->cancelled_quantity;
            $remainingQuantity = $row->remaining_quantity;
            $estimatedCost = $row->estimated_cost;

            $purchaseOrder = PurchaseOrder::query()
            ->whereKey($purchaseOrderId)
            ->first();

            $updateButton = '';
            $deleteButton = '';
            $onProcessButton = '';

            if($writeAccess > 0 && $purchaseOrder->po_status === 'Draft'){
                $updateButton = '<button class="btn btn-icon btn-light btn-active-light-primary update-purchase-order-items" data-bs-toggle="modal" data-bs-target="#purchase-order-items-modal" data-reference-id="' . $purchaseOrderItemsId . '" title="Update Item">
                                    <i class="ki-outline ki-pencil fs-3 m-0 fs-5"></i>
                                </button>';

                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-purchase-order-items" data-reference-id="' . $purchaseOrderItemsId . '" title="Delete Item">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            if($writeAccess > 0 && $purchaseOrder->po_status === 'On-Process' && $remainingQuantity > 0){
                $onProcessButton = '<button class="btn btn-icon btn-light btn-active-light-success receive-purchase-order-items" data-bs-toggle="modal" data-bs-target="#recieve-purchase-order-items-modal" data-reference-id="' . $purchaseOrderItemsId . '" title="Receive Item">
                                    <i class="ki-outline ki-exit-down fs-3 m-0 fs-5"></i>
                                </button>
                                <button class="btn btn-icon btn-light btn-active-light-warning cancel-purchase-order-items" data-bs-toggle="modal" data-bs-target="#cancel-purchase-order-items-modal" data-reference-id="' . $purchaseOrderItemsId . '" title="Cancel Item">
                                    <i class="ki-outline ki-cross fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-purchase-order-items-log-notes" data-reference-id="' . $purchaseOrderItemsId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'PRODUCT' => $productName,
                'ORDERED_QUANTITY' => number_format($orderedQuantity, 2),
                'RECEIVED_QUANTITY' => number_format($receivedQuantity, 2),
                'CANCELLED_QUANTITY' => number_format($cancelledQuantity, 2),
                'REMAINING_QUANTITY' => number_format($remainingQuantity, 2),
                'ESTIMATED_COST' => number_format($estimatedCost, 2),
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $updateButton .'
                                '. $onProcessButton .'
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
