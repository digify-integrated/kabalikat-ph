<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentReason extends Model
{
    protected $table = 'stock_adjustment_reason';

    protected $fillable = [
        'stock_adjustment_reason_name',
        'last_log_by'
    ];

    public function getLabelAttribute(): string
    {
        return $this->stock_adjustment_reason_name;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('stock_adjustment_reason_name');
    }
}
