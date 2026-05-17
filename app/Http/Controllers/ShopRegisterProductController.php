<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShopRegister;
use App\Models\ShopRegisterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShopRegisterProductController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_register_id' => ['required', 'integer', Rule::exists('shop_register', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $shopRegisterId = $validated['shop_register_id'] ?? null;

        $shopRegisterName = (string) ShopRegister::query()
            ->whereKey($shopRegisterId)
            ->value('shop_register_name');
        
        $productIds = (array) $request->input('product_id', []);

        if (empty($productIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select the products you wish to add to the shop register',
            ]);
        }

        foreach ($productIds as $productId) {
            $product = Product::find($productId);

            if (!$product) {
                continue;
            }

            $productName = (string) Product::query()
            ->whereKey($productId)
            ->value('product_name');

            $payload = [
                'shop_register_id' => $shopRegisterId,
                'shop_register_name' => $shopRegisterName,
                'product_id' => $productId,
                'product_name' => $productName,
                'last_log_by' => Auth::id(),
            ];

            ShopRegisterProduct::query()->create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'The product has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('shop_register_product', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $shopRegisterProduct = ShopRegisterProduct::query()->select(['id'])->findOrFail($referenceId);

            $shopRegisterProduct->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The product has been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('shop_register_product', 'id')],
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

        $shopRegisterProduct = DB::table('shop_register_product')
            ->where('id', $validated['referenceId'])
            ->first();

        if (!$shopRegisterProduct) {
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
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'productId' => $shopRegisterProduct->product_id ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $shopRegisterId = (int) $request->input('shop_register_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $shopRegisterProducts = DB::table('shop_register_product')
        ->where('shop_register_id', $shopRegisterId)
        ->orderBy('product_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $shopRegisterProducts->map(function ($row) use ($writeAccess, $logsAccess)  {
            $shopRegisterProductId = $row->id;
            $productName = $row->product_name;

            if($writeAccess > 0){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-product" data-reference-id="' . $shopRegisterProductId . '" title="Delete Product">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-product-log-notes" data-reference-id="' . $shopRegisterProductId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'PRODUCT' => $productName,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
