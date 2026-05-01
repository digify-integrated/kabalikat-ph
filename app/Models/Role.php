<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'role';

    protected $fillable = [
        'role_name',
        'role_description',
        'last_log_by'
    ];

    public function roleUsers()
    {
        return $this->hasMany(RoleUserAccount::class, 'role_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'role_user_account',
            'role_id',
            'user_account_id'
        )->withTimestamps();
    }

    public function menuPermissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    public function systemActionPermissions()
    {
        return $this->hasMany(RoleSystemActionPermission::class, 'role_id');
    }

    public function hasMenuAccess(int $navigationMenuId, string $ability): bool
    {
        $permission = $this->menuPermissions
            ->where('navigation_menu_id', $navigationMenuId)
            ->first();

        if (!$permission) {
            return false;
        }

        return (bool)($permission->{$ability . '_access'} ?? false);
    }
}
