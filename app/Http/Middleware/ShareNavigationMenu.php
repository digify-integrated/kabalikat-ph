<?php

namespace App\Http\Middleware;

use App\Services\NavigationMenuBuilder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShareNavigationMenu
{
    public function handle(Request $request, Closure $next)
    {
        $userId = (int) ($request->user()?->id ?? 0);
        $appId = (int) ($request->route('appId') ?? 0);

        $user = DB::table('users')
            ->where('id', $userId)
            ->first();

        $defaultProfilePicture = asset('assets/media/default/default-avatar.jpg');
        $path = trim((string) ($user->profile_picture ?? ''));

        $profilePictureUrl = $path !== '' && Storage::disk('public')->exists($path)
            ? Storage::url($path)
            : $defaultProfilePicture;

        $navTree = [];
        if ($userId > 0 && $appId > 0) {
            /** @var NavigationMenuBuilder $builder */
            $builder = app(NavigationMenuBuilder::class);
            $navTree = $builder->buildForUserAndApp($userId, $appId);
        }

        view()->share([
            'nav_app_id' => $appId,
            'nav_tree' => $navTree,
            'navName' => $user->name ?? null,
            'navEmail' => $user->email ?? null,
            'navProfilePicture' => $profilePictureUrl,
        ]);

        return $next($request);
    }
}
