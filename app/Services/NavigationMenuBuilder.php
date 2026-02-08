<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NavigationMenuBuilder
{
    /**
     * Build a navigation tree for a given app, filtered by permissions.
     *
     * Output node shape:
     * [
     *   'id' => 123,
     *   'title' => 'Menu Name',
     *   'icon' => 'ki-outline ki-handcart fs-2' | null,
     *   'children' => [...],
     * ]
     */
    public function buildForUserAndApp(int $userId, int $appModuleId): array
    {
        // Cache per user+app. If permissions change often, lower TTL or remove cache.
        return Cache::remember(
            "navtree:user:{$userId}:app:{$appModuleId}",
            now()->addMinutes(10),
            function () use ($userId, $appModuleId) {

                // 1) Get user role ids
                $roleIds = DB::table('role_user_account')
                    ->where('user_account_id', $userId)
                    ->pluck('role_id')
                    ->map(fn ($v) => (int) $v)
                    ->all();

                if (empty($roleIds)) {
                    return [];
                }

                // 2) Load menus for this app
                $menus = DB::table('navigation_menu')
                    ->where('app_id', $appModuleId)
                    ->select([
                        'id',
                        'navigation_menu_name',
                        'navigation_menu_icon',
                        'parent_navigation_menu_id',
                        'order_sequence',
                    ])
                    ->orderByRaw('COALESCE(parent_navigation_menu_id, 0) ASC')
                    ->orderBy('order_sequence')
                    ->orderBy('navigation_menu_name')
                    ->get();

                if ($menus->isEmpty()) {
                    return [];
                }

                // 3) direct allow set = menus with ANY role_permission.read_access = 1
                $allowedIds = DB::table('role_permission')
                    ->whereIn('role_id', $roleIds)
                    ->where('read_access', true)
                    ->pluck('navigation_menu_id')
                    ->map(fn ($v) => (int) $v)
                    ->unique()
                    ->values()
                    ->all();

                $allowedSet = array_fill_keys($allowedIds, true);

                // 4) Build adjacency list (parent -> children)
                $childrenByParent = [];
                $nodeById = [];

                foreach ($menus as $m) {
                    $id = (int) $m->id;
                    $pid = $m->parent_navigation_menu_id !== null ? (int) $m->parent_navigation_menu_id : null;

                    $nodeById[$id] = [
                        'id' => $id,
                        'title' => (string) $m->navigation_menu_name,
                        'icon' => $m->navigation_menu_icon ? (string) $m->navigation_menu_icon : null,
                        'parent_id' => $pid,
                    ];

                    $key = $pid ?? 0;
                    $childrenByParent[$key] ??= [];
                    $childrenByParent[$key][] = $id;
                }

                // 5) Recursive prune: effectiveAllow = directAllow && ancestorAllow
                $build = function (int $parentKey, bool $ancestorAllowed) use (&$build, $childrenByParent, $nodeById, $allowedSet) {
                    $result = [];

                    foreach (($childrenByParent[$parentKey] ?? []) as $childId) {
                        $directAllowed = isset($allowedSet[$childId]);
                        $effectiveAllowed = $ancestorAllowed && $directAllowed;

                        // If parent chain disallows, skip entire subtree
                        if (! $effectiveAllowed) {
                            continue;
                        }

                        $children = $build($childId, true);

                        $result[] = [
                            'id' => $childId,
                            'title' => $nodeById[$childId]['title'],
                            'icon' => $nodeById[$childId]['icon'],
                            'children' => $children,
                        ];
                    }

                    return $result;
                };

                // Root parentKey is 0 (we used 0 for null parent)
                return $build(0, true);
            }
        );
    }
}
