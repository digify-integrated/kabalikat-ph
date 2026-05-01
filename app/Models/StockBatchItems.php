<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatchItems extends Model
{
    protected $table = 'stock_batch_items';

    protected $fillable = [
        'stock_batch_id',
        'product_id',
        'product_name',
        'batch_number',
        'cost_per_unit',
        'expiration_date',
        'received_date',
        'quantity',
        'last_log_by'
    ];

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function inventoryLot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }

    public function getTotalCostAttribute(): float
    {
        return $this->quantity * $this->cost_per_unit;
    }
}
