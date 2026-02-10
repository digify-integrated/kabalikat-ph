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
        $appId = (int) ($request->route('appId') ?? 0);

        $navTree = [];
        if ($userId > 0 && $appId > 0) {
            /** @var NavigationMenuBuilder $builder */
            $builder = app(NavigationMenuBuilder::class);
            $navTree = $builder->buildForUserAndApp($userId, $appId);
        }

        view()->share([
            'nav_app_id' => $appId,
            'nav_tree' => $navTree,
        ]);

        return $next($request);
    }
}
