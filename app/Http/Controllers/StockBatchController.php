<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\StockBatch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockBatchController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_batch_id' => ['nullable', 'integer'],
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

        $stockBatchId = $validated['stock_batch_id'] ?? null;

        if ($stockBatchId && StockBatch::query()->whereKey($stockBatchId)->exists()) {
            $stockBatch = StockBatch::query()->findOrFail($stockBatchId);
            $stockBatch->update($payload);
        } else {
            $stockBatch = StockBatch::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $stockBatch->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock batch has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function forApproval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_batch', 'id')],
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
            $stockBatch = StockBatch::query()
            ->select(['id', 'stock_batch_status'])
            ->findOrFail($detailId);

            if ($stockBatch->stock_batch_status !== 'Draft') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The stock batch is not "Draft" status',
                ]);
            }

            $stockBatch->update([
                'stock_batch_status' => 'For Approval',
                'for_approval_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock batch has been submitted for approval successfully',
            'redirect_link' => $link,
        ]);
    }

    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_batch', 'id')],
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
            $stockBatch = StockBatch::query()
                ->select(['id', 'stock_batch_status'])
                ->findOrFail($detailId);

            if ($stockBatch->stock_batch_status !== 'For Approval' && $stockBatch->stock_batch_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock batch is not "For Approval" or "Draft" status',
                ]);
            }

            $stockBatch->update([
                'stock_batch_status' => 'Cancelled',
                'cancellation_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock batch has been cancelled successfully',
            'redirect_link' => $link,
        ]);
    }

    public function setToDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_batch', 'id')],
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
            $stockBatch = StockBatch::query()
                ->select(['id', 'stock_batch_status'])
                ->findOrFail($detailId);

            if ($stockBatch->stock_batch_status !== 'For Approval') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The stock batch is not in "For Approval" status',
                ]);
            }

            $stockBatch->update([
                'stock_batch_status' => 'Draft',
                'set_to_draft_date' => Carbon::now()
            ]);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock batch has been set to draft successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', Rule::exists('stock_batch', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $detailId = (int) $validator->validated()['detailId'];

        $batch = StockBatch::with(['items.product', 'warehouse'])
            ->findOrFail($detailId);

        if ($batch->stock_batch_status !== 'For Approval') {
            return response()->json([
                'success' => false,
                'message' => 'Stock batch is not in For Approval status'
            ]);
        }

        DB::transaction(function () use ($batch) {

            $batch->update([
                'stock_batch_status' => 'Approved',
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
                    'reference_type'    => 'Stock Batch',
                    'reference_number'  => $batch->reference_number,
                    'remarks'           => 'Stock received via batch approval',
                    'last_log_by'       => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock batch approved successfully',
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_batch', 'id')],
        ]);

        DB::transaction(function () use ($validated) {

            $batches = StockBatch::query()
                ->with(['items.product', 'warehouse'])
                ->whereIn('id', $validated['selected_id'])
                ->where('stock_batch_status', 'For Approval')
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                $batch->update([
                    'stock_batch_status' => 'Approved',
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
                        'reference_type'    => 'Stock Batch',
                        'reference_number'  => $batch->reference_number,
                        'remarks'           => 'Stock received via batch approval',
                        'last_log_by'       => auth()->id(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Selected stock batches have been approved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_batch', 'id')],
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
            $stockBatch = StockBatch::query()->select(['id'])->findOrFail($detailId);

            $stockBatch->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock batch has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_batch', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            StockBatch::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected stock batchs have been deleted successfully',
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

        $stockBatch = DB::table('stock_batch')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$stockBatch) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Stock batch not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'referenceNumber' => $stockBatch->reference_number ?? null,
            'warehouseId' => $stockBatch->warehouse_id ?? null,
            'remarks' => $stockBatch->remarks ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByStatus = $request->input('filter_by_status');

        $stockBatchs = DB::table('stock_batch')
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('stock_batch_status', (array) $filterByStatus)
            )
            ->orderBy('reference_number')
            ->get();

        $response = $stockBatchs->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockBatchId = $row->id;
            $referenceNumber = $row->reference_number;
            $warehouseName = $row->warehouse_name;
            $batchStatus = $row->stock_batch_status;

            $statusClass = match ($batchStatus) {
                'Draft' => 'badge badge-secondary',
                'For Approval' => 'badge badge-warning',
                'Approved' => 'badge badge-success',
                'Cancelled' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$batchStatus.'</span>';

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stockBatchId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stockBatchId.'">
                    </div>
                ',
                'REFERENCE_NUMBER' => $referenceNumber,
                'WAREHOUSE' => $warehouseName,
                'STATUS' => $statusBadge,
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

        $stockBatchs = DB::table('stock_batch')
            ->select(['id', 'stock_batch_name', 'stock_batch'])
            ->orderBy('stock_batch_name')
            ->get();

        $response = $response->concat(
            $stockBatchs->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->stock_batch_name . ' (.' . $row->stock_batch . ')',
            ])
        )->values();

        return response()->json($response);
    }
}
