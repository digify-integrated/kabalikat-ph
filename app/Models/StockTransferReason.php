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

    public function getLabelAttribute(): string
    {
        return $this->stock_transfer_reason_name;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('stock_transfer_reason_name');
    }
}