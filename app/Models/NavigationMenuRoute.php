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

    public const TYPE_INDEX   = 'index';
    public const TYPE_DETAILS = 'details';
    public const TYPE_NEW     = 'new';
    public const TYPE_IMPORT  = 'import';

    public function navigationMenu()
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    public function scopeForMenu($query, int $menuId)
    {
        return $query->where('navigation_menu_id', $menuId);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('route_type', $type);
    }

    public function isIndex(): bool
    {
        return $this->route_type === self::TYPE_INDEX;
    }

    public function isDetails(): bool
    {
        return $this->route_type === self::TYPE_DETAILS;
    }

    public function isNew(): bool
    {
        return $this->route_type === self::TYPE_NEW;
    }

    public function isImport(): bool
    {
        return $this->route_type === self::TYPE_IMPORT;
    }
}