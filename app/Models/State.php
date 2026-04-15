<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'state';

    protected $fillable = [
        'state_name',
        'country_id',
        'country_name',
        'last_log_by'
    ];
}
