<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBOM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProductBOMController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', Rule::exists('product', 'id')],
            'bom_product_id' => ['required', 'integer', Rule::exists('product', 'id')],
            'quantity' => ['required', 'numeric'],
            'stock_policy' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $productId = $validated['product_id'] ?? null;
        $bomProductId = $validated['bom_product_id'] ?? null;

        $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

        $bomProductName = (string) Product::query()
            ->whereKey($bomProductId)
            ->value('product_name');

        $payload = [
            'product_id' => $productId,
            'product_name' => $productName,
            'bom_product_id' => $bomProductId,
            'bom_product_name' => $bomProductName,
            'quantity' => $validated['quantity'] ?? 0.01,
            'stock_policy' => $validated['stock_policy'] ?? 'Strict',
            'last_log_by' => Auth::id(),
        ];

        ProductBOM::query()->create($payload);

        return response()->json([
            'success' => true,
            'message' => 'The component has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('product_bom', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $productBom = ProductBOM::query()->select(['id'])->findOrFail($referenceId);

            $productBom->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The component has been deleted successfully',
        ]);
    }

    public function generateTable(Request $request)
    {
        $productId = (int) $request->input('product_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $productBoms = DB::table('product_bom')
        ->where('product_id', $productId)
        ->orderBy('bom_product_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $productBoms->map(function ($row) use ($writeAccess, $logsAccess)  {
            $productBomId = $row->id;
            $bomProductName = $row->bom_product_name;
            $quantity = $row->quantity;
            $stockPolicy = $row->stock_policy;

            $deleteButton = '';
            if($writeAccess > 0){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-bom" data-reference-id="' . $productBomId . '" title="Delete Component">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-bom-log-notes" data-reference-id="' . $productBomId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'BOM_PRODUCT' => $bomProductName,
                'QUANTITY' => number_format($quantity, 2),
                'STOCK_POLICY' => $stockPolicy,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
