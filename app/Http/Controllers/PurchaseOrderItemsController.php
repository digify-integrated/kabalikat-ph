<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItems;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderItemsController extends Controller
{
   public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => ['required', 'integer', Rule::exists('purchase_order', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('product', 'id')],
            'batch_number' => ['required', 'string'],
            'cost_per_unit' => ['required', 'numeric', 'min: 0'],
            'quantity' => ['required', 'numeric', 'min: 0.01'],
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

        $validated['expiration_date'] = !empty($validated['expiration_date'])
            ? Carbon::parse($validated['expiration_date'])->format('Y-m-d')
            : null;

        $validated['received_date'] = !empty($validated['received_date'])
            ? Carbon::parse($validated['received_date'])->format('Y-m-d')
            : null;

        $productId = $validated['product_id'] ?? null;

        $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

        $payload = [
            'purchase_order_id' => $validated['purchase_order_id'],
            'product_id' => $productId,
            'product_name' => $productName,
            'batch_number' => $validated['batch_number'],
            'cost_per_unit' => $validated['cost_per_unit'] ?? 0,
            'expiration_date' => $validated['expiration_date'],
            'received_date' => $validated['received_date'],
            'quantity' => $validated['quantity'] ?? 0.01,
            'last_log_by' => Auth::id(),
        ];

        PurchaseOrderItems::query()->create($payload);

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

            $deleteButton = '';

            if($writeAccess > 0 && $purchaseOrder->po_status === 'Draft'){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-stock-batch-items" data-reference-id="' . $purchaseOrderItemsId . '" title="Delete Item">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-stock-batch-items-log-notes" data-reference-id="' . $purchaseOrderItemsId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
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
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
