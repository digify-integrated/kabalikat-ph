<?php

namespace App\Http\Middleware;

use App\Services\BreadcrumbBuilder;
use Closure;
use Illuminate\Http\Request;

class ShareBreadcrumbs
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();

        $appModuleId      = (int) ($route?->parameter('appModuleId') ?? 0);
        $navigationMenuId = (int) ($route?->parameter('navigationMenuId') ?? 0);
        $detailsId        = $route?->parameter('details_id');

        $bcItems = [];
        $routeName = $route?->getName();

        // Only build breadcrumbs when we have a navigation menu id
        if ($navigationMenuId > 0) {
            /** @var BreadcrumbBuilder $builder */
            $builder = app(BreadcrumbBuilder::class);

            // Base trail from navigation_menu tree
            $bcItems = $builder->forNavigationMenu($navigationMenuId);

            // Append action crumb depending on route
            if ($routeName === 'apps.new') {
                $bcItems[] = ['id' => null, 'label' => 'New'];
            } elseif ($routeName === 'apps.import') {
                $bcItems[] = ['id' => null, 'label' => 'Import'];
            } elseif ($routeName === 'apps.details' && $detailsId !== null) {
                $bcItems[] = ['id' => null, 'label' => (string) $detailsId];
            }
        }

        // Share to all views for the current request
        view()->share([
            'bc_app_id'             => $appModuleId,
            'bc_navigation_menu_id' => $navigationMenuId,
            'bc_items'              => $bcItems,
            'bc_route_name'         => $routeName,
            'bc_details_id'         => $detailsId,
        ]);

        return $next($request);
    }
}
