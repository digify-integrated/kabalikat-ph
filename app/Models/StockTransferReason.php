<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferReason extends Model
{
    protected $table = 'stock_transfer_reason';

    protected $fillable = [
        'stock_transfer_reason_name',
        'last_log_by'
    ];
}
