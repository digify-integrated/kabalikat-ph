<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $table = 'stock_batch';

    protected $fillable = [
        'product_id',
        'product_name',
        'warehouse_id',
        'warehouse_name',
        'batch_status',
        'quantity',
        'batch_number',
        'cost_per_unit',
        'remarks',
        'expiration_date',
        'received_date',
        'for_approval_date',
        'approved_date',
        'cancellation_date',
        'set_to_draft_date',
        'last_log_by'
    ];
}
