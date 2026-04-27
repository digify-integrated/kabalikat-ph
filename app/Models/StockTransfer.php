<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $table = 'stock_transfer';

    protected $fillable = [
        'product_id',
        'product_name',
        'stock_level_from_id',
        'stock_level_to_id',
        'stock_transfer_status',
        'quantity',
        'stock_transfer_reason_id',
        'stock_transfer_reason_name',
        'remarks',
        'for_approval_date',
        'approved_date',
        'cancellation_date',
        'set_to_draft_date',
        'last_log_by'
    ];
}
