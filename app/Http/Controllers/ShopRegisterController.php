<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShopRegisterController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['nullable', 'integer'],
            'product_name' => ['required', 'string'],
            'sku' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string'],
            'product_type' => ['required', 'string'],
            'product_status' => ['required', 'string'],
            'tax_classification' => ['required', 'string'],
            'base_price' => ['required', 'numeric'],
            'cost_price' => ['required', 'numeric'],
            'base_unit_id' => ['required', 'integer', Rule::exists('unit', 'id')],
            'inventory_flow' => ['required', 'string'],
            'reorder_level' => ['required', 'numeric'],
            'product_description' => ['nullable', 'string'],
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

        $baseUnitId = (int) $validated['base_unit_id'];

        $baseUnitName = (string) Unit::query()
            ->whereKey($baseUnitId)
            ->value('unit_name');

        $baseUnitDetails = Unit::query()->find($baseUnitId);
        $baseUnitName = $baseUnitDetails?->unit_name;
        $baseUnitabbreviation = $baseUnitDetails?->abbreviation;
        

        $payload = [
            'product_name' => $validated['product_name'],
            'sku' => $validated['sku'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'product_type' => $validated['product_type'] ?? null,
            'product_status' => $validated['product_status'] ?? 'Active',
            'tax_classification' => $validated['tax_classification'] ?? null,
            'base_price' => $validated['base_price'] ?? 0,
            'cost_price' => $validated['cost_price'] ?? 0,
            'base_unit_id' => $validated['base_unit_id'] ?? null,
            'base_unit_name' => $baseUnitName,
            'base_unit_abbreviation' => $baseUnitabbreviation,
            'inventory_flow' => $validated['inventory_flow'] ?? null,
            'reorder_level' => $validated['reorder_level'] ?? 0,
            'product_description' => $validated['product_description'] ?? null,
            'last_log_by' => Auth::id(),
        ];   

        $product = isset($validated['product_id'])
            ? Product::query()->find($validated['product_id'])
            : null;

        if ($product) {
            $product->update($payload);
        } else {
            $product = Product::query()->create($payload);
        }

        $lastLogBy = Auth::id();
        $productName = $product->product_name;
        $productId = $product->id;

        $updates = [
            [
                'model' => Product::class,
                'where' => ['parent_product_id' => $productId],
                'data' => ['parent_product_name' => $productName],
            ],
            [
                'model' => ProductCategoryMap::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => ProductAttribute::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => ProductBOM::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => ProductBOM::class,
                'where' => ['bom_product_id' => $productId],
                'data' => ['bom_product_name' => $productName],
            ],
            [
                'model' => ProductAddon::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => ProductAddon::class,
                'where' => ['addon_product_id' => $productId],
                'data' => ['addon_product_name' => $productName],
            ],
            [
                'model' => InventoryLot::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => StockLevel::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => StockBatchItems::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => StockMovement::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => PurchaseOrderItems::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => PurchaseOrderReceiptItems::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
            [
                'model' => PurchaseOrderCancellations::class,
                'where' => ['product_id' => $productId],
                'data' => ['product_name' => $productName],
            ],
        ];

        foreach ($updates as $update) {
            $update['model']::query()
                ->where($update['where'])
                ->update([
                    ...$update['data'],
                    'last_log_by' => $lastLogBy,
                ]);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $product->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The product has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('product', 'id')],
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
            $product = Product::query()->select(['id', 'product_image'])->findOrFail($detailId);

            $path = ltrim((string) $product->product_image, '/');
            $path = Str::replaceFirst('storage/', '', $path);
            $path = Str::replaceFirst('app/public/', '', $path);
            $path = Str::replaceFirst('public/', '', $path);

            if ($path !== '') {
                Storage::disk('public')->delete($path);
            }

            $product->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The product has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('product', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $authId = Auth::id();

            $ids = array_values(array_diff($ids, [$authId]));
            if (empty($ids)) return;

            $products = Product::query()
                ->whereIn('id', $ids)
                ->get(['id', 'product_image']);

            foreach ($products as $product) {
                $existing = (string) ($product->product_image ?? '');
                if ($existing === '') continue;

                $path = ltrim($existing, '/');
                $path = Str::replaceFirst('storage/', '', $path);
                $path = Str::replaceFirst('app/public/', '', $path);
                $path = Str::replaceFirst('public/', '', $path);

                if ($path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }

            Product::query()->whereIn('id', $ids)->delete();
        });


        return response()->json([
            'success' => true,
            'message' => 'The selected products have been deleted successfully',
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

        $product = DB::table('product')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$product) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Product not found',
            ]);
        }

        $defaultProductImage = asset('assets/media/default/upload-placeholder.png');
        $path = trim((string) ($product->product_image ?? ''));

        $productImageUrl = $path !== '' && Storage::disk('public')->exists($path)
            ? Storage::url($path)
            : $defaultProductImage;

        $productCategoryIds = DB::table('product_category_map')
            ->where('product_id', $product->id)
            ->pluck('product_category_id')
            ->toArray();

        return response()->json([
            'success'               => true,
            'notExist'              => false,
            'productName'           => $product->product_name ?? null,
            'sku'                   => $product->sku ?? null,
            'barcode'               => $product->barcode ?? null,
            'productType'           => $product->product_type ?? null,
            'productStatus'         => $product->product_status ?? null,
            'taxClassification'     => $product->tax_classification ?? null,
            'basePrice'             => $product->base_price ?? null,
            'costPrice'             => $product->cost_price ?? null,
            'baseUnitId'            => $product->base_unit_id ?? null,
            'inventoryFlow'         => $product->inventory_flow ?? null,
            'reorderLevel'          => $product->reorder_level ?? null,
            'productDescription'    => $product->product_description ?? null,
            'trackInventory'        => $product->track_inventory ?? 'Yes',
            'isAddon'               => $product->is_addon ?? 'No',
            'showOnPos'             => $product->show_on_pos ?? 'No',
            'isPurchasable'         => $product->is_purchasable ?? 'Yes',
            'productImage'          => $productImageUrl,
            'productCategoryId'     => $productCategoryIds,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByProductType = $request->input('filter_by_product_type');
        $filterByProductStatus = $request->input('filter_by_product_status');

        $products = DB::table('product')
        ->when(!empty($filterByProductType), function ($q) use ($filterByProductType) {
            $q->where('product_type', $filterByProductType);
        })
        ->when(!empty($filterByProductStatus), function ($q) use ($filterByProductStatus) {
            $q->where('product_status', $filterByProductStatus);
        })
        ->orderBy('product_name')
        ->get();

        $response = $products->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $productId = $row->id;
            $productName = $row->product_name;
            $productDescription = $row->product_description;
            $parentProductName = $row->parent_product_name ?? '--';
            $sku = $row->sku ?? '--';
            $barcode = $row->barcode ?? '--';
            $productType = $row->product_type;
            $basePrice = $row->base_price;
            $productStatus = $row->product_status;
            $class = $productStatus === 'Active' ? 'success' : 'danger';
            $activeBadge = "<span class=\"badge badge-light-{$class}\">{$productStatus}</span>";
            
            $defaultProductImage = asset('assets/media/default/upload-placeholder.png');

            $path = trim((string) ($row->product_image ?? ''));

            $productImageLUrl = $path !== '' && Storage::disk('public')->exists($path)
                ? Storage::url($path)
                : $defaultProductImage;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $productId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$productId.'">
                    </div>
                ',
                'PRODUCT' => '
                    <div class="d-flex align-items-center">
                        <img src="'.$productImageLUrl.'" alt="product-image" width="45" />
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$productName.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$productDescription.'</small>
                            </div>
                        </div>
                    </div>
                ',
                'SKU' => $sku,
                'BARCODE' => $barcode,
                'PARENT_PRODUCT' => $parentProductName,
                'PRODUCT_TYPE' => $productType,
                'BASE_PRICE' => number_format($basePrice, 2),
                'STATUS' => $activeBadge,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
