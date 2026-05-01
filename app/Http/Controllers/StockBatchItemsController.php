<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockBatchItems;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class StockBatchItemsController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_batch_id' => ['required', 'integer', Rule::exists('stock_batch', 'id')],
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
            'stock_batch_id' => $validated['stock_batch_id'],
            'product_id' => $productId,
            'product_name' => $productName,
            'batch_number' => $validated['batch_number'],
            'cost_per_unit' => $validated['cost_per_unit'] ?? 0,
            'expiration_date' => $validated['expiration_date'],
            'received_date' => $validated['received_date'],
            'quantity' => $validated['quantity'] ?? 0.01,
            'last_log_by' => Auth::id(),
        ];

        StockBatchItems::query()->create($payload);

        return response()->json([
            'success' => true,
            'message' => 'The stock batch item has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('stock_batch_items', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $stockBatchItems = StockBatchItems::query()->select(['id'])->findOrFail($referenceId);

            $stockBatchItems->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The stock batch item has been deleted successfully',
        ]);
    }

    public function generateTable(Request $request)
    {
        $stockBatchItemsId = (int) $request->input('stock_batch_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $stockBatchItems = DB::table('stock_batch_items')
        ->where('stock_batch_id', $stockBatchItemsId)
        ->orderBy('product_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $stockBatchItems->map(function ($row) use ($writeAccess, $logsAccess)  {
            $stockBatchItemsId = $row->id;
            $stockBatchId = $row->stock_batch_id;
            $productName = $row->product_name;
            $batchNumber = $row->batch_number;
            $costPerUnit = $row->cost_per_unit;
            $quantity = $row->quantity;

            $stockBatch = StockBatch::query()
            ->whereKey($stockBatchId)
            ->first();

            $expirationDate = $row->expiration_date
                ? date('M d, Y', strtotime($row->expiration_date))
                : null;

            $receivedDate = $row->received_date
                ? date('M d, Y', strtotime($row->received_date))
                : 'No received date';

            if (!empty($expirationDate)) {
                $expDate = Carbon::parse($expirationDate);
                $today = Carbon::today();

                $formattedDate = $expDate->format('M d, Y');

                if ($expDate->isPast()) {
                    $daysExpired = $expDate->diffInDays($today);
                    $expirationDate = '
                        '.$formattedDate.'<br>
                        <small class="text-danger">(Expired '.$daysExpired.' day'.($daysExpired > 1 ? 's' : '').' ago)</small>
                    ';
                } elseif ($expDate->isFuture()) {
                    $daysRemaining = $today->diffInDays($expDate);
                    $expirationDate = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expiring in '.$daysRemaining.' day'.($daysRemaining > 1 ? 's' : '').')</small>
                    ';
                } else {
                    $expirationDate = '
                        '.$formattedDate.'<br>
                        <small class="text-warning">(Expires today)</small>
                    ';
                }
            } else {
                $expirationDate = 'No expiry';
            }

            $deleteButton = '';

            if($writeAccess > 0 && $stockBatch->stock_batch_status === 'Draft'){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-stock-batch-items" data-reference-id="' . $stockBatchItemsId . '" title="Delete Item">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-stock-batch-items-log-notes" data-reference-id="' . $stockBatchItemsId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'PRODUCT' => $productName,
                'BATCH_NUMBER' => $batchNumber,
                'QUANTITY' => number_format($quantity, 2),
                'COST_PER_UNIT' => number_format($costPerUnit, 2),
                'BATCH_VALUE' => number_format(($quantity * $costPerUnit), 2),
                'EXPIRATION_DATE' => $expirationDate,
                'RECEIVED_DATE' => $receivedDate,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
