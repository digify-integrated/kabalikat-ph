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
            'product_id' => ['required', 'integer', Rule::exists('product', 'id')],
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
        
        $attributeIds = $request->input('attribute_id') ?? [];

        if (empty($attributeIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select the attributes you wish to add to the product',
            ]);
        }

        foreach ($attributeIds as $attributeId) {
            $attribute = Attribute::find($attributeId);

            if (!$attribute) {
                continue;
            }

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
        }

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
            $attributeId = $row->attribute_id;
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

            $attributeValues = DB::table('attribute_value')
                ->where('attribute_id', $attributeId)
                ->pluck('attribute_value');

            $badges = $attributeValues
            ->chunk(5)
            ->map(function ($group) {
                return '<div class="mb-1">' .
                    $group->map(function ($ext) {
                        return '<span class="badge bg-primary me-1">'.e($ext).'</span>';
                    })->implode('') .
                '</div>';
            })
            ->implode('');

            return [
                'ATTRIBUTE' => $attributeName,
                'ATTRIBUTE_VALUE' => $badges,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
