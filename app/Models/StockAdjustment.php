<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustment';

    protected $fillable = [
        'stock_level_id',
        'adjustment_type',
        'stock_adjustment_status',
        'quantity',
        'stock_adjustment_reason_id',
        'stock_adjustment_reason_name',
        'remarks',
        'for_approval_date',
        'approved_date',
        'cancellation_date',
        'set_to_draft_date',
        'last_log_by'
    ];
}
