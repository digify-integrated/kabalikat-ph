<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'city';

    protected $fillable = [
        'city_name',
        'state_id',
        'state_name',
        'country_id',
        'country_name',
        'last_log_by'
    ];
}
