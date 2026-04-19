<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UnitConversionController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_conversion_id' => ['nullable', 'integer'],
            'from_unit_id' => ['required', 'integer', Rule::exists('unit', 'id')],
            'to_unit_id' => ['required', 'integer', Rule::exists('unit', 'id')],
            'conversion_factor' => ['required', 'numeric', 'gt:0'],
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

        $fromUnitId = (int) $validated['from_unit_id'];
        $toUnitId = (int) $validated['to_unit_id'];

        $fromUnitName = (string) Unit::query()
            ->whereKey($fromUnitId)
            ->value('unit_name');

        $toUnitName = (string) Unit::query()
            ->whereKey($toUnitId)
            ->value('unit_name');

        $payload = [
            'from_unit_id' => $fromUnitId,
            'from_unit_name' => $fromUnitName,
            'to_unit_id' => $toUnitId,
            'to_unit_name' => $toUnitName,
            'conversion_factor' => $validated['conversion_factor'],
            'last_log_by' => Auth::id(),
        ];

        $unitConversionId = $validated['unit_conversion_id'] ?? null;

        if ($unitConversionId && UnitConversion::query()->whereKey($unitConversionId)->exists()) {
            $unitConversion = UnitConversion::query()->findOrFail($unitConversionId);
            $unitConversion->update($payload);
        } else {
            $unitConversion = UnitConversion::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $unitConversion->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The unit conversion has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('unit_conversion', 'id')],
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
            $unit = UnitConversion::query()->select(['id'])->findOrFail($detailId);

            $unit->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The unit conversion has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('unit_conversion', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            UnitConversion::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected unit conversions have been deleted successfully',
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

        $unit = DB::table('unit_conversion')
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
            'fromUnitId' => $unit->from_unit_id ?? null,
            'toUnitId' => $unit->to_unit_id ?? null,
            'conversionFactor' => $unit->conversion_factor ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $units = DB::table('unit_conversion')
        ->orderBy('from_unit_name')
        ->get();

        $response = $units->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $unitConversionId = $row->id;
            $fromUnitId = $row->from_unit_id;
            $fromUnitName = $row->from_unit_name;
            $toUnitName = $row->to_unit_name;
            $toUnitId = $row->to_unit_id;
            $conversionFactor = $row->conversion_factor;

            $fromAbbreviation = (string) Unit::query()
                ->whereKey($fromUnitId)
                ->value('abbreviation');

            $toAbbreviation = (string) Unit::query()
                ->whereKey($toUnitId)
                ->value('abbreviation');

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $unitConversionId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$unitConversionId.'">
                    </div>
                ',
                'FROM' => '1 '. $fromUnitName . ' ('. $fromAbbreviation .')',
                'TO' => number_format($conversionFactor, 2) . ' '. $toUnitName . ' ('. $toAbbreviation .')',
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
