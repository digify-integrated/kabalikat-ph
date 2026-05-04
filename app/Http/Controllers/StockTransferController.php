<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\StockTransferItems;
use App\Models\StockTransferReason;
use App\Models\StockMovement;
use App\Models\Warehouse;
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
            'reference_number' => ['required', 'string'],
            'from_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouse', 'id'),
                'different:to_warehouse_id'
            ],
            'to_warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouse', 'id'),
            ],
            'stock_transfer_reason_id' => [
                'required',
                'integer',
                Rule::exists('stock_transfer_reason', 'id')
            ],
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

        $fromWarehouseId = (int) $validated['from_warehouse_id'];
        $toWarehouseId = (int) $validated['to_warehouse_id'];
        $stockTransferReasonId = (int) $validated['stock_transfer_reason_id'];

        $fromWarehouseName = (string) Warehouse::query()
            ->whereKey($fromWarehouseId)
            ->value('warehouse_name');

        $toWarehouseName = (string) Warehouse::query()
            ->whereKey($toWarehouseId)
            ->value('warehouse_name');

        $stockTransferReasonName = (string) StockTransferReason::query()
            ->whereKey($stockTransferReasonId)
            ->value('stock_transfer_reason_name');

        $payload = [
            'reference_number' => $validated['reference_number'],
            'from_warehouse_id' => $fromWarehouseId,
            'from_warehouse_name' => $fromWarehouseName,
            'to_warehouse_id' => $toWarehouseId,
            'to_warehouse_name' => $toWarehouseName,
            'stock_transfer_reason_id' => $stockTransferReasonId,
            'stock_transfer_reason_name' => $stockTransferReasonName,
            'remarks' => $validated['remarks'],
            'last_log_by' => Auth::id(),
        ];

        DB::transaction(function () use (&$stockTransfer, $validated, $payload, $fromWarehouseId) {

            $stockTransferId = $validated['stock_transfer_id'] ?? null;

            if ($stockTransferId && StockTransfer::query()->whereKey($stockTransferId)->exists()) {

                $stockTransfer = StockTransfer::query()->lockForUpdate()->findOrFail($stockTransferId);

                $oldFromWarehouseId = $stockTransfer->from_warehouse_id;

                $stockTransfer->update($payload);

                if ($oldFromWarehouseId !== $fromWarehouseId) {
                    StockTransferItems::query()
                        ->where('stock_transfer_id', $stockTransfer->id)
                        ->delete();
                }

            } else {

                $stockTransfer = StockTransfer::query()->create($payload);
            }
        });

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
            'detailId' => ['required', 'integer', Rule::exists('stock_transfer', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $transfer = StockTransfer::with([
            'items.stockLevel.product'
        ])->findOrFail($request->detailId);

        if ($transfer->stock_transfer_status !== 'For Approval') {
            return response()->json([
                'success' => false,
                'message' => 'Stock transfer is not in For Approval status'
            ]);
        }

        DB::transaction(function () use ($transfer) {

            $transfer->update([
                'stock_transfer_status' => 'Approved',
                'approved_date' => now(),
            ]);

            foreach ($transfer->items as $item) {

                $sourceStock = $item->stockLevel;

                if (!$sourceStock) {
                    throw new \Exception('Source stock not found');
                }

                $qty = $item->quantity;

                // ❗ Prevent negative stock
                if ($sourceStock->quantity < $qty) {
                    throw new \Exception('Insufficient stock for transfer');
                }

                /*
                |--------------------------------------------------------------------------
                | 1. TRANSFER OUT (SOURCE)
                |--------------------------------------------------------------------------
                */
                $sourceStock->quantity -= $qty;

                $reorderLevel = $sourceStock->product->reorder_level ?? 0;

                $sourceStock->stock_status = match (true) {
                    $sourceStock->quantity == 0 => 'Out of Stock',
                    $sourceStock->quantity <= $reorderLevel => 'Low Stock',
                    default => 'In Stock',
                };

                $sourceStock->last_log_by = auth()->id();
                $sourceStock->save();

                /*
                |--------------------------------------------------------------------------
                | 2. TRANSFER IN (DESTINATION)
                |--------------------------------------------------------------------------
                */
                $destStock = StockLevel::firstOrCreate(
                    [
                        'product_id'       => $sourceStock->product_id,
                        'warehouse_id'     => $transfer->to_warehouse_id,
                        'inventory_lot_id' => $sourceStock->inventory_lot_id,
                    ],
                    [
                        'product_name'     => $sourceStock->product_name,
                        'warehouse_name'   => $transfer->to_warehouse_name,
                        'quantity'         => 0,
                        'stock_status'     => 'Out of Stock',
                        'last_log_by'      => auth()->id(),
                    ]
                );

                $destStock->quantity += $qty;

                $reorderLevelDest = $sourceStock->product->reorder_level ?? 0;

                $destStock->stock_status = match (true) {
                    $destStock->quantity == 0 => 'Out of Stock',
                    $destStock->quantity <= $reorderLevelDest => 'Low Stock',
                    default => 'In Stock',
                };

                $destStock->last_log_by = auth()->id();
                $destStock->save();

                /*
                |--------------------------------------------------------------------------
                | 3. STOCK MOVEMENTS
                |--------------------------------------------------------------------------
                */

                // TRANSFER OUT
                StockMovement::create([
                    'product_id'        => $sourceStock->product_id,
                    'product_name'      => $sourceStock->product_name,
                    'warehouse_id'      => $transfer->from_warehouse_id,
                    'warehouse_name'    => $transfer->from_warehouse_name,
                    'inventory_lot_id'  => $sourceStock->inventory_lot_id,
                    'movement_type'     => 'TRANSFER_OUT',
                    'quantity'          => $qty,
                    'reference_type'    => 'Stock Transfer',
                    'reference_number'  => $transfer->reference_number,
                    'remarks'           => 'Stock transferred out',
                    'last_log_by'       => auth()->id(),
                ]);

                // TRANSFER IN
                StockMovement::create([
                    'product_id'        => $sourceStock->product_id,
                    'product_name'      => $sourceStock->product_name,
                    'warehouse_id'      => $transfer->to_warehouse_id,
                    'warehouse_name'    => $transfer->to_warehouse_name,
                    'inventory_lot_id'  => $sourceStock->inventory_lot_id,
                    'movement_type'     => 'TRANSFER_IN',
                    'quantity'          => $qty,
                    'reference_type'    => 'Stock Transfer',
                    'reference_number'  => $transfer->reference_number,
                    'remarks'           => 'Stock transferred in',
                    'last_log_by'       => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock transfer approved successfully',
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_transfer', 'id')],
        ]);

        DB::transaction(function () use ($validated) {

            $transfers = StockTransfer::query()
                ->with(['items.stockLevel.product'])
                ->whereIn('id', $validated['selected_id'])
                ->where('stock_transfer_status', 'For Approval')
                ->lockForUpdate()
                ->get();

            if ($transfers->isEmpty()) {
                throw new \Exception('No transfers available for approval');
            }

            foreach ($transfers as $transfer) {

                $transfer->update([
                    'stock_transfer_status' => 'Approved',
                    'approved_date' => now(),
                ]);

                foreach ($transfer->items as $item) {

                    $sourceStock = $item->stockLevel;

                    if (!$sourceStock) {
                        throw new \Exception('Source stock not found');
                    }

                    $qty = $item->quantity;

                    if ($sourceStock->quantity < $qty) {
                        throw new \Exception('Insufficient stock for transfer');
                    }

                    // TRANSFER OUT
                    $sourceStock->quantity -= $qty;

                    $reorderLevel = $sourceStock->product->reorder_level ?? 0;

                    $sourceStock->stock_status = match (true) {
                        $sourceStock->quantity == 0 => 'Out of Stock',
                        $sourceStock->quantity <= $reorderLevel => 'Low Stock',
                        default => 'In Stock',
                    };

                    $sourceStock->last_log_by = auth()->id();
                    $sourceStock->save();

                    // TRANSFER IN
                    $destStock = StockLevel::firstOrCreate(
                        [
                            'product_id'       => $sourceStock->product_id,
                            'warehouse_id'     => $transfer->to_warehouse_id,
                            'inventory_lot_id' => $sourceStock->inventory_lot_id,
                        ],
                        [
                            'product_name'     => $sourceStock->product_name,
                            'warehouse_name'   => $transfer->to_warehouse_name,
                            'quantity'         => 0,
                            'stock_status'     => 'Out of Stock',
                            'last_log_by'      => auth()->id(),
                        ]
                    );

                    $destStock->quantity += $qty;

                    $destStock->stock_status = match (true) {
                        $destStock->quantity == 0 => 'Out of Stock',
                        $destStock->quantity <= $reorderLevel => 'Low Stock',
                        default => 'In Stock',
                    };

                    $destStock->last_log_by = auth()->id();
                    $destStock->save();

                    // MOVEMENTS
                    StockMovement::create([
                        'product_id'        => $sourceStock->product_id,
                        'product_name'      => $sourceStock->product_name,
                        'warehouse_id'      => $transfer->from_warehouse_id,
                        'warehouse_name'    => $transfer->from_warehouse_name,
                        'inventory_lot_id'  => $sourceStock->inventory_lot_id,
                        'movement_type'     => 'TRANSFER_OUT',
                        'quantity'          => $qty,
                        'reference_type'    => 'Stock Transfer',
                        'reference_number'  => $transfer->reference_number,
                        'remarks'           => 'Stock transferred out',
                        'last_log_by'       => auth()->id(),
                    ]);

                    StockMovement::create([
                        'product_id'        => $sourceStock->product_id,
                        'product_name'      => $sourceStock->product_name,
                        'warehouse_id'      => $transfer->to_warehouse_id,
                        'warehouse_name'    => $transfer->to_warehouse_name,
                        'inventory_lot_id'  => $sourceStock->inventory_lot_id,
                        'movement_type'     => 'TRANSFER_IN',
                        'quantity'          => $qty,
                        'reference_type'    => 'Stock Transfer',
                        'reference_number'  => $transfer->reference_number,
                        'remarks'           => 'Stock transferred in',
                        'last_log_by'       => auth()->id(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Selected stock transfers approved successfully',
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
            'referenceNumber' => $stockTransfer->reference_number ?? null,
            'fromWarehouseId' => $stockTransfer->from_warehouse_id ?? null,
            'toWarehouseId' => $stockTransfer->to_warehouse_id ?? null,
            'stockTransferReasonId' => $stockTransfer->stock_transfer_reason_id ?? null,
            'remarks' => $stockTransfer->remarks ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByStatus = $request->input('filter_by_status');

        $stockTransfers = DB::table('stock_transfer')
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('stock_transfer_status', (array) $filterByStatus)
            )
            ->orderBy('reference_number')
            ->get();

        $response = $stockTransfers->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockTransferId = $row->id;
            $referenceNumber = $row->reference_number;
            $fromWarehouseName = $row->from_warehouse_name;
            $toWarehouseName = $row->to_warehouse_name;
            $stockTransferReasonName = $row->stock_transfer_reason_name;
            $transferStatus = $row->stock_transfer_status;

            $statusClass = match ($transferStatus) {
                'Draft' => 'badge badge-secondary',
                'For Approval' => 'badge badge-warning',
                'Approved' => 'badge badge-success',
                'Cancelled' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$transferStatus.'</span>';

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
                'REFERENCE_NUMBER' => $referenceNumber,
                'TRANSFER' => $fromWarehouseName . ' -> ' . $toWarehouseName,
                'STOCK_ADJUSTMENT_REASON' => $stockTransferReasonName,
                'STATUS' => $statusBadge,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
