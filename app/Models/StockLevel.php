<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLevel extends Model
{
    protected $table = 'stock_level';

    protected $fillable = [
        'product_id',
        'product_name',
        'warehouse_id',
        'warehouse_name',
        'stock_status',
        'quantity',
        'batch_tracking_id',
        'expiration_date',
        'received_date',
        'cost_per_unit',
        'last_log_by'
    ];
}
