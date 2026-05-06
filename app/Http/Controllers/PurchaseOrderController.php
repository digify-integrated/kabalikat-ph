<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Supplier;
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
            'supplier_id' => ['required', 'integer', Rule::exists('supplier', 'id')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouse', 'id')],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $validated['order_date'] = !empty($validated['order_date'])
            ? Carbon::parse($validated['order_date'])->format('Y-m-d')
            : null;

        $validated['expected_delivery_date'] = !empty($validated['expected_delivery_date'])
            ? Carbon::parse($validated['expected_delivery_date'])->format('Y-m-d')
            : null;

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $supplierId = (int) $validated['supplier_id'];
        $warehouseId = (int) $validated['warehouse_id'];

        $supplierName = (string) Supplier::query()
            ->whereKey($supplierId)
            ->value('supplier_name');

        $warehouseName = (string) Warehouse::query()
            ->whereKey($warehouseId)
            ->value('warehouse_name');

        $payload = [
            'reference_number' => $validated['reference_number'],
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierName,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'order_date' => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'],
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
            ->select(['id', 'po_status'])
            ->findOrFail($detailId);

            if ($purchaseOrder->po_status !== 'Draft') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not "Draft" status',
                ]);
            }

            $purchaseOrder->update([
                'po_status' => 'For Approval',
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
                ->select(['id', 'po_status'])
                ->findOrFail($detailId);

            if ($purchaseOrder->po_status !== 'For Approval' && $purchaseOrder->po_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not "For Approval" or "Draft" status',
                ]);
            }

            $purchaseOrder->update([
                'po_status' => 'Cancelled',
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
                ->select(['id', 'po_status'])
                ->findOrFail($detailId);

            if ($purchaseOrder->po_status !== 'For Approval') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not in "For Approval" status',
                ]);
            }

            $purchaseOrder->update([
                'po_status' => 'Draft',
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
                ->select(['id', 'po_status'])
                ->findOrFail($detailId);

            if ($purchaseOrder->po_status !== 'For Approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not "For Approval" status',
                ]);
            }

            $purchaseOrder->update([
                'po_status' => 'Approved',
                'approved_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The purchase order has been approved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('purchase_order', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $purchaseOrder = PurchaseOrder::query()
                ->select(['id', 'po_status'])
                ->findOrFail($ids);

            if ($purchaseOrder->po_status === 'For Approval') {
                $purchaseOrder->update([
                    'po_status' => 'Approved',
                    'approved_date' => Carbon::now()
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected purchase orders have been approved successfully',
        ]);
    }

    public function onProcess(Request $request)
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
                ->select(['id', 'po_status'])
                ->findOrFail($detailId);

            if ($purchaseOrder->po_status !== 'Approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'The purchase order is not "Approved" status',
                ]);
            }

            $purchaseOrder->update([
                'po_status' => 'On-Process',
                'on_process_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The purchase order has been tagged as on-process successfully',
            'redirect_link' => $link,
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
            'supplierId' => $purchaseOrder->supplier_id ?? null,
            'warehouseId' => $purchaseOrder->warehouse_id ?? null,
            'orderDate' => $purchaseOrder->order_date
            ? date('M d, Y', strtotime($purchaseOrder->order_date))
            : '',
            'expectedDeliveryDate' => $purchaseOrder->expected_delivery_date
            ? date('M d, Y', strtotime($purchaseOrder->expected_delivery_date))
            : '',
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
                ? date('M d, Y', strtotime($row->order_date))
                : null;
            $expectedDeliveryDate = $row->expected_delivery_date
                ? date('M d, Y', strtotime($row->expected_delivery_date))
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
