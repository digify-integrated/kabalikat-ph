<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderCancellations extends Model
{
    protected $table = 'purchase_order_cancellations';

    protected $fillable = [
        'purchase_order_id',
        'purchase_order_items_id',
        'product_id',
        'product_name',
        'cancelled_quantity',
        'reason',
        'cancelled_date',
        'last_log_by'
    ];

    protected $casts = [
        'cancelled_quantity' => 'decimal:2',
        'cancelled_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItems::class, 'purchase_order_items_id');
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