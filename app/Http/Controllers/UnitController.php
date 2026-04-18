<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_id' => ['nullable', 'integer'],
            'unit_name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'unit_type_id' => ['required', 'integer', Rule::exists('unit_type', 'id')],
            'is_base_unit' => ['required', 'string', 'max:5'],
            'conversion_factor' => ['nullable', 'numeric', 'min:0'],
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

        $unitTypeId = (int) $validated['unit_type_id'];

        $unitTypeName = (string) UnitType::query()
            ->whereKey($unitTypeId)
            ->value('unit_type_name');

        $payload = [
            'unit_name' => $validated['unit_name'],
            'abbreviation' => $validated['abbreviation'],
            'unit_type_id' => $unitTypeId,
            'unit_type_name' => $unitTypeName,
            'is_base_unit' => $validated['is_base_unit'],
            'conversion_factor' => $validated['conversion_factor'],
            'last_log_by' => Auth::id(),
        ];

        $unitId = $validated['unit_id'] ?? null;

        if ($unitId && Unit::query()->whereKey($unitId)->exists()) {
            $unit = Unit::query()->findOrFail($unitId);
            $unit->update($payload);
        } else {
            $unit = Unit::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $unit->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The unit has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('unit', 'id')],
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
            $unit = Unit::query()->select(['id'])->findOrFail($detailId);

            $unit->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The unit has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('unit', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Unit::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected units have been deleted successfully',
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

        $unit = DB::table('unit')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$unit) {
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
            'unitName' => $unit->unit_name ?? null,
            'abbreviation' => $unit->abbreviation ?? null,
            'unitTypeId' => $unit->unit_type_id ?? null,
            'isBaseUnit' => $unit->is_base_unit ?? 'No',
            'conversionFactor' => $unit->conversion_factor ?? 0,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $units = DB::table('unit')
        ->orderBy('unit_name')
        ->get();

        $response = $units->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $unitId = $row->id;
            $unitName = $row->unit_name;
            $abbreviation = $row->abbreviation;
            $unitTypeName = $row->unit_type_name;
            $isBaseUnit = $row->is_base_unit;
            $conversionFactor = $row->conversion_factor;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $unitId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$unitId.'">
                    </div>
                ',
                'UNIT' => $unitName,
                'ABBREVIATION' => $abbreviation,
                'UNIT_TYPE' => $unitTypeName,
                'IS_BASE_UNIT' => $isBaseUnit,
                'CONVERSION_FACTOR' => $conversionFactor,
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

        $units = DB::table('unit')
            ->select(['id', 'unit_name', 'abbreviation'])
            ->orderBy('unit_name')
            ->get();

        $response = $response->concat(
            $units->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->unit_name . ' (' . $row->abbreviation . ')',
            ])
        )->values();

        return response()->json($response);
    }
}
