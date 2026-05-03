<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItems extends Model
{
    protected $table = 'stock_adjustment_items';

    protected $fillable = [
        'stock_adjustment_id',
        'stock_level_id',
        'adjustment_type',
        'adjustment_quantity',
        'current_quantity',
        'new_quantity',
        'last_log_by'
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function stockLevel(): BelongsTo
    {
        return $this->belongsTo(StockLevel::class, 'stock_level_id');
    }

    public function isIncrease(): bool
    {
        return $this->adjustment_type === 'Add Stock';
    }

    public function isDecrease(): bool
    {
        return $this->adjustment_type === 'Remove Stock';
    }

    public function isSetExact(): bool
    {
        return $this->adjustment_type === 'Set Exact Stock';
    }

    public function quantityDifference(): float
    {
        return ($this->new_quantity ?? 0) - ($this->current_quantity ?? 0);
    }
}