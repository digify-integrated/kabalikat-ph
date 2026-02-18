<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemActionController extends Controller
{
    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $apps = DB::table('system_action')
        ->orderBy('system_action_name')
        ->get();

        $response = $apps->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
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
