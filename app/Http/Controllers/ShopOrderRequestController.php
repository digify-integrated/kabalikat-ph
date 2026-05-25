<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShopOrder;
use App\Models\ShopOrderPayment;
use App\Models\ShopOrderRequest;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ShopOrderRequestController extends Controller
{
    public function save(Request $request)
    {
        return $this->saveOrderRequest(
            request: $request,
            requestType: $request->input('request_type') ?? 'Void'
        );
    }

    public function saveVoidRequest(Request $request)
    {
        return $this->saveOrderRequest(
            request: $request,
            requestType: 'Void'
        );
    }

    public function saveRefundRequest(Request $request)
    {
        return $this->saveOrderRequest(
            request: $request,
            requestType: 'Refund'
        );
    }

    private function saveOrderRequest(Request $request, string $requestType) 
    {
        $validator = Validator::make(
            $request->all(),
            [
                'shop_order_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'shop_order',
                        'id'
                    ),
                ],

                'request_reason' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,

                'message'
                    => $validator
                        ->errors()
                        ->first(),
            ]);
        }

        $validated = $validator->validated();

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $shopOrder = ShopOrder::query()
            ->find(
                $validated['shop_order_id']
            );

        if (!$shopOrder) {

            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ]);
        }

        if (
            $shopOrder->order_status === 'Cancelled'
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Cancelled orders cannot be requested.',
            ]);
        }

        if ($shopOrder->order_status === 'Voided') {
            return response()->json([
                'success' => false,
                'message' => 'Order already voided.',
            ]);
        }

        if ($requestType === 'Refund' && $shopOrder->payment_status !== 'Paid') {
            return response()->json([
                'success' => false,
                'message' => 'Only paid orders can be refunded.',
            ]);
        }

        if ($requestType === 'Void' && $shopOrder->payment_status !== 'Paid') {
            return response()->json([
                'success' => false,
                'message' => 'Only paid orders can be voided.',
            ]);
        }

        if ($shopOrder->payment_status === 'Refunded') {
            return response()->json([
                'success' => false,
                'message' => 'Order already refunded.',
            ]);
        }

        $existingRequest = ShopOrderRequest::query()
            ->where(
                'shop_order_id',
                $shopOrder->id
            )
            ->where(
                'request_status',
                'Pending'
            )
            ->exists();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message'
                    => "There is already a pending request.",
            ]);
        }

        $shopOrderRequest = ShopOrderRequest::query()
            ->create([
                'shop_order_id' => $shopOrder->id,
                'order_number' => $shopOrder->order_number,
                'request_type' => $requestType,
                'request_status' => 'Pending',
                'request_reason' => $validated['request_reason'],
                'requested_by' => auth()->id(),
                'requested_by_name' => auth()->user()->name,
                'requested_at' => now(),
                'last_log_by' => auth()->id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $shopOrderRequest->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$requestType} request submitted successfully.",
            'redirect_link' => $link,
        ]);
    }

    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_order_request_id' => ['required', 'integer', 'min:1', Rule::exists('shop_order_request', 'id')],
            'cancellation_reason' => ['required', 'string'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }


        $validated = $validator->validated();

        $detailId = (int) $validated['shop_order_request_id'];

        DB::transaction(function () use ($detailId, $validated) {
            $shopOrderRequest = ShopOrderRequest::query()
                ->select(['id', 'request_status'])
                ->findOrFail($detailId);

            if ($shopOrderRequest->request_status !== 'Pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'The shop order request is not "Pending" status',
                ]);
            }

            $shopOrderRequest->update([
                'request_status' => 'Cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_by_name' => Auth::user()->name,
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => $validated['cancellation_reason'],
                'last_log_by' => Auth::id()
            ]);
        });

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The shop order request has been cancelled successfully',
            'redirect_link' => $link,
        ]);
    }

    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_order_request_id' => ['required', 'integer', 'min:1', Rule::exists('shop_order_request', 'id')],
            'rejection_reason' => ['required', 'string'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('detailId') ?? 'Validation failed',
            ]);
        }

        $validated = $validator->validated();

         $detailId = (int) $validated['shop_order_request_id'];

        DB::transaction(function () use ($detailId, $validated) {
            $shopOrderRequest = ShopOrderRequest::query()
                ->select(['id', 'request_status'])
                ->findOrFail($detailId);

            if ($shopOrderRequest->request_status !== 'Pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'The shop order request is not "Pending" status',
                ]);
            }

            $shopOrderRequest->update([
                'request_status' => 'Rejected',
                'rejected_by' => Auth::id(),
                'rejected_by_name' => Auth::user()->name,
                'rejected_at' => Carbon::now(),
                'rejection_reason' => $validated['rejection_reason'],
                'last_log_by' => Auth::id()
            ]);
        });

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The shop order request has been cancelled successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_order_request_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('shop_order_request', 'id'),
            ],
            'approval_remarks' => [
                'nullable',
                'string',
                'max:500',
            ],

        ]);

        $pageAppId = (int) $request->input('appId');

        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {

            DB::transaction(function () use ($validator) {

                $detailId =
                    (int) $validator->validated()['shop_order_request_id'];

                /*
                |--------------------------------------------------------------------------
                | LOAD REQUEST
                |--------------------------------------------------------------------------
                */

                $shopOrderRequest = ShopOrderRequest::query()

                    ->with([
                        'shopOrder',
                        'shopOrder.payments',
                    ])

                    ->lockForUpdate()

                    ->findOrFail($detailId);

                /*
                |--------------------------------------------------------------------------
                | VALIDATE STATUS
                |--------------------------------------------------------------------------
                */

                if (
                    $shopOrderRequest->request_status !== 'Pending'
                ) {

                    throw new \Exception(
                        'The request is no longer pending.'
                    );
                }

                $shopOrder =
                    $shopOrderRequest->shopOrder;

                if (!$shopOrder) {

                    throw new \Exception(
                        'Shop order not found.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | PROCESS REQUEST TYPE
                |--------------------------------------------------------------------------
                */

                if (
                    $shopOrderRequest->request_type === 'Void'
                ) {

                    $this->approveVoidRequest(
                        $shopOrder,
                        $shopOrderRequest
                    );

                } elseif (

                    $shopOrderRequest->request_type === 'Refund'

                ) {

                    $this->approveRefundRequest(
                        $shopOrder,
                        $shopOrderRequest
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | APPROVE REQUEST
                |--------------------------------------------------------------------------
                */

                $shopOrderRequest->update([

                    'request_status' => 'Approved',

                    'approved_by' => Auth::id(),

                    'approved_by_name' => Auth::user()?->name,

                    'approved_at' => now(),

                    'approval_remarks' => $validator->validated()['approval_remarks'],

                    'last_log_by' => Auth::id(),
                ]);
            });

            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request approved successfully.',
                'redirect_link' => $link,
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function approveVoidRequest(ShopOrder $shopOrder, ShopOrderRequest $shopOrderRequest): void 
    {

        /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder->update([

            'order_status' => 'Voided',

            'payment_status' => 'Refunded',

            'voided_by' => Auth::id(),

            'voided_by_name' => Auth::user()?->name,

            'void_reason' => $shopOrderRequest->request_reason,

            'last_log_by' => Auth::id(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | UPDATE PAYMENTS
        |--------------------------------------------------------------------------
        */

        ShopOrderPayment::query()

            ->where(
                'shop_order_id',
                $shopOrder->id
            )

            ->update([

                'payment_status' => 'Voided',

                'voided_at' => now(),

                'void_reason'
                    => $shopOrderRequest->request_reason,

                'voided_by' => Auth::id(),

                'voided_by_name'
                    => Auth::user()?->name,

                'last_log_by' => Auth::id(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | RETURN INVENTORY
        |--------------------------------------------------------------------------
        */

        $this->reverseInventoryMovements(
            $shopOrder->order_number,
            'VOID'
        );
    }

    private function approveRefundRequest(ShopOrder $shopOrder, ShopOrderRequest $shopOrderRequest): void 
    {
        /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder->update([

            'payment_status' => 'Refunded',

            'refund_by' => Auth::id(),

            'refund_by_name' => Auth::user()?->name,

            'refund_reason'
                => $shopOrderRequest->request_reason,

            'last_log_by' => Auth::id(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | UPDATE PAYMENTS
        |--------------------------------------------------------------------------
        */

        ShopOrderPayment::query()

            ->where(
                'shop_order_id',
                $shopOrder->id
            )

            ->update([

                'payment_status' => 'Refunded',

                'refunded_at' => now(),

                'refund_reason'
                    => $shopOrderRequest->request_reason,

                'refunded_by' => Auth::id(),

                'refunded_by_name'
                    => Auth::user()?->name,

                'last_log_by' => Auth::id(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | RETURN INVENTORY
        |--------------------------------------------------------------------------
        */

        $this->reverseInventoryMovements(
            $shopOrder->order_number,
            'REFUND'
        );
    }

    private function reverseInventoryMovements(
        string $referenceNumber,
        string $reason
    ): void {

        $movements = StockMovement::query()
        ->where('reference_type', 'Shop Order')
        ->where('reference_number', $referenceNumber)
        ->where('movement_type', 'SALE')
        ->lockForUpdate()
        ->get();

        foreach ($movements as $movement) {

            /*
            |--------------------------------------------------------------------------
            | RETURN STOCK
            |--------------------------------------------------------------------------
            */

            $stockLevel = StockLevel::query()

                ->where(
                    'product_id',
                    $movement->product_id
                )

                ->where(
                    'warehouse_id',
                    $movement->warehouse_id
                )

                ->where(
                    'inventory_lot_id',
                    $movement->inventory_lot_id
                )

                ->lockForUpdate()

                ->first();

            if (!$stockLevel) {

                continue;
            }

            $stockLevel->increment(
                'quantity',
                $movement->quantity
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE STOCK STATUS
            |--------------------------------------------------------------------------
            */

            $product = Product::query()
                ->find($movement->product_id);

            $stockLevel->refresh();

            $stockLevel->update([

                'stock_status' => match (true) {

                    $stockLevel->quantity <= 0
                        => 'Out of Stock',

                    $stockLevel->quantity <= ($product?->reorder_level ?? 0)
                        => 'Low Stock',

                    default
                        => 'In Stock',
                },
            ]);

            /*
            |--------------------------------------------------------------------------
            | CREATE RETURN MOVEMENT
            |--------------------------------------------------------------------------
            */

            StockMovement::create([

                'product_id'
                    => $movement->product_id,

                'product_name'
                    => $movement->product_name,

                'warehouse_id'
                    => $movement->warehouse_id,

                'warehouse_name'
                    => $movement->warehouse_name,

                'inventory_lot_id'
                    => $movement->inventory_lot_id,

                'movement_type'
                    => 'RETURN',

                'quantity'
                    => $movement->quantity,

                'reference_type'
                    => 'Shop Order ' . $reason,

                'reference_number'
                    => $referenceNumber,

                'remarks'
                    => "Inventory reversal due to {$reason}",

                'last_log_by'
                    => Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | MARK ORIGINAL AS REVERSED
            |--------------------------------------------------------------------------
            */

            $movement->update([

                'is_reversed' => true,

                'reversed_at' => now(),

                'last_log_by' => Auth::id(),
            ]);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('shop_order_request', 'id')],
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
            $shopOrderRequest = ShopOrderRequest::query()->select(['id'])->findOrFail($detailId);

            $shopOrderRequest->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The shop order request has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('shop_order_request', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            ShopOrderRequest::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected shop order requests have been deleted successfully',
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

        $shopOrderRequest = DB::table('shop_order_request')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$shopOrderRequest) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Shop order request not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'orderNumber' => $shopOrderRequest->order_number ?? null,
            'requestType' => $shopOrderRequest->request_type ?? null,
            'requestStatus' => $shopOrderRequest->request_status ?? null,
            'requestReason' => $shopOrderRequest->request_reason ?? null,
            'requestedByName' => $shopOrderRequest->requested_by_name ?? null,
            'requestedAt' => $shopOrderRequest->requested_at
            ? date('M d, Y h:i:m a', strtotime($shopOrderRequest->requested_at))
            : '',
            'approvedByName' => $shopOrderRequest->approved_by_name ?? null,
            'approvedAt' => $shopOrderRequest->approved_at
            ? date('M d, Y h:i:m a', strtotime($shopOrderRequest->approved_at))
            : '',
            'approvalRemarks' => $shopOrderRequest->approval_remarks ?? null,
            'rejectedByName' => $shopOrderRequest->rejected_by_name ?? null,
            'rejectedAt' => $shopOrderRequest->rejected_at
            ? date('M d, Y h:i:m a', strtotime($shopOrderRequest->rejected_at))
            : '',
            'rejectionReason' => $shopOrderRequest->rejection_reason ?? null,
            'cancelledByName' => $shopOrderRequest->cancelled_by_name ?? null,
            'cancelledAt' => $shopOrderRequest->cancelled_at
            ? date('M d, Y h:i:m a', strtotime($shopOrderRequest->cancelled_at))
            : '',
            'cancellationReason' => $shopOrderRequest->cancellation_reason ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByRequestDate = $request->input('filter_by_request_date');
        $filterByRequestType = $request->input('filter_by_request_type');
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

        $requestRange = $parseRange($filterByRequestDate);

        $shopOrderRequests = DB::table('shop_order_request')
            ->when($requestRange, fn($q) =>
                $q->whereBetween('requested_at', $requestRange)
            )
            ->when(!empty($filterByRequestType), fn($q) =>
                $q->whereIn('request_type', (array) $filterByRequestType)
            )
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('request_status', (array) $filterByStatus)
            )
            ->orderBy('order_number')
            ->get();

        $response = $shopOrderRequests->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockLevelId = $row->id;
            $orderNumber = $row->order_number;
            $requestType = $row->request_type;
            $requestReason = $row->request_reason;
            $requestedByName = $row->requested_by_name;
            $requestStatus = $row->request_status;

            $requestedAt = $row->requested_at
                ? date('M d, Y h:i:m a', strtotime($row->requested_at))
                : null;

            $statusClass = match ($requestStatus) {
                'Pending' => 'badge badge-secondary',
                'Approved' => 'badge badge-success',
                'Rejected' => 'badge badge-danger',
                'Cancelled' => 'badge badge-warning',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$requestStatus.'</span>';

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
                'ORDER_NUMBER' => $orderNumber,
                'REQUEST_TYPE' => $requestType,
                'REQUEST_REASON' => $requestReason,
                'STATUS' => $statusBadge,
                'REQUESTED_BY' => $requestedByName,
                'REQUESTED_AT' => $requestedAt,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
