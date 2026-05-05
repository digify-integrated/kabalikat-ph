<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderReceiptItems extends Model
{
    protected $table = 'purchase_order_receipt_items';

    protected $fillable = [
        'purchase_order_id',
        'purchase_order_items_id',
        'product_id',
        'product_name',
        'batch_number',
        'cost_per_unit',
        'expiration_date',
        'received_date',
        'received_quantity',
        'last_log_by'
    ];

    protected $casts = [
        'cost_per_unit' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'expiration_date' => 'date',
        'received_date' => 'date',
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