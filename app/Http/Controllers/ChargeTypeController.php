<?php

namespace App\Http\Controllers;

use App\Models\ChargeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChargeTypeController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charge_type_id' => ['nullable', 'integer'],
            'charge_type_name' => ['required', 'string', 'max:255'],
            'value_type' => ['required', 'string', 'max:255'],
            'charge_value' => ['required', 'numeric', 'min:0'],
            'is_variable' => ['required', 'string', 'max:255'],
            'application_order' => ['required', 'string', 'max:255'],
            'tax_type' => ['required', 'string', 'max:255'],
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
            'charge_type_name' => $validated['charge_type_name'],
            'value_type' => $validated['value_type'],
            'charge_value' => $validated['charge_value'],
            'is_variable' => $validated['is_variable'],
            'application_order' => $validated['application_order'],
            'tax_type' => $validated['tax_type'],
            'last_log_by' => Auth::id(),
        ];

        $chargeTypeId = $validated['charge_type_id'] ?? null;

        if ($chargeTypeId && ChargeType::query()->whereKey($chargeTypeId)->exists()) {
            $chargeType = ChargeType::query()->findOrFail($chargeTypeId);
            $chargeType->update($payload);
        } else {
            $chargeType = ChargeType::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $chargeType->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The charge type has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('charge_type', 'id')],
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
            $chargeType = ChargeType::query()->select(['id'])->findOrFail($detailId);

            $chargeType->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The charge type has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('charge_type', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            ChargeType::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected charge types have been deleted successfully',
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

        $chargeType = DB::table('charge_type')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$chargeType) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Charge type not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'chargeTypeName' => $chargeType->charge_type_name ?? null,
            'valueType' => $chargeType->value_type ?? 'Percentage',
            'chargeValue' => $chargeType->charge_value ?? 0,
            'isVariable' => $chargeType->is_variable ?? 'No',
            'applicationOrder' => $chargeType->application_order ?? 'After Tax',
            'taxType' => $chargeType->tax_type ?? 'Non Vatable',
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByValueType = $request->input('filter_by_value_type');
        $filterByIsVariable = $request->input('filter_by_is_variable');
        $filterByApplicationOrder = $request->input('filter_by_application_order');
        $filterByTaxType = $request->input('filter_by_tax_type');

        $chargeTypes = DB::table('charge_type')
        ->when(!empty($filterByValueType), function ($q) use ($filterByValueType) {
            $q->where('value_type', $filterByValueType);
        })
        ->when(!empty($filterByIsVariable), function ($q) use ($filterByIsVariable) {
            $q->where('is_variable', $filterByIsVariable);
        })
        ->when(!empty($filterByApplicationOrder), function ($q) use ($filterByApplicationOrder) {
            $q->where('application_order', $filterByApplicationOrder);
        })
        ->when(!empty($filterByTaxType), function ($q) use ($filterByTaxType) {
            $q->where('taxt_type', $filterByTaxType);
        })
        ->orderBy('charge_type_name')
        ->get();

        $response = $chargeTypes->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $chargeTypeId = $row->id;
            $chargeTypeName = $row->charge_type_name;
            $valueType = $row->value_type;
            $chargeValue = ($valueType == 'Percentage') 
                ? number_format($row->charge_value, 2) . '%' 
                : number_format($row->charge_value, 2);
            $isVariable = $row->is_variable;
            $applicationOrder = $row->application_order;
            $taxType = $row->tax_type;
            

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $chargeTypeId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$chargeTypeId.'">
                    </div>
                ',
                'CHARGE_TYPE' => $chargeTypeName,
                'CHARGE_VALUE' => $chargeValue,
                'IS_VARIABLE' => $isVariable,
                'APPLICATION_ORDER' => $applicationOrder,
                'TAX_TYPE' => $taxType,
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

        $chargeTypes = DB::table('charge_type')
            ->select(['id', 'charge_type_name', 'charge_type'])
            ->orderBy('charge_type_name')
            ->get();

        $response = $response->concat(
            $chargeTypes->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->charge_type_name . ' (.' . $row->charge_type . ')',
            ])
        )->values();

        return response()->json($response);
    }
}
