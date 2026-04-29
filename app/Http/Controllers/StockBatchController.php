<?php

namespace App\Http\Controllers;

use App\Models\StockBatch;
use App\Models\Product;
use App\Models\StockLevel;
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
            'product_id' => ['required', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'batch_number' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'cost_per_unit' => ['required', 'numeric', 'min:0.01'],
            'expiration_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
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

        $productId = (int) $validated['product_id'];
        $warehouseId = (int) $validated['warehouse_id'];

        $validated['expiration_date'] = !empty($validated['expiration_date'])
            ? Carbon::parse($validated['expiration_date'])->format('Y-m-d')
            : null;

        $validated['received_date'] = !empty($validated['received_date'])
            ? Carbon::parse($validated['received_date'])->format('Y-m-d')
            : null;

        $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

        $warehouseName = (string) Warehouse::query()
            ->whereKey($warehouseId)
            ->value('warehouse_name');

        $payload = [
            'product_id' => $productId,
            'product_name' => $productName,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'quantity' => $validated['quantity'],
            'batch_number' => $validated['batch_number'],
            'cost_per_unit' => $validated['cost_per_unit'],
            'remarks' => $validated['remarks'],
            'expiration_date' => $validated['expiration_date'],
            'received_date' => $validated['received_date'],
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
            ->select(['id', 'batch_status'])
            ->findOrFail($detailId);

            if ($stockBatch->batch_status !== 'Draft') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The stock batch is not "Draft" status',
                ]);
            }

            $stockBatch->update([
                'batch_status' => 'For Approval',
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
                ->select(['id', 'batch_status'])
                ->findOrFail($detailId);

            if ($stockBatch->batch_status !== 'For Approval' && $stockBatch->batch_status !== 'Draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock batch is not "For Approval" or "Draft" status',
                ]);
            }

            $stockBatch->update([
                'batch_status' => 'Cancelled',
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
                ->select(['id', 'batch_status'])
                ->findOrFail($detailId);

            if ($stockBatch->batch_status !== 'For Approval') {
                 return response()->json([
                    'success' => false,
                    'message' => 'The stock batch is not in "For Approval" status',
                ]);
            }

            $stockBatch->update([
                'batch_status' => 'Draft',
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
            ->findOrFail($detailId);

            if ($stockBatch->batch_status !== 'For Approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'The stock batch is not "For Approval" status',
                ]);
            }

            $stockBatch->update([
                'batch_status' => 'Approved',
                'approved_date' => Carbon::now()
            ]);

            $reorderLevel = DB::table('product')
                ->where('id', $stockBatch->product_id)
                ->value('reorder_level');

            $quantity = $stockBatch->quantity;

            if ($quantity == 0) {
                $stockStatus = 'Out of Stock';
            } elseif ($quantity <= $reorderLevel) {
                $stockStatus = 'Low Stock';
            } else {
                $stockStatus = 'In Stock';
            }

            $stockLevel = StockLevel::create([
                'product_id' => $stockBatch->product_id,
                'product_name' => $stockBatch->product_name,
                'warehouse_id' => $stockBatch->warehouse_id,
                'warehouse_name' => $stockBatch->warehouse_name,
                'stock_status' => $stockStatus,
                'quantity' => $quantity,
                'stock_batch_id' => $stockBatch->id,
                'expiration_date' => $stockBatch->expiration_date,
                'received_date' => $stockBatch->received_date,
                'cost_per_unit' => $stockBatch->cost_per_unit,
                'last_log_by' => auth()->id(),
            ]);

            DB::table('stock_movement')->insert([
                'stock_level_id' => $stockLevel->id,
                'movement_type' => 'In',
                'quantity' => $quantity,
                'reference_type' => 'Stock Batch',
                'reference_id' => $stockBatch->id,
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
            'message' => 'The stock batch has been approved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function approveMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_batch', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $stockBatchs = StockBatch::query()
                ->whereIn('id', $ids)
                ->where('batch_status', 'For Approval')
                ->get();

            StockBatch::query()
                ->whereIn('id', $stockBatchs->pluck('id'))
                ->update([
                    'batch_status' => 'Approved',
                    'approved_date' => Carbon::now()
                ]);

            foreach ($stockBatchs as $stockBatch) {
                $reorderLevel = DB::table('product')
                    ->where('id', $stockBatch->product_id)
                    ->value('reorder_level');

                $quantity = $stockBatch->quantity;

                $stockStatus = match (true) {
                    $quantity == 0 => 'Out of Stock',
                    $quantity <= $reorderLevel => 'Low Stock',
                    default => 'In Stock',
                };

                $stockLevel = StockLevel::create([
                    'product_id' => $stockBatch->product_id,
                    'product_name' => $stockBatch->product_name,
                    'warehouse_id' => $stockBatch->warehouse_id,
                    'warehouse_name' => $stockBatch->warehouse_name,
                    'stock_status' => $stockStatus,
                    'quantity' => $quantity,
                    'stock_batch_id' => $stockBatch->id,
                    'expiration_date' => $stockBatch->expiration_date,
                    'received_date' => $stockBatch->received_date,
                    'cost_per_unit' => $stockBatch->cost_per_unit,
                    'last_log_by' => auth()->id(),
                ]);

                DB::table('stock_movement')->insert([
                    'stock_level_id' => $stockLevel->id,
                    'movement_type' => 'In',
                    'quantity' => $quantity,
                    'reference_type' => 'Stock Batch',
                    'reference_id' => $stockBatch->batch_number,
                    'remarks' => 'Initial stock from batch approval',
                    'last_log_by' => auth()->id(),
                ]);
            }
        });
        return response()->json([
            'success' => true,
            'message' => 'The selected stock batchs have been approved successfully',
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
            'productId' => $stockBatch->product_id ?? null,
            'warehouseId' => $stockBatch->warehouse_id ?? null,
            'quantity' => $stockBatch->quantity ?? 0.01,
            'batchNumber' => $stockBatch->batch_number ?? null,
            'costPerUnit' => $stockBatch->cost_per_unit ?? 0.01,
            'remarks' => $stockBatch->remarks ?? null,
            'expirationDate' => $stockBatch->expiration_date
            ? date('M d, Y', strtotime($stockBatch->expiration_date))
            : '',
            'receivedDate' => $stockBatch->received_date
            ? date('M d, Y', strtotime($stockBatch->received_date))
            : '',
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $filterByProduct = $request->input('filter_by_product');
        $filterByWarehouse = $request->input('filter_by_warehouse');
        $filterByExpirationDate = $request->input('filter_by_expiration_date');
        $filterByReceivedDate = $request->input('filter_by_received_date');
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

        $expirationRange = $parseRange($filterByExpirationDate);
        $receivedRange = $parseRange($filterByReceivedDate);

        $stockBatchs = DB::table('stock_batch')
            ->when(!empty($filterByProduct), fn($q) =>
                $q->whereIn('product_id', (array) $filterByProduct)
            )
            ->when(!empty($filterByWarehouse), fn($q) =>
                $q->whereIn('warehouse_id', (array) $filterByWarehouse)
            )
            ->when($expirationRange, fn($q) =>
                $q->whereBetween('expiration_date', $expirationRange)
            )
            ->when($receivedRange, fn($q) =>
                $q->whereBetween('received_date', $receivedRange)
            )
            ->when(!empty($filterByStatus), fn($q) =>
                $q->whereIn('batch_status', (array) $filterByStatus)
            )
            ->orderBy('product_name')
            ->get();

        $response = $stockBatchs->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockBatchId = $row->id;
            $productName = $row->product_name;
            $warehouseName = $row->warehouse_name;
            $batchStatus = $row->batch_status;
            $quantity = $row->quantity;
            $batchNumber = $row->batch_number;
            $costPerUnit = $row->cost_per_unit;
            $expiration_date = $row->expiration_date
            ? date('M d, Y', strtotime($row->expiration_date))
            : 'No expiry';
            $received_date = $row->received_date
            ? date('M d, Y', strtotime($row->received_date))
            : 'No received date';

            $statusClass = match ($batchStatus) {
                'Draft' => 'badge badge-secondary',
                'For Approval' => 'badge badge-warning',
                'Approved' => 'badge badge-success',
                'Cancelled' => 'badge badge-danger',
                default => 'badge badge-light',
            };

            $statusBadge = '<span class="'.$statusClass.'">'.$batchStatus.'</span>';

           if ($row->expiration_date) {
                $expDate = Carbon::parse($row->expiration_date);
                $today = Carbon::today();

                $formattedDate = $expDate->format('M d, Y');

                if ($expDate->isPast()) {
                    $daysExpired = $expDate->diffInDays($today);
                    $expiration_date = '
                        '.$formattedDate.'<br>
                        <small class="text-danger">(Expired '.$daysExpired.' day'.($daysExpired > 1 ? 's' : '').' ago)</small>
                    ';
                } elseif ($expDate->isFuture()) {
                    $daysRemaining = $today->diffInDays($expDate);
                    $expiration_date = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expiring in '.$daysRemaining.' day'.($daysRemaining > 1 ? 's' : '').')</small>
                    ';
                } else {
                    $expiration_date = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expires today)</small>
                    ';
                }
            } else {
                $expiration_date = 'No expiry';
            }

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
                'PRODUCT' => $productName,
                'WAREHOUSE' => $warehouseName,
                'BATCH_NUMBER' => $batchNumber,
                'QUANTITY' => number_format($quantity, 2),
                'COST_PER_UNIT' => number_format($costPerUnit, 2),
                'STATUS' => $statusBadge,
                'EXPIRATION_DATE' => $expiration_date,
                'RECEIVED_DATE' => $received_date,
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
