<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountType extends Model
{
    protected $table = 'discount_type';

    protected $fillable = [
        'discount_type_name',
        'value_type',
        'discount_value',
        'is_variable',
        'application_order',
        'is_vat_exempt',
        'last_log_by'
    ];
}
