<?php

namespace App\Http\Controllers;

use App\Models\NavigationMenu;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    public function saveNavigationMenuRoleAssignment(Request $request)
    {
        $validator = Validator::make(
        $request->all(),
        [
                'navigation_menu_id' => ['required', 'integer', Rule::exists('navigation_menu', 'id')],
                'role_id'            => ['required', 'array', 'min:1'],
                'role_id.*'          => ['required', 'integer', Rule::exists('role', 'id')],
            ],
            [
                'navigation_menu_id.required' => 'Please select a navigation menu',
                'navigation_menu_id.exists'   => 'The selected navigation menu does not exist',
                'role_id.required'            => 'Please select the role(s) you wish to assign to the navigation menu',
                'role_id.array'               => 'The selected roles must be provided as a list',
                'role_id.min'                 => 'Please select at least one role',
                'role_id.*.exists'            => 'One or more of the selected roles is invalid',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $navigationMenuId = (int) $validated['navigation_menu_id'];
        $roleIds = collect($validated['role_id'])->map(fn ($id) => (int) $id)->unique()->values();

        $navigationMenuName = (string) NavigationMenu::query()
            ->whereKey($navigationMenuId)
            ->value('navigation_menu_name');

        if ($navigationMenuName === '') {
            return response()->json([
                'success' => false,
                'message' => 'The selected navigation menu does not exist',
            ]);
        }

        $lastLogBy = (int) auth()->id();

        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->get(['id', 'role_name'])
            ->keyBy('id');

        $now = now();
        $rows = $roleIds->map(function (int $roleId) use ($roles, $navigationMenuId, $navigationMenuName, $lastLogBy, $now) {
            return [
                'role_id'               => $roleId,
                'role_name'             => (string) ($roles[$roleId]->role_name ?? ''),
                'navigation_menu_id'    => $navigationMenuId,
                'navigation_menu_name'  => $navigationMenuName,
                'last_log_by'           => $lastLogBy,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];
        })->all();

        DB::transaction(function () use ($rows) {
            DB::table('role_permission')->upsert(
                $rows,
                ['role_id', 'navigation_menu_id'],
                ['role_name', 'navigation_menu_name', 'last_log_by', 'updated_at']
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'The navigation menu role has been assigned successfully',
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'referenceId' => ['required', 'integer', 'min:1', 'exists:role_permission,id'],
                'accessType'  => ['required', 'string', 'in:read_access,write_access,create_access,delete_access,import_access,export_access,logs_access'],
                'access'      => ['required'],
            ],
            [
                'referenceId.required' => 'Reference ID is required.',
                'referenceId.integer'  => 'Reference ID must be an integer.',
                'referenceId.min'      => 'Reference ID must be at least 1.',
                'referenceId.exists'   => 'The selected permission record does not exist.',

                'accessType.required'  => 'Access type is required.',
                'accessType.string'    => 'Access type must be a string.',
                'accessType.in'        => 'Access type is invalid.',

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
        $accessType  = (string) $data['accessType'];
        $access      = (bool) $data['access'];

        DB::transaction(function () use ($referenceId, $accessType, $access) {
            $rolePermission = RolePermission::query()->lockForUpdate()->findOrFail($referenceId);

            $rolePermission->forceFill([
                $accessType   => $access,
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
            'referenceId' => ['required', 'integer', 'min:1', 'exists:role_permission,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $rolePermission = RolePermission::query()->select(['id'])->findOrFail($referenceId);

            $rolePermission->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The role permission has been deleted successfully',
        ]);
    }

    public function generateNavigationMenuRolePermissionTable(Request $request)
    {
        $navigationMenuId = (int) $request->input('navigation_menu_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $apps = DB::table('role_permission')
        ->where('navigation_menu_id', $navigationMenuId)
        ->orderBy('role_name')
        ->get();

        $canUpdateRoleAccess = app(SystemActionController::class)
            ->userHasRoleAccessForAction(4, Auth::id());

        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $apps->map(function ($row) use ($canUpdateRoleAccess, $logsAccess)  {
            $rolePermissionId = $row->id;
            $roleName = $row->role_name;
            $readAccess = $row->read_access;
            $writeAccess = $row->write_access;
            $createAccess = $row->create_access;
            $deleteAccess = $row->delete_access;
            $importAccess = $row->import_access;
            $exportAccess = $row->export_access;
            $logNotesAccess = $row->logs_access;

            $readAccessChecked = $readAccess ? 'checked' : '';
            $writeAccessChecked = $writeAccess ? 'checked' : '';
            $createAccessChecked = $createAccess ? 'checked' : '';
            $deleteAccessChecked = $deleteAccess ? 'checked' : '';
            $importAccessChecked = $importAccess ? 'checked' : '';
            $exportAccessChecked = $exportAccess ? 'checked' : '';
            $logNotesAccessChecked = $logNotesAccess ? 'checked' : '';
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

            $readAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="read_access" ' . $readAccessChecked . ' '. $disabled .' />
                                </div>';

            $writeAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="write_access" ' . $writeAccessChecked . ' '. $disabled .' />
                                </div>';

            $createAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="create_access" ' . $createAccessChecked . ' '. $disabled .' />
                                </div>';

            $deleteAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="delete_access" ' . $deleteAccessChecked . ' '. $disabled .' />
                                </div>';

            $importAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="import_access" ' . $importAccessChecked . ' '. $disabled .' />
                                </div>';

            $exportAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="export_access" ' . $exportAccessChecked . ' '. $disabled .' />
                                </div>';

            $logNotesAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="logs_access" ' . $logNotesAccessChecked . ' '. $disabled .' />
                                </div>';

            return [
                'ROLE' => $roleName,
                'READ_ACCESS' => $readAccessButton,
                'WRITE_ACCESS' => $writeAccessButton,
                'CREATE_ACCESS' => $createAccessButton,
                'DELETE_ACCESS' => $deleteAccessButton,
                'IMPORT_ACCESS' => $importAccessButton,
                'EXPORT_ACCESS' => $exportAccessButton,
                'LOGS_ACCESS' => $logNotesAccessButton,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }

    public function generateRoleNavigationMenuPermissionTable(Request $request)
    {
        $roleId = (int) $request->input('role_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $apps = DB::table('role_permission')
        ->where('role_id', $roleId)
        ->orderBy('navigation_menu_name')
        ->get();

        $canUpdateRoleAccess = app(SystemActionController::class)
            ->userHasRoleAccessForAction(4, Auth::id());

        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $apps->map(function ($row) use ($canUpdateRoleAccess, $logsAccess)  {
            $rolePermissionId = $row->id;
            $navigationMenuName = $row->navigation_menu_name;
            $readAccess = $row->read_access;
            $writeAccess = $row->write_access;
            $createAccess = $row->create_access;
            $deleteAccess = $row->delete_access;
            $importAccess = $row->import_access;
            $exportAccess = $row->export_access;
            $logNotesAccess = $row->logs_access;

            $readAccessChecked = $readAccess ? 'checked' : '';
            $writeAccessChecked = $writeAccess ? 'checked' : '';
            $createAccessChecked = $createAccess ? 'checked' : '';
            $deleteAccessChecked = $deleteAccess ? 'checked' : '';
            $importAccessChecked = $importAccess ? 'checked' : '';
            $exportAccessChecked = $exportAccess ? 'checked' : '';
            $logNotesAccessChecked = $logNotesAccess ? 'checked' : '';
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

            $readAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="read_access" ' . $readAccessChecked . ' '. $disabled .' />
                                </div>';

            $writeAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="write_access" ' . $writeAccessChecked . ' '. $disabled .' />
                                </div>';

            $createAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="create_access" ' . $createAccessChecked . ' '. $disabled .' />
                                </div>';

            $deleteAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="delete_access" ' . $deleteAccessChecked . ' '. $disabled .' />
                                </div>';

            $importAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="import_access" ' . $importAccessChecked . ' '. $disabled .' />
                                </div>';

            $exportAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="export_access" ' . $exportAccessChecked . ' '. $disabled .' />
                                </div>';

            $logNotesAccessButton = '<div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input update-role-permission" type="checkbox" data-reference-id="' . $rolePermissionId . '" data-access-type="logs_access" ' . $logNotesAccessChecked . ' '. $disabled .' />
                                </div>';

            return [
                'NAVIGATION_MENU' => $navigationMenuName,
                'READ_ACCESS' => $readAccessButton,
                'WRITE_ACCESS' => $writeAccessButton,
                'CREATE_ACCESS' => $createAccessButton,
                'DELETE_ACCESS' => $deleteAccessButton,
                'IMPORT_ACCESS' => $importAccessButton,
                'EXPORT_ACCESS' => $exportAccessButton,
                'LOG_NOTES_ACCESS' => $logNotesAccessButton,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }

    public function generateNavigationMenuRoleDualListboxOptions(Request $request)
    {
        $navigationMenuId = (int) $request->input('navigationMenuId');

        $response = collect();

        $roles = DB::table('role')
        ->select(['id', 'role_name'])
        ->whereNotIn('id', function ($q) use ($navigationMenuId) {
            $q->select('role_id')
            ->from('role_permission')
            ->where('navigation_menu_id', $navigationMenuId);
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

    public function generateRoleNavigationMenuDualListboxOptions(Request $request)
    {
        $roleId = (int) $request->input('roleId');

        $response = collect();

        $roles = DB::table('navigation_menu')
        ->select(['id', 'navigation_menu_name'])
        ->whereNotIn('id', function ($q) use ($roleId) {
            $q->select('navigation_menu_id')
            ->from('role_permission')
            ->where('role_id', $roleId);
        })
        ->orderBy('navigation_menu_name')
        ->get();

        $response = $response->concat(
            $roles->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->navigation_menu_name,
            ])
        )->values();

        return response()->json($response);
    }
}
