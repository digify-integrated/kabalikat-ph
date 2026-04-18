<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouse';

    protected $fillable = [
        'warehouse_name',
        'contact_person',
        'warehouse_status',
        'warehouse_type_id',
        'warehouse_type_name',
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
