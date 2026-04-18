<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'unit';

    protected $fillable = [
        'unit_name',
        'abbreviation',
        'unit_type_id',
        'unit_type_name',
        'is_base_unit',
        'conversion_factor',
        'last_log_by'
    ];
}
