<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItems extends Model
{
    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'stock_level_id',
        'quantity',
        'last_log_by'
    ];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }    

    public function stockLevel(): BelongsTo
    {
        return $this->belongsTo(StockLevel::class, 'stock_level_id');
    }
}