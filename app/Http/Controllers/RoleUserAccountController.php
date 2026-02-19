<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleUserAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class RoleUserAccountController extends Controller
{
    public function saveRoleAssignment(Request $request)
    {
        $validator = Validator::make(
        $request->all(),
        [
                'user_account_id'   => ['required', 'integer', Rule::exists('users', 'id')],
                'role_id'           => ['required', 'array', 'min:1'],
                'role_id.*'         => ['required', 'integer', Rule::exists('role', 'id')],
            ],
            [
                'user_account_id.required'  => 'Please select a user account',
                'user_account_id.exists'    => 'The selected user account does not exist',
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

        $userAccountId = (int) $validated['user_account_id'];
        $roleIds = collect($validated['role_id'])->map(fn ($id) => (int) $id)->unique()->values();

        $userName = (string) User::query()
            ->whereKey($userAccountId)
            ->value('name');

        $lastLogBy = (int) auth()->id();

        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->get(['id', 'role_name'])
            ->keyBy('id');

        $now = now();
        $rows = $roleIds->map(function (int $roleId) use ($roles, $userAccountId, $userName, $lastLogBy, $now) {
            return [
                'role_id'           => $roleId,
                'role_name'         => (string) ($roles[$roleId]->role_name ?? ''),
                'user_account_id'   => $userAccountId,
                'user_name'         => $userName,
                'last_log_by'       => $lastLogBy,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        })->all();

        DB::transaction(function () use ($rows) {
            DB::table('role_user_account')->upsert(
                $rows,
                ['role_id', 'user_account_id'],
                ['role_name', 'user_name', 'last_log_by', 'updated_at']
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'The role has been assigned successfully',
        ]);
    }

    public function saveUserAccountAssignment(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'role_id'              => ['required', 'integer', Rule::exists('role', 'id')],
                'user_account_id'      => ['required', 'array', 'min:1'],
                'user_account_id.*'    => ['required', 'integer', Rule::exists('users', 'id')],
            ],
            [
                'role_id.required'           => 'Please select a role',
                'role_id.exists'             => 'The selected role does not exist',

                'user_account_id.required'   => 'Please select the user account(s) you wish to assign to the role',
                'user_account_id.array'      => 'The selected user accounts must be provided as a list',
                'user_account_id.min'        => 'Please select at least one user account',
                'user_account_id.*.exists'   => 'One or more of the selected user accounts is invalid',
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

        $userAccountIds = collect($validated['user_account_id'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $roleName = (string) Role::query()
            ->whereKey($roleId)
            ->value('role_name');

        $lastLogBy = (int) auth()->id();

        $users = User::query()
            ->whereIn('id', $userAccountIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $now = now();

        $rows = $userAccountIds->map(function (int $userAccountId) use ($users, $roleId, $roleName, $lastLogBy, $now) {
            return [
                'role_id'         => $roleId,
                'role_name'       => $roleName,
                'user_account_id' => $userAccountId,
                'user_name'       => (string) ($users[$userAccountId]->name ?? ''),
                'last_log_by'     => $lastLogBy,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        })->all();

        DB::transaction(function () use ($rows) {
            DB::table('role_user_account')->upsert(
                $rows,
                ['role_id', 'user_account_id'],
                ['role_name', 'user_name', 'last_log_by', 'updated_at']
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'The user accounts have been assigned successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', 'exists:role_user_account,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $rolePermission = RoleUserAccount::query()->select(['id'])->findOrFail($referenceId);

            $rolePermission->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The role user account has been deleted successfully',
        ]);
    }

    public function generateUserAccountRoleTable(Request $request)
    {
        $userAccountId = (int) $request->input('user_account_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $rolePermissions = DB::table('role_user_account')
        ->where('user_account_id', $userAccountId)
        ->orderBy('role_name')
        ->get();

        $canUpdateRoleAccess = app(SystemActionController::class)
            ->userHasRoleAccessForAction(3, Auth::id());

        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $rolePermissions->map(function ($row) use ($canUpdateRoleAccess, $logsAccess)  {
            $rolePermissionId = $row->id;
            $roleName = $row->role_name;

            $deleteButton = '';
            if($canUpdateRoleAccess ?? false === true){
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

            return [
                'ROLE' => $roleName,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }

    public function generateRoleUserAccountTable(Request $request)
    {
        $roleId = (int) $request->input('role_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $rolePermissions = DB::table('role_user_account')
        ->where('role_id', $roleId)
        ->orderBy('user_name')
        ->get();

        $canUpdateRoleAccess = app(SystemActionController::class)
            ->userHasRoleAccessForAction(5, Auth::id());

        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $rolePermissions->map(function ($row) use ($canUpdateRoleAccess, $logsAccess)  {
            $rolePermissionId = $row->id;
            $userAccountName = $row->user_name;          

            $deleteButton = '';
            if($canUpdateRoleAccess ?? false === true){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-role-user-account" data-reference-id="' . $rolePermissionId . '" title="Delete Role Permission">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-role-user-account-log-notes" data-reference-id="' . $rolePermissionId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'USER_ACCOUNT' => $userAccountName,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }

    public function generateUserAccountRoleDualListboxOptions(Request $request)
    {
        $userAccountId = (int) $request->input('userAccountId');

        $response = collect();

        $roles = DB::table('role')
        ->select(['id', 'role_name'])
        ->whereNotIn('id', function ($q) use ($userAccountId) {
            $q->select('role_id')
            ->from('role_user_account')
            ->where('user_account_id', $userAccountId);
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

    public function generateRoleUserAccountDualListboxOptions(Request $request)
    {
        $roleId = (int) $request->input('roleId');

        $response = collect();

        $roles = DB::table('users')
        ->select(['id', 'name'])
        ->whereNotIn('id', function ($q) use ($roleId) {
            $q->select('user_account_id')
            ->from('role_user_account')
            ->where('role_id', $roleId);
        })
        ->orderBy('name')
        ->get();

        $response = $response->concat(
            $roles->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->name,
            ])
        )->values();

        return response()->json($response);
    }
}
