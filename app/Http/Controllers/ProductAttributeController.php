<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProductAttributeController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_attribute_id' => ['nullable', 'integer'],
            'product_id' => ['required', 'integer', Rule::exists('product', 'id')],
            'attribute_id' => ['required', 'integer', Rule::exists('attribute', 'id')]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $productId = $validated['product_id'] ?? null;
        $attributeId = $validated['attribute_id'] ?? null;

        $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

        $attributeName = (string) Attribute::query()
            ->whereKey($attributeId)
            ->value('attribute_name');

        $payload = [
            'product_id' => $productId,
            'product_name' => $productName,
            'attribute_id' => $attributeId,
            'attribute_name' => $attributeName,
            'last_log_by' => Auth::id(),
        ];

        ProductAttribute::query()->create($payload);

        return response()->json([
            'success' => true,
            'message' => 'The product attribute has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('product_attribute', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $productAttribute = ProductAttribute::query()->select(['id'])->findOrFail($referenceId);

            $productAttribute->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The product attribute has been deleted successfully',
        ]);
    }

    public function generateTable(Request $request)
    {
        $productId = (int) $request->input('product_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $productAttributes = DB::table('product_attribute')
        ->where('product_id', $productId)
        ->orderBy('attribute_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $productAttributes->map(function ($row) use ($writeAccess, $logsAccess)  {
            $productAttributeId = $row->id;
            $attributeName = $row->attribute_name;

            $deleteButton = '';
            if($writeAccess > 0){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-attribute" data-reference-id="' . $productAttributeId . '" title="Delete Product Attribute">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-attribute-log-notes" data-reference-id="' . $productAttributeId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'ATTRIBUTE' => $attributeName,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
