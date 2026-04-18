<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'supplier';

    protected $fillable = [
        'supplier_name',
        'contact_person',
        'supplier_status',
        'address',
        'city_id',
        'city_name',
        'state_id',
        'state_name',
        'country_id',
        'country_name',
        'phone',
        'telephone',
        'email',
        'last_log_by'
    ];
}
