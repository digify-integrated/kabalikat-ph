<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAction extends Model
{
    protected $table = 'system_action';

    protected $fillable = [
        'system_action',
        'system_action_description',
    ];
}
