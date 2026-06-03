<?php

namespace App\Http\Middleware;

use App\Services\BreadcrumbBuilder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShareBreadcrumbs
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();

        $appId = (int) ($route?->parameter('appId') ?? 0);
        $navigationMenuId = (int) ($route?->parameter('navigationMenuId') ?? 0);
        $detailsId = $route?->parameter('details_id');

        $bcItems = [];
        $routeName = $route?->getName();

        if ($navigationMenuId > 0) {
            /** @var BreadcrumbBuilder $builder */
            $builder = app(BreadcrumbBuilder::class);

            // Base trail from navigation_menu tree
            $bcItems = $builder->forNavigationMenu($navigationMenuId);

            // 1. Collect all valid navigation menu IDs from the trail
            $menuIds = collect($bcItems)->pluck('id')->filter()->toArray();

            // 2. Query DB once to see which of these IDs exist in navigation_menu_route
            $routableMenuIds = [];
            if (!empty($menuIds)) {
                $routableMenuIds = DB::table('navigation_menu_route')
                    ->whereIn('navigation_menu_id', $menuIds)
                    ->pluck('navigation_menu_id')
                    ->toArray();
            }

            // 3. Inject a 'has_route' flag into each breadcrumb item
            $bcItems = array_map(function ($item) use ($routableMenuIds) {
                $item['has_route'] = in_array($item['id'], $routableMenuIds);
                return $item;
            }, $bcItems);

            // Append action crumb depending on route
            if ($routeName === 'apps.new') {
                $bcItems[] = ['id' => null, 'label' => 'New', 'has_route' => false];
            } elseif ($routeName === 'apps.import') {
                $bcItems[] = ['id' => null, 'label' => 'Import', 'has_route' => false];
            } elseif ($routeName === 'apps.details' && $detailsId !== null) {
                $bcItems[] = ['id' => null, 'label' => (string) $detailsId, 'has_route' => false];
            }
        }

        // Share to all views for the current request
        view()->share([
            'bc_app_id'             => $appId,
            'bc_navigation_menu_id' => $navigationMenuId,
            'bc_items'              => $bcItems,
            'bc_route_name'         => $routeName,
            'bc_details_id'         => $detailsId,
        ]);

        return $next($request);
    }
}