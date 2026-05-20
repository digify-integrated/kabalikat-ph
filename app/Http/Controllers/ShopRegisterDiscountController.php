<?php

namespace App\Http\Controllers;

use App\Models\DiscountType;
use App\Models\ShopRegister;
use App\Models\ShopRegisterDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShopRegisterDiscountController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_register_discount_id' => ['nullable', 'integer'],
            'shop_register_id' => ['required', 'integer', Rule::exists('shop_register', 'id')],
            'discount_type_id' => ['required', 'integer', Rule::exists('discount_type', 'id')],
            'discount_automatic_application' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $shopRegisterId = $validated['shop_register_id'] ?? null;
        $discountTypeId = $validated['discount_type_id'] ?? null;

        $shopRegisterName = (string) ShopRegister::query()
            ->whereKey($shopRegisterId)
            ->value('shop_register_name');

        $discountTypeName = (string) DiscountType::query()
            ->whereKey($discountTypeId)
            ->value('discount_type_name');

        $payload = [
            'shop_register_id' => $shopRegisterId,
            'shop_register_name' => $shopRegisterName,
            'discount_type_id' => $discountTypeId,
            'discount_type_name' => $discountTypeName,
            'automatic_application' => $validated['discount_automatic_application'],
            'last_log_by' => Auth::id(),
        ];

        $shopRegisterDiscountId = $validated['shop_register_discount_id'] ?? null;

        if ($shopRegisterDiscountId && ShopRegisterDiscount::query()->whereKey($shopRegisterDiscountId)->exists()) {
            $shopRegisterDiscount = ShopRegisterDiscount::query()->findOrFail($shopRegisterDiscountId);
            $shopRegisterDiscount->update($payload);
        } else {
            $shopRegisterDiscount = ShopRegisterDiscount::query()->create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'The discount has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('shop_register_discount', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $shopRegisterDiscount = ShopRegisterDiscount::query()->select(['id'])->findOrFail($referenceId);

            $shopRegisterDiscount->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The discount has been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('shop_register_discount', 'id')],
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

        $shopRegisterDiscount = DB::table('shop_register_discount')
            ->where('id', $validated['referenceId'])
            ->first();

        if (!$shopRegisterDiscount) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Discount not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'discountTypeId' => $shopRegisterDiscount->discount_type_id ?? null,
            'automaticApplication' => $shopRegisterDiscount->automatic_application ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $shopRegisterId = (int) $request->input('shop_register_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $shopRegisterDiscounts = DB::table('shop_register_discount')
        ->where('shop_register_id', $shopRegisterId)
        ->orderBy('discount_type_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $shopRegisterDiscounts->map(function ($row) use ($writeAccess, $logsAccess)  {
            $shopRegisterDiscountId = $row->id;
            $discountTypeId = $row->discount_type_id;
            $discountTypeName = $row->discount_type_name;
            $automaticApplication = $row->automatic_application;

            $discountType = DB::table('discount_type')
                ->where('id', $discountTypeId)
                ->first();
            $valueType = $discountType->value_type;
            $discountValue = ($valueType == 'Percentage') 
                ? number_format($discountType->discount_value, 2) . '%' 
                : number_format($discountType->discount_value, 2);
            $isVariable = $discountType->is_variable;

            $updateButton = '';
            $deleteButton = '';

            if($writeAccess > 0){
                $updateButton = '<button class="btn btn-icon btn-light btn-active-light-primary update-discount" data-bs-toggle="modal" data-bs-target="#discount-modal" data-reference-id="' . $shopRegisterDiscountId . '" title="Update Discount">
                                    <i class="ki-outline ki-pencil fs-3 m-0 fs-5"></i>
                                </button>';

                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-discount" data-reference-id="' . $shopRegisterDiscountId . '" title="Delete Discount">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-discount-log-notes" data-reference-id="' . $shopRegisterDiscountId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'DISCOUNT' => $discountTypeName,
                'IS_VARIABLE' => $isVariable,
                'DISCOUNT_VALUE' => $discountValue,
                'AUTOMATIC_APPLICATION' => $automaticApplication,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $updateButton .'
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
