<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use App\Models\UploadSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'            => ['nullable', 'integer'],
            'product_name'          => ['required', 'string'],
            'sku'                   => ['nullable', 'string'],
            'barcode'               => ['nullable', 'string'],
            'product_type'          => ['required', 'string'],
            'product_status'        => ['required', 'string'],
            'tax_classification'    => ['required', 'string'],
            'base_price'            => ['required', 'numeric'],
            'cost_price'            => ['required', 'numeric'],
            'base_unit_id'          => ['required', 'integer', Rule::exists('unit', 'id')],
            'inventory_flow'        => ['required', 'string'],
            'reorder_level'         => ['required', 'numeric'],
            'product_description'   => ['nullable', 'string'],
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

        $productId = $validated['product_id'] ?? null;

        if ($productId && Product::query()->whereKey($productId)->exists()) {
            $product = Product::query()->findOrFail($productId);
            $product->update($payload);
        } else {
            $product = Product::query()->create($payload);
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

    public function saveProductSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', Rule::exists('product', 'id')],
            'setting'    => ['required', 'string'],
            'value'      => ['required'],
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

        $settingMap = [
            'track-inventory'     => 'track_inventory',
            'is-addon'            => 'is_addon',
            'batch-tracking'      => 'batch_tracking',
            'expiration-tracking' => 'expiration_tracking',
        ];

        $settingKey = $validated['setting'];

        if (!array_key_exists($settingKey, $settingMap)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid setting provided.',
            ]);
        }

        $column = $settingMap[$settingKey];

        $product = Product::query()->findOrFail($validated['product_id']);

        $payload = [
            $column => $validated['value'],
            'last_log_by' => Auth::id(),
        ];

        $product->update($payload);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $product->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The product setting has been updated successfully.',
            'redirect_link' => $link,
        ]);
    }

    public function uploadProductImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('product', 'id')],
            'image'    => ['required', 'file'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'notExist' => false,
                'redirect_link' => $link,
                'message' => $validator->errors()->first() ?? 'Validation failed',
            ]);
        }

        $detailId = (int) $request->input('detailId');

        $product = Product::query()->findOrFail($detailId);

        $uploadSettingId = 8;

        $uploadSetting = UploadSetting::query()->findOrFail($uploadSettingId);

        $maxMb = (float) $uploadSetting->max_file_size;
        $maxKb = (int) round($maxMb * 1024);

        $allowedExt = $uploadSetting->uploadSettingFileExtensions()
            ->pluck('file_extension')
            ->map(fn ($e) => strtolower((string) $e))
            ->unique()
            ->values()
            ->all();

        $file = $request->file('image');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file',
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedExt, true)) {
            return response()->json([
                'success' => false,
                'message' => 'The file uploaded is not supported',
            ]);
        }

        $sizeValidator = Validator::make($request->all(), [
            'image' => ['max:' . $maxKb],
        ]);

        if ($sizeValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The product image exceeds the maximum allowed size of ' . $maxMb . ' MB',
            ]);
        }

        DB::transaction(function () use ($product, $file, $ext) {
            $existing = (string) ($product->product_image ?? '');
            if ($existing !== '') {
                $path = ltrim($existing, '/');
                $path = Str::replaceFirst('storage/', '', $path);
                $path = Str::replaceFirst('app/public/', '', $path);
                $path = Str::replaceFirst('public/', '', $path);

                if ($path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }

            $fileName = Str::random(20);
            $fileNew  = $fileName . '.' . $ext;

            $relativePath = "product/{$product->id}/{$fileNew}";
            $file->storeAs("product/{$product->id}", $fileNew, 'public');

            $product->update([
                'product_image' => $relativePath,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'The product image has been updated successfully',
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
            'batchTracking'         => $product->batch_tracking ?? 'No',
            'expirationTracking'    => $product->expiration_tracking ?? 'No',
            'productImage'          => $productImageUrl,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        //$filterByStatus = $request->input('filter_by_user_status');

        $products = DB::table('product')
        ->orderBy('product_name')
        ->get();

        $response = $products->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $productId = $row->id;
            $productName = $row->product_name;
            $productDescription = $row->product_description;
            $sku = $row->sku;
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
                'PRODUCT_TYPE' => $productType,
                'BASE_PRICE' => number_format($basePrice, 2),
                'STATUS' => $activeBadge,
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

        $product = DB::table('product')
            ->select(['id', 'product_name'])
            ->orderBy('product_name')
            ->get();

        $response = $response->concat(
            $product->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->product_name,
            ])
        )->values();

        return response()->json($response);
    }

    public function generateBomOptions(Request $request)
    {
        $productId = $request->input('product_id');
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $boms = DB::table('product')
            ->select(['id', 'product_name'])
            ->whereNotIn('id', function ($query) use ($productId) {
                $query->select('product_id')
                    ->from('product_bom')
                    ->where('product_id', $productId);
            })
            ->where('track_inventory', 'Yes')
            ->where('product_status', 'Active')
            ->where('id', '!=', $productId)
            ->orderBy('product_name')
            ->get();

        $response = $response->concat(
            $boms->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->product_name,
            ])
        )->values();

        return response()->json($response);
    }

    public function generateAddOnOptions(Request $request)
    {
        $productId = $request->input('product_id');
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $boms = DB::table('product')
            ->select(['id', 'product_name'])
            ->whereNotIn('id', function ($query) use ($productId) {
                $query->select('product_id')
                    ->from('product_addon')
                    ->where('product_id', $productId);
            })
            ->where('is_addon', 'Yes')
            ->where('product_status', 'Active')
            ->where('id', '!=', $productId)
            ->orderBy('product_name')
            ->get();

        $response = $response->concat(
            $boms->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->product_name,
            ])
        )->values();

        return response()->json($response);
    }
}
