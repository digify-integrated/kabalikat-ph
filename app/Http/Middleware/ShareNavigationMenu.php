<?php

namespace App\Http\Middleware;

use App\Services\NavigationMenuBuilder;
use Closure;
use Illuminate\Http\Request;

class ShareNavigationMenu
{
    public function handle(Request $request, Closure $next)
    {
        $userId = (int) ($request->user()?->id ?? 0);
        $appModuleId = (int) ($request->route('appModuleId') ?? 0);

        $navTree = [];
        if ($userId > 0 && $appModuleId > 0) {
            /** @var NavigationMenuBuilder $builder */
            $builder = app(NavigationMenuBuilder::class);
            $navTree = $builder->buildForUserAndApp($userId, $appModuleId);
        }

        view()->share([
            'nav_appModuleId' => $appModuleId,
            'nav_tree' => $navTree,
        ]);

        return $next($request);
    }
}
