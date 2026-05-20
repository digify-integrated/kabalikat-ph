<?php

namespace App\Http\Controllers;

use App\Models\ChargeType;
use App\Models\ShopRegister;
use App\Models\ShopRegisterCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShopRegisterChargeController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_register_charge_id' => ['nullable', 'integer'],
            'shop_register_id' => ['required', 'integer', Rule::exists('shop_register', 'id')],
            'charge_type_id' => ['required', 'integer', Rule::exists('charge_type', 'id')],
            'charge_automatic_application' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $shopRegisterId = $validated['shop_register_id'] ?? null;
        $chargeTypeId = $validated['charge_type_id'] ?? null;

        $shopRegisterName = (string) ShopRegister::query()
            ->whereKey($shopRegisterId)
            ->value('shop_register_name');

        $chargeTypeName = (string) ChargeType::query()
            ->whereKey($chargeTypeId)
            ->value('charge_type_name');

        $payload = [
            'shop_register_id' => $shopRegisterId,
            'shop_register_name' => $shopRegisterName,
            'charge_type_id' => $chargeTypeId,
            'charge_type_name' => $chargeTypeName,
            'automatic_application' => $validated['charge_automatic_application'],
            'last_log_by' => Auth::id(),
        ];

        $shopRegisterChargeId = $validated['shop_register_charge_id'] ?? null;

        if ($shopRegisterChargeId && ShopRegisterCharge::query()->whereKey($shopRegisterChargeId)->exists()) {
            $shopRegisterCharge = ShopRegisterCharge::query()->findOrFail($shopRegisterChargeId);
            $shopRegisterCharge->update($payload);
        } else {
            $shopRegisterCharge = ShopRegisterCharge::query()->create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'The charge has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('shop_register_charge', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $shopRegisterCharge = ShopRegisterCharge::query()->select(['id'])->findOrFail($referenceId);

            $shopRegisterCharge->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The charge has been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('shop_register_charge', 'id')],
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

        $shopRegisterCharge = DB::table('shop_register_charge')
            ->where('id', $validated['referenceId'])
            ->first();

        if (!$shopRegisterCharge) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Charge not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'chargeTypeId' => $shopRegisterCharge->charge_type_id ?? null,
            'automaticApplication' => $shopRegisterCharge->automatic_application ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $shopRegisterId = (int) $request->input('shop_register_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $shopRegisterCharges = DB::table('shop_register_charge')
        ->where('shop_register_id', $shopRegisterId)
        ->orderBy('charge_type_name')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $shopRegisterCharges->map(function ($row) use ($writeAccess, $logsAccess)  {
            $shopRegisterChargeId = $row->id;
            $chargeTypeId = $row->charge_type_id;
            $chargeTypeName = $row->charge_type_name;
            $automaticApplication = $row->automatic_application;

            $chargeType = DB::table('charge_type')
                ->where('id', $chargeTypeId)
                ->first();
            $valueType = $chargeType->value_type;
            $chargeValue = ($valueType == 'Percentage') 
                ? number_format($chargeType->charge_value, 2) . '%' 
                : number_format($chargeType->charge_value, 2);
            $isVariable = $chargeType->is_variable;

            $updateButton = '';
            $deleteButton = '';

            if($writeAccess > 0){
                $updateButton = '<button class="btn btn-icon btn-light btn-active-light-primary update-charge" data-bs-toggle="modal" data-bs-target="#charge-modal" data-reference-id="' . $shopRegisterChargeId . '" title="Update Charge">
                                    <i class="ki-outline ki-pencil fs-3 m-0 fs-5"></i>
                                </button>';

                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-charge" data-reference-id="' . $shopRegisterChargeId . '" title="Delete Charge">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-charge-log-notes" data-reference-id="' . $shopRegisterChargeId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'CHARGE' => $chargeTypeName,
                'IS_VARIABLE' => $isVariable,
                'CHARGE_VALUE' => $chargeValue,
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
