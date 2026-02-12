<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $table = 'app';
    
    protected $fillable = [
        'app_name',
        'app_description',
        'app_version',
        'app_logo',
        'navigation_menu_id',
        'navigation_menu_name',
        'order_sequence',
    ];

    public function navigationMenus()
    {
        return $this->hasMany(NavigationMenu::class, 'app_id');
    }

    public function navigationMenu()
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }
}
