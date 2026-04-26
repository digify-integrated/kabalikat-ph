<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProductCategoryMapController extends Controller
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

        $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

        $productCategoryIds = $request->input('product_category_id') ?? [];

        if (is_string($productCategoryIds)) {
            $productCategoryIds = explode(',', $productCategoryIds);
        }

        if (!empty($productCategoryIds)) {
            ProductCategoryMap::query()
            ->where('product_id', $productId)
            ->delete();

            foreach ($productCategoryIds as $productCategoryId) {
                $productCategory = ProductCategory::find($productCategoryId);

                if (!$productCategory) {
                    continue;
                }

                $productCategoryName = (string) ProductCategory::query()
                ->whereKey($productCategoryId)
                ->value('product_category_name');

                $payload = [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'product_category_id' => $productCategoryId,
                    'product_category_name' => $productCategoryName,
                    'last_log_by' => Auth::id(),
                ];

                ProductCategoryMap::query()->create($payload);
            }
        }        

        return response()->json([
            'success' => true,
            'message' => 'The product category has been saved successfully',
        ]);
    }
}
