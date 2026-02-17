<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationMenuRoute extends Model
{
    protected $table = 'navigation_menu_route';

    protected $fillable = [
        'navigation_menu_id',
        'route_type',
        'view_file',
        'js_file',
        'last_log_by'
    ];
}
