<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ShopRegister;
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
            'shop_register_id' => ['nullable', 'integer'],
            'shop_register_name' => ['required', 'string'],
            'company_id' => ['required', 'integer', Rule::exists('company', 'id')],
            'is_restaurant' => ['required', 'string'],
            'shop_register_status' => ['required', 'string'],
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

        $companyId = (int) $validated['company_id'];

        $companyName = (string) Company::query()
            ->whereKey($companyId)
            ->value('company_name');        

        $payload = [
            'shop_register_name' => $validated['shop_register_name'],
            'company_id' => $companyId,
            'company_name' => $companyName,
            'is_restaurant' => $validated['is_restaurant'] ?? 'No',
            'shop_register_status' => $validated['shop_register_status'] ?? 'Active',
            'last_log_by' => Auth::id(),
        ];   

        $product = isset($validated['shop_register_id'])
            ? ShopRegister::query()->find($validated['shop_register_id'])
            : null;

        if ($product) {
            $product->update($payload);
        } else {
            $product = ShopRegister::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $product->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The shop register has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('shop_register_id', 'id')],
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
            $shopRegister = ShopRegister::query()->select(['id'])->findOrFail($detailId);

            $shopRegister->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The shop register has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('shop_register', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            ShopRegister::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected shop registers have been deleted successfully',
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

        $shopRegister = DB::table('shop_register')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$shopRegister) {
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
            'shopRegisterName' => $shopRegister->shop_register_name ?? null,
            'companyId' => $shopRegister->company_id ?? null,
            'isRestaurant' => $shopRegister->is_restaurant ?? 'No',
            'shopRegisterStatus' => $shopRegister->shop_register_status ?? 'Yes',
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByCompany = $request->input('filter_by_company');
        $filterByIsRestaurant = $request->input('filter_by_is_restaurant');
        $filterByStatus = $request->input('filter_by_status');

        $products = DB::table('shop_register')
        ->when(!empty($filterByCompany), fn($q) => $q->whereIn('company_id', $filterByCompany))
        ->when(!empty($filterByIsRestaurant), function ($q) use ($filterByIsRestaurant) {
            $q->where('is_restaurant', $filterByIsRestaurant);
        })
        ->when(!empty($filterByStatus), function ($q) use ($filterByStatus) {
            $q->where('shop_register_status', $filterByStatus);
        })
        ->orderBy('shop_register_name')
        ->get();

        $response = $products->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $shopRegisterId = $row->id;
            $shopRegisterName = $row->shop_register_name;
            $companyName = $row->company_name;
            $isRestaurant = $row->is_restaurant ?? 'No';
            $shopRegisterStatus = $row->shop_register_status;
            $class = $shopRegisterStatus === 'Active' ? 'success' : 'danger';
            $activeBadge = "<span class=\"badge badge-light-{$class}\">{$shopRegisterStatus}</span>";

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $shopRegisterId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$shopRegisterId.'">
                    </div>
                ',
                'SHOP_REGISTER' => $shopRegisterName,
                'COMPANY' => $companyName,
                'IS_RESTAURANT' => $isRestaurant,
                'STATUS' => $activeBadge,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
