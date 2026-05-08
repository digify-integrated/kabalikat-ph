<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeType extends Model
{
    protected $table = 'charge_type';

    protected $fillable = [
        'charge_type_name',
        'value_type',
        'charge_value',
        'is_variable',
        'application_order',
        'tax_type',
        'last_log_by'
    ];
}
