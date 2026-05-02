<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentReason;
use App\Models\StockLevel;
use App\Models\StockMovement;
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
            'reference_number' => ['required', 'string'],
            'stock_adjustment_reason_id' => ['required', 'integer', Rule::exists('stock_adjustment_reason', 'id')],
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
            'reference_number' => $validated['reference_number'],
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
            'detailId' => ['required', 'integer', Rule::exists('stock_adjustment', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $adjustment = StockAdjustment::with(['items.stockLevel'])
            ->findOrFail($request->detailId);

        if ($adjustment->stock_adjustment_status !== 'For Approval') {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment is not in For Approval status'
            ]);
        }

        DB::transaction(function () use ($adjustment) {
            $adjustment->update([
                'stock_adjustment_status' => 'Approved',
                'approved_date' => now(),
            ]);

            foreach ($adjustment->items as $item) {
                $stock = $item->stockLevel;

                $oldQty = $stock->quantity;
                $newQty = $item->new_quantity;
                $diff   = $newQty - $oldQty;

                // ✅ APPLY CHANGE
                $stock->quantity = $newQty;
                $stock->last_log_by = auth()->id();
                $stock->save();

                // ✅ DETERMINE MOVEMENT TYPE
                $movementType = match (true) {
                    $diff > 0  => 'IN',
                    $diff < 0  => 'OUT',
                    default    => 'ADJUSTMENT',
                };

                // ✅ LOG MOVEMENT (difference only)
                StockMovement::create([
                    'product_id'        => $stock->product_id,
                    'product_name'      => $stock->product_name,
                    'warehouse_id'      => $stock->warehouse_id,
                    'warehouse_name'    => $stock->warehouse_name,
                    'inventory_lot_id'  => $stock->inventory_lot_id,
                    'movement_type'     => $movementType,
                    'quantity'          => abs($diff),
                    'reference_type'    => 'Stock Adjustment',
                    'reference_number'  => $adjustment->reference_number,
                    'remarks'           => 'Stock adjusted',
                    'last_log_by'       => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment approved successfully',
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_adjustment', 'id')],
        ]);

        DB::transaction(function () use ($validated) {

            $adjustments = StockAdjustment::query()
                ->with(['items.stockLevel'])
                ->whereIn('id', $validated['selected_id'])
                ->where('stock_adjustment_status', 'For Approval')
                ->lockForUpdate()
                ->get();

            foreach ($adjustments as $adjustment) {

                $adjustment->update([
                    'stock_adjustment_status' => 'Approved',
                    'approved_date' => now(),
                ]);

                foreach ($adjustment->items as $item) {

                    $stock = $item->stockLevel;

                    if (!$stock) {
                        throw new \Exception('Stock level not found');
                    }

                    $oldQty = $stock->quantity;
                    $newQty = $item->new_quantity;
                    $diff   = $newQty - $oldQty;

                    $stock->quantity = $newQty;
                    $stock->last_log_by = auth()->id();
                    $stock->save();

                    $movementType = match (true) {
                        $diff > 0  => 'IN',
                        $diff < 0  => 'OUT',
                        default    => 'ADJUSTMENT',
                    };

                    StockMovement::create([
                        'product_id'        => $stock->product_id,
                        'product_name'      => $stock->product_name,
                        'warehouse_id'      => $stock->warehouse_id,
                        'warehouse_name'    => $stock->warehouse_name,
                        'inventory_lot_id'  => $stock->inventory_lot_id,
                        'movement_type'     => $movementType,
                        'quantity'          => abs($diff),
                        'reference_type'    => 'Stock Adjustment',
                        'reference_number'  => $adjustment->reference_number,
                        'remarks'           => 'Stock adjusted',
                        'last_log_by'       => auth()->id(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Selected stock adjustments approved successfully',
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
            'referenceNumber' => $stockAdjustment->reference_number ?? null,
            'stockAdjustmentReasonId' => $stockAdjustment->stock_adjustment_reason_id ?? null,
            'remarks' => $stockAdjustment->remarks ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByStatus = $request->input('filter_by_status');

        $stockAdjustments = DB::table('stock_adjustment')
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('stock_adjustment_status', (array) $filterByStatus)
            )
            ->orderBy('reference_number')
            ->get();

        $response = $stockAdjustments->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockAdjustmentId = $row->id;
            $referenceNumber = $row->reference_number;
            $stockAdjustmentReasonName = $row->stock_adjustment_reason_name;
            $adjustmentStatus = $row->stock_adjustment_status;

            $statusClass = match ($adjustmentStatus) {
                'Draft' => 'badge badge-secondary',
                'For Approval' => 'badge badge-warning',
                'Approved' => 'badge badge-success',
                'Cancelled' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$adjustmentStatus.'</span>';

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
                'REFERENCE_NUMBER' => $referenceNumber,
                'STOCK_ADJUSTMENT_REASON' => $stockAdjustmentReasonName,
                'STATUS' => $statusBadge,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
