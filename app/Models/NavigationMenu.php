<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationMenu extends Model
{
    protected $table = 'navigation_menu';

    protected $fillable = [
        'navigation_menu_name',
        'navigation_menu_icon',
        'app_id',
        'app_name',
        'parent_navigation_menu_id',
        'parent_navigation_menu_name',
        'database_table',
        'order_sequence',
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function parent()
    {
        return $this->belongsTo(NavigationMenu::class, 'parent_navigation_menu_id');
    }

    public function children()
    {
        return $this->hasMany(NavigationMenu::class, 'parent_navigation_menu_id');
    }
}
