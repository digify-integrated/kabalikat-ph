<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItems;
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
            'ordered_quantity' => ['required', 'string', 'min: 0.01'],
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
                $onProcessButton = '<button class="btn btn-icon btn-light btn-active-light-success receive-purchase-order-items" data-bs-toggle="modal" data-bs-target="#receive-purchase-order-items-modal" data-reference-id="' . $purchaseOrderItemsId . '" title="Receive Item">
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
