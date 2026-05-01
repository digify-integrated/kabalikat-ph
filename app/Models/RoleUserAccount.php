<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleUserAccount extends Model
{
    protected $table = 'role_user_account';

    protected $fillable = [
        'role_id',
        'role_name',
        'user_account_id',
        'user_name',
        'last_log_by'
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_account_id');
    }

    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_account_id', $userId);
    }
}
