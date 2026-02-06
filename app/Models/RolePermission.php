<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permission';

    protected $fillable = [
        'role_id',
        'navigation_menu_id',
        'read_access',
        'write_access',
        'create_access',
        'delete_access',
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
