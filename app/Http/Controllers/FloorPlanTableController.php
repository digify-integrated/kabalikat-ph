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
            'floor_plan_id' => ['required', 'integer', Rule::exists('floor_plan', 'id')],
            'quantity'      => ['required', 'integer', 'min:1', 'max:50'], // Capped at 50 per batch for performance
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();
        $floorPlanId = $validated['floor_plan_id'];

        DB::beginTransaction();
        try {
            // Fetch the floor plan name once to optimize payload assignments
            $floorPlanName = (string) FloorPlan::query()
                ->whereKey($floorPlanId)
                ->value('floor_plan_name');

            /*
            |--------------------------------------------------------------------------
            | SEQUENCING RESOLVER
            |--------------------------------------------------------------------------
            | We grab the maximum string table number for this floor plan, cast it to 
            | an unsigned integer so MySQL can sort it numerically instead of alphabetically,
            | and fallback to 0 if this is the very first table.
            |--------------------------------------------------------------------------
            */
            $maxTableNumber = (int) FloorPlanTable::query()
                ->where('floor_plan_id', $floorPlanId)
                ->selectRaw('MAX(CAST(table_number AS UNSIGNED)) as max_num')
                ->value('max_num') ?? 0;

            $quantityToCreate = (int) $validated['quantity'];
            $createdTablesCount = 0;

            // Loop to generate sequential records
            for ($i = 1; $i <= $quantityToCreate; $i++) {
                $nextTableNumber = $maxTableNumber + $i;

                FloorPlanTable::query()->create([
                    'floor_plan_id'   => $floorPlanId,
                    'floor_plan_name' => $floorPlanName,
                    'table_number'    => (string) $nextTableNumber, // Cast back to string for database consistency
                    'seats'           => 1,
                    'last_log_by'     => Auth::id(),
                ]);

                $createdTablesCount++;
            }

            DB::commit();

            $message = $createdTablesCount === 1 
                ? '1 new table has been added successfully.' 
                : "{$createdTablesCount} tables have been sequenced and added successfully.";

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the table sequence.',
            ]);
        }
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

            $deleteButton = '';

            if($writeAccess > 0){
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
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
