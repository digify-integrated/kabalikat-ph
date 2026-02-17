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

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function systemAction()
    {
        return $this->belongsTo(SystemAction::class, 'system_action_id');
    }
}
