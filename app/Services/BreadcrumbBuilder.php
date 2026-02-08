<?php

namespace App\Services;

use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Cache;

class BreadcrumbBuilder
{
    public function forNavigationMenu(int $navigationMenuId): array
    {
        return Cache::remember(
            "breadcrumbs:navmenu:{$navigationMenuId}",
            now()->addMinutes(30),
            function () use ($navigationMenuId) {

                $trail = [];
                $visited = [];

                $current = NavigationMenu::query()
                    ->select(['id', 'navigation_menu_name', 'parent_navigation_menu_id'])
                    ->find($navigationMenuId);

                while ($current) {
                    if (isset($visited[$current->id])) {
                        break;
                    }
                    $visited[$current->id] = true;

                    $trail[] = [
                        'id'    => $current->id,
                        'label' => $current->navigation_menu_name,
                    ];

                    if (!$current->parent_navigation_menu_id) {
                        break;
                    }

                    $current = NavigationMenu::query()
                        ->select(['id', 'navigation_menu_name', 'parent_navigation_menu_id'])
                        ->find($current->parent_navigation_menu_id);
                }

                return array_reverse($trail);
            }
        );
    }
}
