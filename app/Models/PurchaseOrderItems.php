<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model
;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItems extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'product_name',
        'ordered_quantity',
        'received_quantity',
        'cancelled_quantity',
        'remaining_quantity',
        'estimated_cost',
        'last_log_by'
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'cancelled_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceiptItems::class, 'purchase_order_items_id');
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(PurchaseOrderCancellations::class, 'purchase_order_items_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function lastLogBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_log_by');
    }
}