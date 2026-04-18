<?php

namespace App\Http\Controllers;

use App\Models\WarehouseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WarehouseTypeController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_type_id' => ['nullable', 'integer'],
            'warehouse_type_name' => ['required', 'string', 'max:255'],
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
            'warehouse_type_name' => $validated['warehouse_type_name'],
            'last_log_by' => Auth::id(),
        ];

        $warehouseTypeId = $validated['warehouse_type_id'] ?? null;

        if ($warehouseTypeId && WarehouseType::query()->whereKey($warehouseTypeId)->exists()) {
            $warehouseType = WarehouseType::query()->findOrFail($warehouseTypeId);
            $warehouseType->update($payload);
        } else {
            $warehouseType = WarehouseType::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $warehouseType->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The warehouse type has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('warehouse_type', 'id')],
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
            $warehouseType = WarehouseType::query()->select(['id'])->findOrFail($detailId);

            $warehouseType->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The warehouse type has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('warehouse_type', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            WarehouseType::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected warehouse types have been deleted successfully',
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

        $warehouseType = DB::table('warehouse_type')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$warehouseType) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'File type not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'warehouseTypeName' => $warehouseType->warehouse_type_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $warehouseTypes = DB::table('warehouse_type')
        ->orderBy('warehouse_type_name')
        ->get();

        $response = $warehouseTypes->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $warehouseTypeId = $row->id;
            $warehouseTypeName = $row->warehouse_type_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $warehouseTypeId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$warehouseTypeId.'">
                    </div>
                ',
                'WAREHOUSE_TYPE' => $warehouseTypeName,
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

        $warehouseTypes = DB::table('warehouse_type')
            ->select(['id', 'warehouse_type_name'])
            ->orderBy('warehouse_type_name')
            ->get();

        $response = $response->concat(
            $warehouseTypes->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->warehouse_type_name,
            ])
        )->values();

        return response()->json($response);
    }
}
