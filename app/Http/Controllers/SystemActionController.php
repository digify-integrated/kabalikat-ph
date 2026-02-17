<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemActionController extends Controller
{
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
