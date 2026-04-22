<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProductAddonController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_addon_id' => ['nullable', 'integer'],
            'product_id' => ['required', 'integer', Rule::exists('product', 'id')],
            'attribute_id' => ['required', 'integer', Rule::exists('attribute', 'id')],
            'max_quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $attributeId = $validated['attribute_id'] ?? null;
        $productId = $validated['product_id'] ?? null;

        $attributeName = (string) Attribute::query()
            ->whereKey($attributeId)
            ->value('attribute_name');

        $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

        $payload = [
            'product_id' => $productId,
            'product_name' => $productName,
            'attribute_id' => $attributeId,
            'attribute_name' => $attributeName,
            'max_quantity' => $validated['max_quantity'] ?? 0.01,
            'last_log_by' => Auth::id(),
        ];

        $productAddonId = $validated['product_addon_id'] ?? null;

        if ($productAddonId && ProductAddon::query()->whereKey($productAddonId)->exists()) {
            $productAddon = ProductAddon::query()->findOrFail($productAddonId);
            $productAddon->update($payload);
        } else {
            $productAddon = ProductAddon::query()->create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'The product add-on has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('product_addon', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $productAddon = ProductAddon::query()->select(['id'])->findOrFail($referenceId);

            $productAddon->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The product add-on has been deleted successfully',
        ]);
    }

    public function generateTable(Request $request)
    {
        $productId = (int) $request->input('product_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $productAddons = DB::table('product_addon')
        ->where('product_id', $productId)
        ->orderBy('addon_product_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $productAddons->map(function ($row) use ($writeAccess, $logsAccess)  {
            $productAddonId = $row->id;
            $addonProductName = $row->addon_product_name;
            $maxQuantity = $row->max_quantity;

            $deleteButton = '';
            if($writeAccess > 0){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-addon" data-reference-id="' . $productAddonId . '" title="Delete Product Add-on">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-addon-log-notes" data-reference-id="' . $productAddonId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'ADDON_PRODUCT' => $addonProductName,
                'MAX_QUANTITY' => number_format($maxQuantity, 2),
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
