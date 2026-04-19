<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitConversion extends Model
{
    protected $table = 'unit_conversion';

    protected $fillable = [
        'from_unit_id',
        'from_unit_name',
        'to_unit_id',
        'to_unit_name',
        'conversion_factor',
        'last_log_by'
    ];
}
