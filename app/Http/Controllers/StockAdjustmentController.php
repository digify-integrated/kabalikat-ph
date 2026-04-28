<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentReason;
use App\Models\StockLevel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_adjustment_id' => ['nullable', 'integer'],
            'stock_level_id' => ['required', 'integer'],
            'adjustment_type' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'stock_adjustment_reason_id' => ['required', 'integer'],
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

        $stockAdjustmentReasonId = (int) $validated['stock_adjustment_reason_id'];

        $stockAdjustmentReasonName = (string) StockAdjustmentReason::query()
            ->whereKey($stockAdjustmentReasonId)
            ->value('stock_adjustment_reason_name');

        $payload = [
            'stock_level_id' => $validated['stock_level_id'],
            'adjustment_type' => $validated['adjustment_type'],
            'quantity' => $validated['quantity'],
            'stock_adjustment_reason_id' => $stockAdjustmentReasonId,
            'stock_adjustment_reason_name' => $stockAdjustmentReasonName,
            'remarks' => $validated['remarks'],
            'last_log_by' => Auth::id(),
        ];

        $stockAdjustmentId = $validated['stock_adjustment_id'] ?? null;

        if ($stockAdjustmentId && StockAdjustment::query()->whereKey($stockAdjustmentId)->exists()) {
            $stockAdjustment = StockAdjustment::query()->findOrFail($stockAdjustmentId);
            $stockAdjustment->update($payload);
        } else {
            $stockAdjustment = StockAdjustment::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $stockAdjustment->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function forApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_adjustment', 'id')],
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
            $stockAdjustment = StockAdjustment::query()
            ->select(['id', 'stock_adjustment_status'])
            ->findOrFail($detailId);

            if ($stockAdjustment->stock_adjustment_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock adjustment is not "Draft" status',
                ]);
            }

            $stockAdjustment->update([
                'stock_adjustment_status' => 'For Approval',
                'for_approval_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment has been submitted for approval successfully',
            'redirect_link' => $link,
        ]);
    }

    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_adjustment', 'id')],
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
            $stockAdjustment = StockAdjustment::query()
            ->select(['id', 'stock_adjustment_status'])
            ->findOrFail($detailId);

            if ($stockAdjustment->stock_adjustment_status !== 'For Approval' && $stockAdjustment->stock_adjustment_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock adjustment is not "For Approval" or "Draft" status',
                ]);
            }

            $stockAdjustment->update([
                'stock_adjustment_status' => 'Cancelled',
                'cancellation_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment has been cancelled successfully',
            'redirect_link' => $link,
        ]);
    }

    public function setToDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_adjustment', 'id')],
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
            $stockAdjustment = StockAdjustment::query()
            ->select(['id', 'stock_adjustment_status'])
            ->findOrFail($detailId);

            if ($stockAdjustment->stock_adjustment_status !== 'For Approval') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The stock adjustment is not in "For Approval" status',
                ]);
            }

            $stockAdjustment->update([
                'stock_adjustment_status' => 'Draft',
                'set_to_draft_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment has been set to draft successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_adjustment', 'id')],
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
            $stockAdjustment = StockAdjustment::query()
                ->select(['id', 'stock_adjustment_status'])
                ->findOrFail($detailId);

            if ($stockAdjustment->stock_adjustment_status !== 'For Approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock adjustment is not "For Approval" status',
                ]);
            }

            $stockAdjustment->update([
                'stock_adjustment_status' => 'Approved',
                'approval_date' => Carbon::now()
            ]);

            $reorderLevel = DB::table('product')
                ->where('id', $stockAdjustment->product_id)
                ->value('reorder_level');

            $quantity = $stockAdjustment->quantity;

            if ($quantity == 0) {
                $stockStatus = 'Out of Stock';
            } elseif ($quantity <= $reorderLevel) {
                $stockStatus = 'Low Stock';
            } else {
                $stockStatus = 'In Stock';
            }

            $stockLevel = StockLevel::create([
                'product_id' => $stockAdjustment->product_id,
                'product_name' => $stockAdjustment->product_name,
                'warehouse_id' => $stockAdjustment->warehouse_id,
                'warehouse_name' => $stockAdjustment->warehouse_name,
                'stock_status' => $stockStatus,
                'quantity' => $quantity,
                'stock_adjustment_id' => $stockAdjustment->id,
                'expiration_date' => $stockAdjustment->expiration_date,
                'received_date' => $stockAdjustment->received_date,
                'cost_per_unit' => $stockAdjustment->cost_per_unit,
                'last_log_by' => auth()->id(),
            ]);

            DB::table('stock_movement')->insert([
                'stock_level_id' => $stockLevel->id,
                'movement_type' => 'In',
                'quantity' => $quantity,
                'reference_type' => 'Stock Adjustment',
                'reference_id' => $stockAdjustment->id,
                'remarks' => 'Initial stock from batch approval',
                'last_log_by' => auth()->id(),
            ]);
        });

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment has been approved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_adjustment', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $stockAdjustments = StockAdjustment::query()
                ->whereIn('id', $ids)
                ->where('stock_adjustment_status', 'For Approval')
                ->get();

            StockAdjustment::query()
                ->whereIn('id', $stockAdjustments->pluck('id'))
                ->update([
                    'stock_adjustment_status' => 'Approved',
                    'approved_date' => Carbon::now()
                ]);

            foreach ($stockAdjustments as $stockAdjustment) {
                $reorderLevel = DB::table('product')
                    ->where('id', $stockAdjustment->product_id)
                    ->value('reorder_level');

                $quantity = $stockAdjustment->quantity;

                $stockStatus = match (true) {
                    $quantity == 0 => 'Out of Stock',
                    $quantity <= $reorderLevel => 'Low Stock',
                    default => 'In Stock',
                };

                $stockLevel = StockLevel::create([
                    'product_id' => $stockAdjustment->product_id,
                    'product_name' => $stockAdjustment->product_name,
                    'warehouse_id' => $stockAdjustment->warehouse_id,
                    'warehouse_name' => $stockAdjustment->warehouse_name,
                    'stock_status' => $stockStatus,
                    'quantity' => $quantity,
                    'stock_adjustment_id' => $stockAdjustment->id,
                    'expiration_date' => $stockAdjustment->expiration_date,
                    'received_date' => $stockAdjustment->received_date,
                    'cost_per_unit' => $stockAdjustment->cost_per_unit,
                    'last_log_by' => auth()->id(),
                ]);

                DB::table('stock_movement')->insert([
                    'stock_level_id' => $stockLevel->id,
                    'movement_type' => 'In',
                    'quantity' => $quantity,
                    'reference_type' => 'Stock Adjustment',
                    'reference_id' => $stockAdjustment->batch_number,
                    'remarks' => 'Initial stock from batch approval',
                    'last_log_by' => auth()->id(),
                ]);
            }
        });
        return response()->json([
            'success' => true,
            'message' => 'The selected stock adjustments have been approved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_adjustment', 'id')],
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
            $stockAdjustment = StockAdjustment::query()->select(['id'])->findOrFail($detailId);

            $stockAdjustment->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_adjustment', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            StockAdjustment::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected stock adjustments have been deleted successfully',
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

        $stockAdjustment = DB::table('stock_adjustment')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$stockAdjustment) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Stock adjustment not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'stockLevelId' => $stockAdjustment->stock_level_id ?? null,
            'adjustmentType' => $stockAdjustment->adjustment_type ?? null,
            'quantity' => $stockAdjustment->quantity ?? 0.01,
            'stockAdjustmentReasonId' => $stockAdjustment->stock_adjustment_reason_id ?? null,
            'remarks' => $stockAdjustment->remarks ?? null
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByStockLevel = $request->input('filter_by_stock_level');
        $filterByAdjustmentType = $request->input('filter_by_adjustment_type');
        $filterByStatus = $request->input('filter_by_status');

        $stockAdjustments = DB::table('stock_adjustment')
            ->when(!empty($filterByStockLevel), fn($q) =>
                $q->whereIn('stock_level_id', (array) $filterByStockLevel)
            )
            ->when(!empty($filterByAdjustmentType), fn($q) =>
                $q->whereIn('adjustment_type', (array) $filterByAdjustmentType)
            )
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('stock_adjustment_status', (array) $filterByStatus)
            )
            ->orderBy('stock_level_id')
            ->get();

        $response = $stockAdjustments->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockAdjustmentId = $row->id;
            $stockLevelId = $row->stock_level_id;
            $adjustmentType = $row->adjustment_type;
            $stockAdjustmentStatus = $row->stock_adjustment_status;
            $quantity = $row->quantity;
            $stockAdjustmentReasonName = $row->stock_adjustment_reason_name;
            $remarks = $row->remarks;

            $stockLevelDetails = StockLevel::query()
                ->where('id', $stockLevelId)
                ->first();

            $statusClass = match ($stockAdjustmentStatus) {
                'Draft' => 'badge badge-secondary',
                'For Approval' => 'badge badge-warning',
                'Approved' => 'badge badge-success',
                'Cancelled' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$stockAdjustmentStatus.'</span>';

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stockAdjustmentId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stockAdjustmentId.'">
                    </div>
                ',
                'STOCK_LEVEL' => '<div class="d-flex align-items-center">
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$stockLevelDetails->product_name.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$stockLevelDetails->warehouse_name.'</small>
                            </div>
                        </div>
                    </div>',
                'ADJUSTMENT_TYPE' => $adjustmentType,
                'CURRENT_QUANTITY' => number_format($stockLevelDetails->quantity ?? 0, 2),
                'QUANTITY' => number_format($quantity, 2),
                'STATUS' => $statusBadge,
                'ADJUSTMENT_REASON' => $stockAdjustmentReasonName,
                'REMARKS' => $remarks,
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

        $stockAdjustments = DB::table('stock_adjustment')
            ->select(['id', 'stock_adjustment_name', 'stock_adjustment'])
            ->orderBy('stock_adjustment_name')
            ->get();

        $response = $response->concat(
            $stockAdjustments->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->stock_adjustment_name . ' (.' . $row->stock_adjustment . ')',
            ])
        )->values();

        return response()->json($response);
    }
}
