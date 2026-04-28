<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferReason;
use App\Models\StockLevel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockTransferController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_transfer_id' => ['nullable', 'integer'],
            'stock_level_id' => ['required', 'integer'],
            'transfer_type' => ['required', 'string'],
            'quantity' => ['required', 'numeric'],
            'stock_transfer_reason_id' => ['required', 'integer'],
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

        $stockTransferReasonId = (int) $validated['stock_transfer_reason_id'];

        $stockTransferReasonName = (string) StockTransferReason::query()
            ->whereKey($stockTransferReasonId)
            ->value('stock_transfer_reason_name');

        $payload = [
            'stock_level_id' => $validated['stock_level_id'],
            'transfer_type' => $validated['transfer_type'],
            'quantity' => $validated['quantity'],
            'stock_transfer_reason_id' => $stockTransferReasonId,
            'stock_transfer_reason_name' => $stockTransferReasonName,
            'remarks' => $validated['remarks'],
            'last_log_by' => Auth::id(),
        ];

        $stockTransferId = $validated['stock_transfer_id'] ?? null;

        if ($stockTransferId && StockTransfer::query()->whereKey($stockTransferId)->exists()) {
            $stockTransfer = StockTransfer::query()->findOrFail($stockTransferId);
            $stockTransfer->update($payload);
        } else {
            $stockTransfer = StockTransfer::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $stockTransfer->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function forApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_transfer', 'id')],
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
            $stockTransfer = StockTransfer::query()
            ->select(['id', 'stock_transfer_status'])
            ->findOrFail($detailId);

            if ($stockTransfer->stock_transfer_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock transfer is not "Draft" status',
                ]);
            }

            $stockTransfer->update([
                'stock_transfer_status' => 'For Approval',
                'for_approval_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer has been submitted for approval successfully',
            'redirect_link' => $link,
        ]);
    }

    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_transfer', 'id')],
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
            $stockTransfer = StockTransfer::query()
            ->select(['id', 'stock_transfer_status'])
            ->findOrFail($detailId);

            if ($stockTransfer->stock_transfer_status !== 'For Approval' && $stockTransfer->stock_transfer_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock transfer is not "For Approval" or "Draft" status',
                ]);
            }

            $stockTransfer->update([
                'stock_transfer_status' => 'Cancelled',
                'cancellation_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer has been cancelled successfully',
            'redirect_link' => $link,
        ]);
    }

    public function setToDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_transfer', 'id')],
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
            $stockTransfer = StockTransfer::query()
            ->select(['id', 'stock_transfer_status'])
            ->findOrFail($detailId);

            if ($stockTransfer->stock_transfer_status !== 'For Approval') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The stock transfer is not in "For Approval" status',
                ]);
            }

            $stockTransfer->update([
                'stock_transfer_status' => 'Draft',
                'set_to_draft_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer has been set to draft successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_transfer', 'id')],
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
            $stockTransfer = StockTransfer::query()
                ->select(['id', 'stock_level_id', 'stock_transfer_status', 'quantity', 'transfer_type', 'stock_transfer_reason_name'])
                ->findOrFail($detailId);

            if ($stockTransfer->stock_transfer_status !== 'For Approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock transfer is not "For Approval" status',
                ]);
            }

            $stockTransfer->update([
                'stock_transfer_status' => 'Approved',
                'approved_date' => Carbon::now()
            ]);
            
            $stockLevel = DB::table('stock_level')
                    ->where('id', $stockTransfer->stock_level_id)
                    ->first();

            $currentQty = DB::table('stock_level')
                ->where('id', $stockTransfer->stock_level_id)
                ->value('quantity');

            $adjustQty  = $stockTransfer->quantity;

            switch ($stockTransfer->transfer_type) {
                case 'Add Stock':
                    $newQty = $currentQty + $adjustQty;
                    break;

                case 'Remove Stock':
                    $newQty = $currentQty - $adjustQty;
                    break;

                case 'Set Exact Stock':
                    $newQty = $adjustQty;
                    break;

                default:
                    $newQty = $currentQty;
            }
            
            $reorderLevel = DB::table('product')
                ->where('id', $stockLevel->product_id)
                ->value('reorder_level');

            if ($newQty == 0) {
                $stockStatus = 'Out of Stock';
            } elseif ($newQty <= $reorderLevel) {
                $stockStatus = 'Low Stock';
            } else {
                $stockStatus = 'In Stock';
            }

            DB::table('stock_level')
                ->where('id', $stockTransfer->stock_level_id)
                ->update([
                    'quantity' => $newQty,
                    'stock_status' => $stockStatus,
                    'last_log_by' => auth()->id(),
                ]);

            DB::table('stock_movement')->insert([
                'stock_level_id' => $stockTransfer->stock_level_id,
                'movement_type' => 'Transfer',
                'quantity' => $adjustQty,
                'reference_type' => 'Stock Transfer',
                'reference_id' => $stockTransfer->id,
                'remarks' => $stockTransfer->stock_transfer_reason_name ?? 'Stock transfer applied',
                'last_log_by' => auth()->id(),
            ]);
        });

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer has been approved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_transfer', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {

            $stockTransfers = StockTransfer::query()
                ->select([
                    'id',
                    'stock_level_id',
                    'stock_transfer_status',
                    'quantity',
                    'transfer_type',
                    'stock_transfer_reason_name'
                ])
                ->whereIn('id', $ids)
                ->where('stock_transfer_status', 'For Approval')
                ->get();

            foreach ($stockTransfers as $stockTransfer) {
                DB::table('stock_transfer')
                    ->where('id', $stockTransfer->id)
                    ->update([
                        'stock_transfer_status' => 'Approved',
                        'approved_date' => Carbon::now()
                    ]);

                $stockLevel = DB::table('stock_level')
                    ->where('id', $stockTransfer->stock_level_id)
                    ->first();

                if (!$stockLevel) {
                    throw new \Exception("Stock level not found for transfer ID {$stockTransfer->id}");
                }

                $currentQty = $stockLevel->quantity;
                $adjustQty  = $stockTransfer->quantity;

                switch ($stockTransfer->transfer_type) {
                    case 'Add Stock':
                        $newQty = $currentQty + $adjustQty;
                        break;

                    case 'Remove Stock':
                        $newQty = $currentQty - $adjustQty;
                        break;

                    case 'Set Exact Stock':
                        $newQty = $adjustQty;
                        break;

                    default:
                        $newQty = $currentQty;
                }

                $reorderLevel = DB::table('product')
                    ->where('id', $stockLevel->product_id)
                    ->value('reorder_level') ?? 0;

                if ($newQty <= 0) {
                    $stockStatus = 'Out of Stock';
                } elseif ($newQty <= $reorderLevel) {
                    $stockStatus = 'Low Stock';
                } else {
                    $stockStatus = 'In Stock';
                }

                DB::table('stock_level')
                    ->where('id', $stockLevel->id)
                    ->update([
                        'quantity' => $newQty,
                        'stock_status' => $stockStatus,
                        'last_log_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);

                DB::table('stock_movement')->insert([
                    'stock_level_id' => $stockLevel->id,
                    'movement_type' => 'Transfer',
                    'quantity' => $adjustQty,
                    'reference_type' => 'Stock Transfer',
                    'reference_id' => $stockTransfer->id,
                    'remarks' => $stockTransfer->stock_transfer_reason_name ?? 'Stock transfer applied',
                    'last_log_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected stock transfers have been approved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_transfer', 'id')],
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
            $stockTransfer = StockTransfer::query()->select(['id'])->findOrFail($detailId);

            $stockTransfer->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_transfer', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            StockTransfer::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected stock transfers have been deleted successfully',
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

        $stockTransfer = DB::table('stock_transfer')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$stockTransfer) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Stock transfer not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'stockLevelId' => $stockTransfer->stock_level_id ?? null,
            'transferType' => $stockTransfer->transfer_type ?? null,
            'quantity' => $stockTransfer->quantity ?? 0,
            'stockTransferReasonId' => $stockTransfer->stock_transfer_reason_id ?? null,
            'remarks' => $stockTransfer->remarks ?? null
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByStockLevel = $request->input('filter_by_stock_level');
        $filterByTransferType = $request->input('filter_by_transfer_type');
        $filterByStatus = $request->input('filter_by_status');

        $stockTransfers = DB::table('stock_transfer')
            ->when(!empty($filterByStockLevel), fn($q) =>
                $q->whereIn('stock_level_id', (array) $filterByStockLevel)
            )
            ->when(!empty($filterByTransferType), fn($q) =>
                $q->whereIn('transfer_type', (array) $filterByTransferType)
            )
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('stock_transfer_status', (array) $filterByStatus)
            )
            ->orderBy('stock_level_id')
            ->get();

        $response = $stockTransfers->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockTransferId = $row->id;
            $stockLevelId = $row->stock_level_id;
            $transferType = $row->transfer_type;
            $stockTransferStatus = $row->stock_transfer_status;
            $quantity = $row->quantity;
            $stockTransferReasonName = $row->stock_transfer_reason_name;
            $remarks = $row->remarks;

            $stockLevelDetails = StockLevel::query()
                ->where('id', $stockLevelId)
                ->first();

            $statusClass = match ($stockTransferStatus) {
                'Draft' => 'badge badge-secondary',
                'For Approval' => 'badge badge-warning',
                'Approved' => 'badge badge-success',
                'Cancelled' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$stockTransferStatus.'</span>';

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stockTransferId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stockTransferId.'">
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
                'ADJUSTMENT_TYPE' => $transferType,
                'CURRENT_QUANTITY' => number_format($stockLevelDetails->quantity ?? 0, 2),
                'QUANTITY' => number_format($quantity, 2),
                'STATUS' => $statusBadge,
                'ADJUSTMENT_REASON' => $stockTransferReasonName,
                'REMARKS' => $remarks,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
