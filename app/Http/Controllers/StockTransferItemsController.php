<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferItems;
use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class StockTransferItemsController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_transfer_id' => ['required', 'integer', Rule::exists('stock_transfer', 'id')],
            'stock_level_id' => ['required', 'integer', Rule::exists('stock_level', 'id')],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $stockLevel = StockLevel::findOrFail($validated['stock_level_id']);

        if ($validated['quantity'] > $stockLevel->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer quantity cannot exceed available stock (' . $stockLevel->quantity . ')',
            ]);
        }

        $payload = [
            'stock_transfer_id' => $validated['stock_transfer_id'],
            'stock_level_id' => $validated['stock_level_id'],
            'quantity' => $validated['quantity'],
            'last_log_by' => Auth::id(),
        ];

        StockTransferItems::query()->create($payload);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer item has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('stock_transfer_items', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $stockTransferItems = StockTransferItems::query()->select(['id'])->findOrFail($referenceId);

            $stockTransferItems->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer item has been deleted successfully',
        ]);
    }

    public function generateTable(Request $request)
    {
        $stockTransferId = (int) $request->input('stock_transfer_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $stockTransferItems = DB::table('stock_transfer_items')
        ->where('stock_transfer_id', $stockTransferId)
        ->orderBy('stock_level_id')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $stockTransferItems->map(function ($row) use ($writeAccess, $logsAccess)  {
            $stockTransferItemsId = $row->id;
            $stockTransferId = $row->stock_transfer_id;
            $stockLevelId = $row->stock_level_id;
            $quantity = $row->quantity;

            $stockTransfer = StockTransfer::query()
            ->whereKey($stockTransferId)
            ->first();

            $stockLevel = StockLevel::query()
            ->whereKey($stockLevelId)
            ->first();

            $deleteButton = '';

            if($writeAccess > 0 && $stockTransfer->stock_transfer_status === 'Draft'){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-stock-transfer-items" data-reference-id="' . $stockTransferItemsId . '" title="Delete Item">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-stock-transfer-items-log-notes" data-reference-id="' . $stockTransferItemsId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'PRODUCT' => $stockLevel->product_name,
                'QUANTITY' => number_format($quantity, 2),
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
