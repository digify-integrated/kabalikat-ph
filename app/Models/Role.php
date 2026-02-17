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

    public function permissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    public function systemActionPermissions()
    {
        return $this->hasMany(RoleSystemActionPermission::class, 'role_id');
    }

    public function roleUsers()
    {
        return $this->hasMany(RoleUserAccount::class, 'role_id');
    }
}
