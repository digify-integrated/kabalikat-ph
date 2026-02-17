<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAction extends Model
{
    protected $table = 'system_action';

    protected $fillable = [
        'system_action_name',
        'system_action_description',
        'last_log_by'
    ];
}
