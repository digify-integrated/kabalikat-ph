<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'currency';

    protected $fillable = [
        'currency_name',
        'symbol',
        'shorthand',
        'last_log_by'
    ];
}
