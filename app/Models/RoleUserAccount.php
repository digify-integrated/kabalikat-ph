<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleUserAccount extends Model
{
    protected $table = 'role_user_account';

    protected $fillable = [
        'role_id',
        'user_account_id',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function userAccount()
    {
        return $this->belongsTo(User::class, 'user_account_id');
    }
}
