<?php

namespace App\Http\Controllers;

use App\Models\RoleSystemActionPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\SystemAction;
use App\Models\Role;

class RoleSystemActionPermissionController extends Controller
{
    public function saveRoleAssignment(Request $request)
    {
        $validator = Validator::make(
        $request->all(),
        [
                'system_action_id' => ['required', 'integer', Rule::exists('system_action', 'id')],
                'role_id'            => ['required', 'array', 'min:1'],
                'role_id.*'          => ['required', 'integer', Rule::exists('role', 'id')],
            ],
            [
                'system_action_id.required' => 'Please select a system action',
                'system_action_id.exists'   => 'The selected system action does not exist',
                'role_id.required'          => 'Please select the role(s) you wish to assign to the system action',
                'role_id.array'             => 'The selected roles must be provided as a list',
                'role_id.min'               => 'Please select at least one role',
                'role_id.*.exists'          => 'One or more of the selected roles is invalid',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $systemActionId = (int) $validated['system_action_id'];
        $roleIds = collect($validated['role_id'])->map(fn ($id) => (int) $id)->unique()->values();

        $systemActionName = (string) SystemAction::query()
            ->whereKey($systemActionId)
            ->value('system_action_name');

        $lastLogBy = (int) auth()->id();

        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->get(['id', 'role_name'])
            ->keyBy('id');

        $now = now();
        $rows = $roleIds->map(function (int $roleId) use ($roles, $systemActionId, $systemActionName, $lastLogBy, $now) {
            return [
                'role_id'             => $roleId,
                'role_name'           => (string) ($roles[$roleId]->role_name ?? ''),
                'system_action_id'    => $systemActionId,
                'system_action_name'  => $systemActionName,
                'last_log_by'         => $lastLogBy,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        })->all();

        DB::transaction(function () use ($rows) {
            DB::table('role_system_action_permission')->upsert(
                $rows,
                ['role_id', 'system_action_id'],
                ['role_name', 'system_action_name', 'last_log_by', 'updated_at']
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'The role has been assigned successfully',
        ]);
    }

    public function saveSystemActionAssignment(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'role_id'               => ['required', 'integer', Rule::exists('role', 'id')],
                'system_action_id'      => ['required', 'array', 'min:1'],
                'system_action_id.*'    => ['required', 'integer', Rule::exists('system_action', 'id')],
            ],
            [
                'role_id.required'            => 'Please select a role',
                'role_id.exists'              => 'The selected role does not exist',

                'system_action_id.required'   => 'Please select the system action(s) you wish to assign to the role',
                'system_action_id.array'      => 'The selected system actions must be provided as a list',
                'system_action_id.min'        => 'Please select at least one system action',
                'system_action_id.*.exists'   => 'One or more of the selected system actions is invalid',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $roleId = (int) $validated['role_id'];
        $systemActionIds = collect($validated['system_action_id'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $roleName = (string) Role::query()
            ->whereKey($roleId)
            ->value('role_name');

        $lastLogBy = (int) auth()->id();

        $systemActions = SystemAction::query()
            ->whereIn('id', $systemActionIds)
            ->get(['id', 'system_action_name'])
            ->keyBy('id');

        $now = now();

        $rows = $systemActionIds->map(function (int $systemActionId) use ($systemActions, $roleId, $roleName, $lastLogBy, $now) {
            return [
                'role_id'             => $roleId,
                'role_name'           => $roleName,
                'system_action_id'    => $systemActionId,
                'system_action_name'  => (string) ($systemActions[$systemActionId]->system_action_name ?? ''),
                'last_log_by'         => $lastLogBy,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        })->all();

        DB::transaction(function () use ($rows) {
            DB::table('role_system_action_permission')->upsert(
                $rows,
                ['role_id', 'system_action_id'],
                ['role_name', 'system_action_name', 'last_log_by', 'updated_at']
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'The system action(s) have been assigned successfully',
        ]);
    }


    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'referenceId' => ['required', 'integer', 'min:1', 'exists:role_system_action_permission,id'],
                'access'      => ['required'],
            ],
            [
                'referenceId.required' => 'Reference ID is required.',
                'referenceId.integer'  => 'Reference ID must be an integer.',
                'referenceId.min'      => 'Reference ID must be at least 1.',
                'referenceId.exists'   => 'The selected permission record does not exist.',

                'access.required'      => 'Access value is required.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $data = $validator->validated();

        $referenceId = (int) $data['referenceId'];
        $access      = (bool) $data['access'];

        DB::transaction(function () use ($referenceId, $access) {
            $rolePermission = RoleSystemActionPermission::query()->lockForUpdate()->findOrFail($referenceId);

            $rolePermission->forceFill([
                'system_action_access' => $access,
                'last_log_by' => auth()->id() ?? 1,
            ])->save();
        });

        return response()->json([
            'success' => true
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', 'exists:role_system_action_permission,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $rolePermission = RoleSystemActionPermission::query()->select(['id'])->findOrFail($referenceId);

            $rolePermission->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The role permission has been deleted successfully',
        ]);
    }

    public function generateSystemActionRolePermissionTable(Request $request)
    {
        $systemActionId = (int) $request->input('system_action_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $rolePermissions = DB::table('role_system_action_permission')
        ->where('system_action_id', $systemActionId)
        ->orderBy('role_name')
        ->get();

        $canUpdateRoleAccess = app(SystemActionController::class)
            ->userHasRoleAccessForAction(5, Auth::id());

        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $rolePermissions->map(function ($row) use ($canUpdateRoleAccess, $logsAccess)  {
            $rolePermissionId = $row->id;
            $roleName = $row->role_name;
            $systemActionAccess = $row->system_action_access;

            $systemActionAccessChecked = $systemActionAccess ? 'checked' : '';
            $disabled = 'disabled';

            $deleteButton = '';
            if($canUpdateRoleAccess ?? false === true){
                $disabled = '';
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-role-permission" data-reference-id="' . $rolePermissionId . '" title="Delete Role Permission">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-role-permission-log-notes" data-reference-id="' . $rolePermissionId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            $systemActionAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" ' . $systemActionAccessChecked . ' '. $disabled .' />
                                </div>';

            return [
                'ROLE' => $roleName,
                'SYSTEM_ACTION_ACCESS' => $systemActionAccessButton,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }

    public function generateRoleSystemActionPermissionTable(Request $request)
    {
        $roleId = (int) $request->input('role_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $rolePermissions = DB::table('role_system_action_permission')
        ->where('role_id', $roleId)
        ->orderBy('system_action_name')
        ->get();

        $canUpdateRoleAccess = app(SystemActionController::class)
            ->userHasRoleAccessForAction(5, Auth::id());

        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $rolePermissions->map(function ($row) use ($canUpdateRoleAccess, $logsAccess)  {
            $rolePermissionId = $row->id;
            $systemActionName = $row->system_action_name;            
            $systemActionAccess = $row->system_action_access;

            $systemActionAccessChecked = $systemActionAccess ? 'checked' : '';
            $disabled = 'disabled';

            $deleteButton = '';
            if($canUpdateRoleAccess ?? false === true){
                $disabled = '';
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-role-system-action-permission" data-reference-id="' . $rolePermissionId . '" title="Delete Role Permission">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-role-system-action-permission-log-notes" data-reference-id="' . $rolePermissionId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            $systemActionAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-system-action-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="read_access" ' . $systemActionAccessChecked . ' '. $disabled .' />
                                </div>';

            return [
                'SYSTEM_ACTION' => $systemActionName,
                'SYSTEM_ACTION_ACCESS' => $systemActionAccessButton,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }

    public function generateSystemActionRoleDualListboxOptions(Request $request)
    {
        $systemActionId = (int) $request->input('systemActionId');

        $response = collect();

        $roles = DB::table('role')
        ->select(['id', 'role_name'])
        ->whereNotIn('id', function ($q) use ($systemActionId) {
            $q->select('role_id')
            ->from('role_system_action_permission')
            ->where('system_action_id', $systemActionId);
        })
        ->orderBy('role_name')
        ->get();

        $response = $response->concat(
            $roles->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->role_name,
            ])
        )->values();

        return response()->json($response);
    }

    public function generateRoleSystemActionDualListboxOptions(Request $request)
    {
        $roleId = (int) $request->input('roleId');

        $response = collect();

        $roles = DB::table('system_action')
        ->select(['id', 'system_action_name'])
        ->whereNotIn('id', function ($q) use ($roleId) {
            $q->select('system_action_id')
            ->from('role_system_action_permission')
            ->where('role_id', $roleId);
        })
        ->orderBy('system_action_name')
        ->get();

        $response = $response->concat(
            $roles->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->system_action_name,
            ])
        )->values();

        return response()->json($response);
    }
}
