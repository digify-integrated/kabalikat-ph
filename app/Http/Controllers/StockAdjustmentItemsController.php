<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItems;
use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class StockAdjustmentItemsController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_adjustment_id' => ['required', 'integer', Rule::exists('stock_adjustment', 'id')],
            'stock_level_id' => ['required', 'integer', Rule::exists('stock_level', 'id')],
            'adjustment_type' => ['required', 'string'],
            'adjustment_quantity' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $stockLevelId = $validated['stock_level_id'] ?? null;
        $adjustmentType = $validated['adjustment_type'] ?? null;
        $adjustmentQuantity = $validated['adjustment_quantity'] ?? 0;        

        $currentQuantity = (string) StockLevel::query()
            ->whereKey($stockLevelId)
            ->value('quantity');

        if($adjustmentType === 'Add Stock'){
            $newQuantity = $currentQuantity + $adjustmentQuantity;
        }
        else if($adjustmentType === 'Remove Stock'){
            $newQuantity = $currentQuantity - $adjustmentQuantity;
        }
        else{
            $newQuantity = $adjustmentQuantity;
        }

        $payload = [
            'stock_adjustment_id' => $validated['stock_adjustment_id'],
            'stock_level_id' => $stockLevelId,
            'adjustment_type' => $validated['adjustment_type'],
            'adjustment_quantity' => $adjustmentQuantity ?? 0,
            'current_quantity' => $currentQuantity ?? 0,
            'new_quantity' => $newQuantity ?? 0,
            'last_log_by' => Auth::id(),
        ];

        StockAdjustmentItems::query()->create($payload);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment item has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('stock_adjustment_items', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $stockAdjustmentItems = StockAdjustmentItems::query()->select(['id'])->findOrFail($referenceId);

            $stockAdjustmentItems->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment item has been deleted successfully',
        ]);
    }

    public function generateTable(Request $request)
    {
        $stockAdjustmentItemsId = (int) $request->input('stock_adjustment_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $stockAdjustmentItems = DB::table('stock_adjustment_items')
        ->where('stock_adjustment_id', $stockAdjustmentItemsId)
        ->orderBy('stock_level_id')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $stockAdjustmentItems->map(function ($row) use ($writeAccess, $logsAccess)  {
            $stockAdjustmentItemsId = $row->id;
            $stockAdjustmentId = $row->stock_adjustment_id;
            $stockLevelId = $row->stock_level_id;
            $adjustmentType = $row->adjustment_type;
            $currentQuantity = $row->current_quantity;
            $newQuantity = $row->new_quantity;

            $stockAdjustment = StockAdjustment::query()
            ->whereKey($stockAdjustmentId)
            ->first();

            $stockLevel = StockLevel::query()
            ->whereKey($stockLevelId)
            ->first();

            $deleteButton = '';

            if($writeAccess > 0 && $stockAdjustment->stock_adjustment_status === 'Draft'){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-stock-adjustment-items" data-reference-id="' . $stockAdjustmentItemsId . '" title="Delete Item">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-stock-adjustment-items-log-notes" data-reference-id="' . $stockAdjustmentItemsId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'PRODUCT' => $stockLevel->product_name,
                'WAREHOUSE' => $stockLevel->warehouse_name,
                'ADJUSTMENT_TYPE' => $adjustmentType,
                'QUANTITY' => number_format($currentQuantity, 2) . ' -> ' . number_format($newQuantity, 2),
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
