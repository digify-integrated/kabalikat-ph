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

        if (!is_numeric($navigationMenuId)) {
            abort(404);
        }

        $navigationMenuId = (int) $navigationMenuId;

        $roleIds = DB::table('role_user_account')
            ->where('user_account_id', $user->id)
            ->pluck('role_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if (empty($roleIds)) {
            abort(404);
        }

        // Build allowed set for this user (ANY role true)
        $allowedIds = DB::table('role_permission')
            ->whereIn('role_id', $roleIds)
            ->where('read_access', true)
            ->pluck('navigation_menu_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $allowedSet = array_fill_keys($allowedIds, true);

        // Must be allowed on the requested menu
        if (!isset($allowedSet[$navigationMenuId])) {
            abort(404);
        }

        // Enforce ancestor permissions (if any parent is not allowed => deny)
        $currentId = $navigationMenuId;

        while (true) {
            $row = DB::table('navigation_menu')
                ->where('id', $currentId)
                ->select('parent_navigation_menu_id')
                ->first();

            if (!$row) {
                abort(404);
            }

            if ($row->parent_navigation_menu_id === null) {
                break;
            }

            $parentId = (int) $row->parent_navigation_menu_id;

            if (!isset($allowedSet[$parentId])) {
                abort(404);
            }

            $currentId = $parentId;
        }

        return $next($request);
    }
}
