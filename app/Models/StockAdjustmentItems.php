<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItems extends Model
{
    protected $table = 'stock_adjustment_items';

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'product_name',
        'warehouse_id',
        'warehouse_name',
        'inventory_lot_id',
        'adjustment_type',
        'current_quantity',
        'new_quantity',
        'last_log_by'
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function inventoryLot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
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