<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    protected $table = 'unit_type';

    protected $fillable = [
        'unit_type_name',
        'last_log_by'
    ];
}
