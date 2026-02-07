<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuReadAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $navigationMenuId = $request->route('navigationMenuId');

        // If the route param is missing or not numeric, treat as not found.
        if (!is_numeric($navigationMenuId)) {
            abort(404);
        }

        // Get the user's role IDs from role_user_account
        $roleIds = DB::table('role_user_account')
            ->where('user_account_id', $user->id)
            ->pluck('role_id');

        // If user has no roles, deny as 404
        if ($roleIds->isEmpty()) {
            abort(404);
        }

        // Check role_permission for ANY role with read_access = 1 for this menu
        $hasReadAccess = DB::table('role_permission')
            ->whereIn('role_id', $roleIds)
            ->where('navigation_menu_id', (int) $navigationMenuId)
            ->where('read_access', true)
            ->exists();

        if (! $hasReadAccess) {
            abort(404);
        }

        return $next($request);
    }
}
