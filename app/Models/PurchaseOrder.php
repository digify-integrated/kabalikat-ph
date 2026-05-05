<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_order';

    protected $fillable = [
        'reference_number',
        'supplier_id',
        'supplier_name',
        'warehouse_id',
        'warehouse_name',
        'po_status',
        'remarks',
        'order_date',
        'expected_delivery_date',
        'for_approval_date',
        'approved_date',
        'on_process_date',
        'completed_date',
        'cancellation_date',
        'last_log_by'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'for_approval_date' => 'date',
        'approved_date' => 'date',
        'on_process_date' => 'date',
        'completed_date' => 'date',
        'cancellation_date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItems::class, 'purchase_order_id');
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceiptItems::class, 'purchase_order_id');
    }

    public function cancellations(): HasMany
    {
        return $this->hasMany(PurchaseOrderCancellations::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function lastLogBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_log_by');
    }
}