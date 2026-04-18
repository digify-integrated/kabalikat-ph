<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseType extends Model
{
    protected $table = 'warehouse_type';

    protected $fillable = [
        'warehouse_type_name',
        'last_log_by'
    ];
}
