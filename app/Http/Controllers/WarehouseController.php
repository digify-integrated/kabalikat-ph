<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => ['nullable', 'integer'],
            'warehouse_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string'],
            'warehouse_status' => ['required', 'string'],
            'warehouse_type_id' => ['required', 'integer', Rule::exists('warehouse_type', 'id')],
            'address' => ['required', 'string'],
            'city_id' => ['required', 'integer', Rule::exists('city', 'id')],
            'phone' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string'],
            'email' => ['nullable', 'string', 'email']
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

        $cityId = (int) $validated['city_id'] ?? null;
        $warehouseTypeId = (int) $validated['warehouse_type_id'] ?? null;

        $warehouseTypeName = (string) WarehouseType::query()
            ->whereKey($warehouseTypeId)
            ->value('warehouse_type');

        $cityDetails = City::query()->find($cityId);
        $cityName = $cityDetails?->city_name;
        $stateId = $cityDetails?->state_id;
        $stateName = $cityDetails?->state_name;
        $countryId = $cityDetails?->country_id;
        $countryName = $cityDetails?->country_name;

        $payload = [
            'warehouse_name' => $validated['warehouse_name'],
            'contact_person' => $validated['contact_person'],
            'warehouse_status' => $validated['warehouse_status'],
            'warehouse_type_id' => $warehouseTypeId,
            'warehouse_type_name' => $warehouseTypeName,
            'address' => $validated['address'] ?? null,
            'city_id' => $cityId,
            'city_name' => $cityName,
            'state_id' => $stateId,
            'state_name' => $stateName,
            'country_id' => $countryId,
            'country_name' => $countryName,
            'phone' => $validated['phone'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'email' => $validated['email'] ?? null,
            'last_log_by' => Auth::id(),
        ];

        $warehouseId = $validated['warehouse_id'] ?? null;

        if ($warehouseId && Warehouse::query()->whereKey($warehouseId)->exists()) {
            $warehouse = Warehouse::query()->findOrFail($warehouseId);
            $warehouse->update($payload);
        } else {
            $warehouse = Warehouse::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $warehouse->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The warehouse has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('warehouse', 'id')],
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
            $warehouse = Warehouse::query()->select(['id'])->findOrFail($detailId);

            $warehouse->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The warehouse has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('warehouse', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Warehouse::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected warehouses have been deleted successfully',
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

        $warehouse = DB::table('warehouse')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$warehouse) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Warehouse not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'notExist' => false,
            'warehouseName' => $warehouse->warehouse_name ?? null,
            'contactPerson' => $warehouse->contact_person ?? null,
            'warehouseStatus' => $warehouse->warehouse_status ?? 'Active',
            'address' => $warehouse->address ?? null,
            'cityId' => $warehouse->city_id ?? null,
            'stateId' => $warehouse->state_id ?? null,
            'countryId' => $warehouse->country_id ?? null,
            'phone' => $warehouse->phone ?? null,
            'telephone' => $warehouse->telephone ?? null,
            'email' => $warehouse->email ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $companies = DB::table('warehouse')
        ->orderBy('warehouse_name')
        ->get();

        $response = $companies->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $warehouseId = $row->id;
            $warehouseName = $row->warehouse_name;
            $address = $row->address . ', ' . $row->city_name . ', ' . $row->state_name . ', ' . $row->country_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $warehouseId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$warehouseId.'">
                    </div>
                ',
                'WAREHOUSE' => '
                    <div class="d-flex align-items-center">
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$warehouseName.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$address.'</small>
                            </div>
                        </div>
                    </div>
                ',
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

        $warehouses = DB::table('warehouse')
            ->select(['id', 'warehouse_name'])
            ->orderBy('warehouse_name')
            ->get();

        $response = $response->concat(
            $warehouses->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->warehouse_name,
            ])
        )->values();

        return response()->json($response);
    }
}
