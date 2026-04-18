<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => ['nullable', 'integer'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string'],
            'supplier_status' => ['required', 'string'],
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

        $cityDetails = City::query()->find($cityId);
        $cityName = $cityDetails?->city_name;
        $stateId = $cityDetails?->state_id;
        $stateName = $cityDetails?->state_name;
        $countryId = $cityDetails?->country_id;
        $countryName = $cityDetails?->country_name;

        $payload = [
            'supplier_name' => $validated['supplier_name'],
            'contact_person' => $validated['contact_person'],
            'supplier_status' => $validated['supplier_status'],
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

        $supplierId = $validated['supplier_id'] ?? null;

        if ($supplierId && Supplier::query()->whereKey($supplierId)->exists()) {
            $supplier = Supplier::query()->findOrFail($supplierId);
            $supplier->update($payload);
        } else {
            $supplier = Supplier::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $supplier->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The supplier has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('supplier', 'id')],
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
            $supplier = Supplier::query()->select(['id'])->findOrFail($detailId);

            $supplier->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The supplier has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('supplier', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Supplier::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected suppliers have been deleted successfully',
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

        $supplier = DB::table('supplier')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$supplier) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Supplier not found',
            ]);
        }

        return response()->json([
            'success' => true,
            'notExist' => false,
            'supplierName' => $supplier->supplier_name ?? null,
            'contactPerson' => $supplier->contact_person ?? null,
            'supplierStatus' => $supplier->supplier_status ?? 'Active',
            'address' => $supplier->address ?? null,
            'cityId' => $supplier->city_id ?? null,
            'stateId' => $supplier->state_id ?? null,
            'countryId' => $supplier->country_id ?? null,
            'phone' => $supplier->phone ?? null,
            'telephone' => $supplier->telephone ?? null,
            'email' => $supplier->email ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $companies = DB::table('supplier')
        ->orderBy('supplier_name')
        ->get();

        $response = $companies->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $supplierId = $row->id;
            $supplierName = $row->supplier_name;
            $address = $row->address . ', ' . $row->city_name . ', ' . $row->state_name . ', ' . $row->country_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $supplierId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$supplierId.'">
                    </div>
                ',
                'SUPPLIER' => '
                    <div class="d-flex align-items-center">
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$supplierName.'</h6>
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

        $suppliers = DB::table('supplier')
            ->select(['id', 'supplier_name'])
            ->orderBy('supplier_name')
            ->get();

        $response = $response->concat(
            $suppliers->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->supplier_name,
            ])
        )->values();

        return response()->json($response);
    }
}
