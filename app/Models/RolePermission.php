<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permission';

    protected $fillable = [
        'role_id',
        'role_name',
        'navigation_menu_id',
        'navigation_menu_name',
        'read_access',
        'write_access',
        'create_access',
        'delete_access',
        'import_access',
        'export_access',
        'logs_access',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function navigationMenu()
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }
}
