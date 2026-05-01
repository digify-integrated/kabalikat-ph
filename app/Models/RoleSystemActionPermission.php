<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleSystemActionPermission extends Model
{
    protected $table = 'role_system_action_permission';

    protected $fillable = [
        'role_id',
        'role_name',
        'system_action_id',
        'system_action_name',
        'system_action_access',
        'last_log_by'
    ];

    protected $casts = [
        'system_action_access' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function systemAction()
    {
        return $this->belongsTo(SystemAction::class, 'system_action_id');
    }

    public function hasAccess(): bool
    {
        return $this->system_action_access;
    }

    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeForSystemAction($query, int $systemActionId)
    {
        return $query->where('system_action_id', $systemActionId);
    }
}
