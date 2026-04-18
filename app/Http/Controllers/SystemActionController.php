<?php

namespace App\Http\Controllers;

use App\Models\RoleSystemActionPermission;
use App\Models\SystemAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SystemActionController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'system_action_id' => ['nullable', 'integer'],
            'system_action_name' => ['required', 'string', 'max:255'],
            'system_action_description' => ['required', 'string', 'max:255']
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
            'system_action_name' => $validated['system_action_name'],
            'system_action_description' => $validated['system_action_description'],
            'last_log_by' => Auth::id(),
        ];

        $systemActionId = $validated['system_action_id'] ?? null;

        if ($systemActionId && SystemAction::query()->whereKey($systemActionId)->exists()) {
            $systemAction = SystemAction::query()->findOrFail($systemActionId);
            $systemAction->update($payload);
        } else {
            $systemAction = SystemAction::query()->create($payload);
        }

        RoleSystemActionPermission::query()
            ->where('system_action_id', $systemAction->id)
            ->update([
                'system_action_name' => $systemAction->system_action_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $systemAction->id,
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
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('system_action', 'id')],
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
            $systemAction = SystemAction::query()->select(['id'])->findOrFail($detailId);

            $systemAction->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The system action has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('system_action', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            SystemAction::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected system actions have been deleted successfully',
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

        $systemAction = DB::table('system_action')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$systemAction) {
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
            'systemActionName' => $systemAction->system_action_name ?? null,
            'systemActionDescription' => $systemAction->system_action_description ?? null
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $systemActions = DB::table('system_action')
        ->orderBy('system_action_name')
        ->get();

        $response = $systemActions->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $systemActionId = $row->id;
            $systemActionName = $row->system_action_name;
            $systemActionDescription = $row->system_action_description;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $systemActionId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$systemActionId.'">
                    </div>
                ',
                'SYSTEM_ACTION' => '
                    <div class="d-flex align-items-center">
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$systemActionName.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$systemActionDescription.'</small>
                            </div>
                        </div>
                    </div>
                ',
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }

    public function countUserRoleAccessForAction(int $systemActionId, int $userAccountId): int
    {
        return (int) DB::table('role_system_action_permission as rsap')
            ->join('role_user_account as rua', 'rua.role_id', '=', 'rsap.role_id')
            ->where('rsap.system_action_id', $systemActionId)
            ->where('rsap.system_action_access', 1)
            ->where('rua.user_account_id', $userAccountId)
            ->count('rsap.role_id');
    }

    public function userHasRoleAccessForAction(int $systemActionId, int $userAccountId): bool
    {
        return $this->countUserRoleAccessForAction($systemActionId, $userAccountId) > 0;
    }
}
