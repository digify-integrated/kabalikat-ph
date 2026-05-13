<?php

namespace App\Http\Controllers;

use App\Models\DiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DiscountTypeController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'discount_type_id' => ['nullable', 'integer'],
            'discount_type_name' => ['required', 'string', 'max:255'],
            'value_type' => ['required', 'string', 'max:255'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'is_variable' => ['required', 'string', 'max:255'],
            'application_order' => ['required', 'string', 'max:255'],
            'is_vat_exempt' => ['required', 'string', 'max:255'],
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

        $payload = [
            'discount_type_name' => $validated['discount_type_name'],
            'value_type' => $validated['value_type'],
            'discount_value' => $validated['discount_value'],
            'is_variable' => $validated['is_variable'],
            'application_order' => $validated['application_order'],
            'is_vat_exempt' => $validated['is_vat_exempt'],
            'last_log_by' => Auth::id(),
        ];

        $discountTypeId = $validated['discount_type_id'] ?? null;

        if ($discountTypeId && DiscountType::query()->whereKey($discountTypeId)->exists()) {
            $discountType = DiscountType::query()->findOrFail($discountTypeId);
            $discountType->update($payload);
        } else {
            $discountType = DiscountType::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $discountType->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The discount type has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('discount_type', 'id')],
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
            $discountType = DiscountType::query()->select(['id'])->findOrFail($detailId);

            $discountType->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The discount type has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('discount_type', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            DiscountType::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected discount types have been deleted successfully',
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

        $discountType = DB::table('discount_type')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$discountType) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Discount type not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'discountTypeName' => $discountType->discount_type_name ?? null,
            'valueType' => $discountType->value_type ?? 'Percentage',
            'discountValue' => $discountType->discount_value ?? 0,
            'isVariable' => $discountType->is_variable ?? 'No',
            'applicationOrder' => $discountType->application_order ?? 'After Tax',
            'isVatExempt' => $discountType->is_vat_exempt ?? 'No',
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByValueType = $request->input('filter_by_value_type');
        $filterByIsVariable = $request->input('filter_by_is_variable');
        $filterByApplicationOrder = $request->input('filter_by_application_order');
        $filterByIsVatExempt = $request->input('filter_by_is_vat_exempt');

        $discountTypes = DB::table('discount_type')
        ->when(!empty($filterByValueType), function ($q) use ($filterByValueType) {
            $q->where('value_type', $filterByValueType);
        })
        ->when(!empty($filterByIsVariable), function ($q) use ($filterByIsVariable) {
            $q->where('is_variable', $filterByIsVariable);
        })
        ->when(!empty($filterByApplicationOrder), function ($q) use ($filterByApplicationOrder) {
            $q->where('application_order', $filterByApplicationOrder);
        })
        ->when(!empty($filterByIsVatExempt), function ($q) use ($filterByIsVatExempt) {
            $q->where('is_vat_exempt', $filterByIsVatExempt);
        })
        ->orderBy('discount_type_name')
        ->get();

        $response = $discountTypes->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $discountTypeId = $row->id;
            $discountTypeName = $row->discount_type_name;
            $valueType = $row->value_type;
            $discountValue = ($valueType == 'Percentage') 
                ? number_format($row->discount_value, 2) . '%' 
                : number_format($row->discount_value, 2);
            $isVariable = $row->is_variable;
            $applicationOrder = $row->application_order;
            $isVatExempt = $row->is_vat_exempt;
            

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $discountTypeId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$discountTypeId.'">
                    </div>
                ',
                'DISCOUNT_TYPE' => $discountTypeName,
                'DISCOUNT_VALUE' => $discountValue,
                'IS_VARIABLE' => $isVariable,
                'APPLICATION_ORDER' => $applicationOrder,
                'IS_VAT_EXEMPT' => $isVatExempt,
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

        $discountTypes = DB::table('discount_type')
            ->select(['id', 'discount_type_name'])
            ->orderBy('discount_type_name')
            ->get();

        $response = $response->concat(
            $discountTypes->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->discount_type_name,
            ])
        )->values();

        return response()->json($response);
    }
}
