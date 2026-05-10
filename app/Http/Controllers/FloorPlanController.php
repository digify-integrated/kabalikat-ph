<?php

namespace App\Http\Controllers;

use App\Models\FloorPlan;
use App\Models\FloorPlanTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FloorPlanController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'floor_plan_id' => ['nullable', 'integer'],
            'floor_plan_name' => ['required', 'string', 'max:255'],
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
            'floor_plan_name' => $validated['floor_plan_name'],
            'last_log_by' => Auth::id(),
        ];

        $floorPlanId = $validated['floor_plan_id'] ?? null;

        if ($floorPlanId && FloorPlan::query()->whereKey($floorPlanId)->exists()) {
            $floorPlan = FloorPlan::query()->findOrFail($floorPlanId);
            $floorPlan->update($payload);
        } else {
            $floorPlan = FloorPlan::query()->create($payload);
        }

        FloorPlanTable::query()
            ->where('floor_plan_id', $floorPlan->id)
            ->update([
                'floor_plan_name' => $floorPlan->floor_plan_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $floorPlan->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The floor plan has been saved successfully',
            'redirect_link' => $link,
        ]);
    }
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('floor_plan', 'id')],
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
            $floorPlan = FloorPlan::query()->select(['id'])->findOrFail($detailId);

            $floorPlan->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The floor plan has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('floor_plan', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            FloorPlan::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected floor plans have been deleted successfully',
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

        $floorPlan = DB::table('floor_plan')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$floorPlan) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Floor plan not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'floorPlanName' => $floorPlan->floor_plan_name ?? null
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $floorPlans = DB::table('floor_plan')
        ->orderBy('floor_plan_name')
        ->get();

        $response = $floorPlans->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $floorPlanId = $row->id;
            $floorPlanName = $row->floor_plan_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $floorPlanId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$floorPlanId.'">
                    </div>
                ',
                'FLOOR_PLAN' => $floorPlanName,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }

    public function generateShowRegisterFloorPlanOptions(Request $request)
    {
        $showRegisterId = $request->input('shop_register_id');
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $floorPlans = DB::table('floor_plan')
            ->select(['id', 'floor_plan_name'])
            ->whereNotIn('id', function ($query) use ($showRegisterId) {
                $query->select('floor_plan_id')
                    ->from('shop_register')
                    ->where('shop_register_id', $showRegisterId);
            })
            ->orderBy('floor_plan_name')
            ->get();

        $response = $response->concat(
            $floorPlans->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->floor_plan_name,
            ])
        )->values();

        return response()->json($response);
    }
}
