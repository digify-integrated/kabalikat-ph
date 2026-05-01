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
        'last_log_by'
    ];
    
    public function navigationMenus()
    {
        return $this->hasMany(NavigationMenu::class, 'app_id');
    }

    public function primaryNavigationMenu()
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    public function hasPrimaryNavigationMenu(): bool
    {
        return !is_null($this->navigation_menu_id);
    }

    public function getPrimaryMenuName(): ?string
    {
        return $this->navigation_menu_name;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_sequence');
    }
}
