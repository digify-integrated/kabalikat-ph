<?php

namespace App\Http\Controllers;

use App\Models\FloorPlan;
use App\Models\FloorPlanTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class FloorPlanTableController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'floor_plan_table_id' => ['nullable', 'integer'],
            'floor_plan_id' => ['required', 'integer', Rule::exists('floor_plan', 'id')],
            'table_number' => ['required', 'int', 'min:1'],
            'seats' => ['required', 'int', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $floorPlanId = $validated['floor_plan_id'] ?? null;

        $floorPlanName = (string) FloorPlan::query()
            ->whereKey($floorPlanId)
            ->value('floor_plan_name');

        $payload = [
            'floor_plan_id' => $floorPlanId,
            'floor_plan_name' => $floorPlanName,
            'table_number' => $validated['table_number'],
            'seats' => $validated['seats'],
            'last_log_by' => Auth::id(),
        ];

        $floorPlanTableId = $validated['floor_plan_table_id'] ?? null;

        if ($floorPlanTableId && FloorPlanTable::query()->whereKey($floorPlanTableId)->exists()) {
            $floorPlanTable = FloorPlanTable::query()->findOrFail($floorPlanTableId);
            $floorPlanTable->update($payload);
        } else {
            $floorPlanTable = FloorPlanTable::query()->create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'The floor plan table has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('floor_plan_table', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $floorPlanTable = FloorPlanTable::query()->select(['id'])->findOrFail($referenceId);

            $floorPlanTable->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The floor plan table has been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('floor_plan_table', 'id')],
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

        $floorPlanTable = DB::table('floor_plan_table')
            ->where('id', $validated['referenceId'])
            ->first();

        if (!$floorPlanTable) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Floor plan table not found',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'notExist' => false,
            'tableNumber' => $floorPlanTable->table_number ?? null,
            'seats' => $floorPlanTable->seats ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $floorPlanId = (int) $request->input('floor_plan_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $floorPlanTables = DB::table('floor_plan_table')
        ->where('floor_plan_id', $floorPlanId)
        ->orderBy('table_number')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $floorPlanTables->map(function ($row) use ($writeAccess, $logsAccess)  {
            $floorPlanTableId = $row->id;
            $tableNumber = $row->table_number;
            $seats = $row->seats;

            $updateButton = '';
            $deleteButton = '';

            if($writeAccess > 0){
                $updateButton = '<button class="btn btn-icon btn-light btn-active-light-primary update-floor-plan-table" data-bs-toggle="modal" data-bs-target="#floor-plan-tables-modal" data-reference-id="' . $floorPlanTableId . '" title="Update Table">
                                    <i class="ki-outline ki-pencil fs-3 m-0 fs-5"></i>
                                </button>';

                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-floor-plan-table" data-reference-id="' . $floorPlanTableId . '" title="Delete Floor Plan Table">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-floor-plan-table-log-notes" data-reference-id="' . $floorPlanTableId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'TABLE_NUMBER' => $tableNumber,
                'SEATS' => $seats,
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
