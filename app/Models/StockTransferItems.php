<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItems extends Model
{
    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'product_name',
        'inventory_lot_id',
        'quantity',
        'last_log_by'
    ];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function inventoryLot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }
}