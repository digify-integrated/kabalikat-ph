<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $table = 'stock_movement';

    protected $fillable = [
        'stock_level_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'remarks',
        'last_log_by'
    ];
}