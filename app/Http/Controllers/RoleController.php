<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\RoleSystemActionPermission;
use App\Models\RoleUserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['nullable', 'integer'],
            'role_name' => ['required', 'string', 'max:255'],
            'role_description' => ['required', 'string', 'max:255']
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $payload = [
            'role_name' => $validated['role_name'],
            'role_description' => $validated['role_description'],
            'last_log_by' => Auth::id(),
        ];

        $roleId = $validated['role_id'] ?? null;

        if ($roleId && Role::query()->whereKey($roleId)->exists()) {
            $role = Role::query()->findOrFail($roleId);
            $role->update($payload);
        } else {
            $role = Role::query()->create($payload);
        }

        RolePermission::query()
            ->where('role_id', $role->id)
            ->update([
                'role_name' => $role->role_name,
                'last_log_by' => Auth::id(),
            ]);

        RoleSystemActionPermission::query()
            ->where('role_id', $role->id)
            ->update([
                'role_name' => $role->role_name,
                'last_log_by' => Auth::id(),
            ]);

        RoleUserAccount::query()
            ->where('role_id', $role->id)
            ->update([
                'role_name' => $role->role_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $role->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The system action has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:role,id'],
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
            $role = Role::query()->select(['id'])->findOrFail($detailId);

            $role->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The role has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:role,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Role::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected roles have been deleted successfully',
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

        $role = DB::table('role')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$role) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'System action not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'roleName' => $role->role_name ?? null,
            'roleDescription' => $role->role_description ?? null
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $roles = DB::table('role')
        ->orderBy('role_name')
        ->get();

        $response = $roles->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $roleId = $row->id;
            $roleName = $row->role_name;
            $roleDescription = $row->role_description;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $roleId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$roleId.'">
                    </div>
                ',
                'ROLE' => '
                    <div class="d-flex align-items-center">
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$roleName.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$roleDescription.'</small>
                            </div>
                        </div>
                    </div>
                ',
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
