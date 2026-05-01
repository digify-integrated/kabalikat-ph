<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $table = 'stock_transfer';

    protected $fillable = [
        'reference_number',
        'from_warehouse_id',
        'from_warehouse_name',
        'to_warehouse_id',
        'to_warehouse_name',
        'stock_transfer_status',
        'stock_transfer_reason_id',
        'stock_transfer_reason_name',
        'remarks',
        'for_approval_date',
        'approved_date',
        'cancellation_date',
        'set_to_draft_date',
        'last_log_by'
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(
            StockTransferReason::class,
            'stock_transfer_reason_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            StockTransferItems::class,
            'stock_transfer_id'
        );
    }

    public function isDraft(): bool
    {
        return $this->stock_transfer_status === 'Draft';
    }

    public function isForApproval(): bool
    {
        return $this->stock_transfer_status === 'For Approval';
    }

    public function isApproved(): bool
    {
        return $this->stock_transfer_status === 'Approved';
    }

    public function isCancelled(): bool
    {
        return $this->stock_transfer_status === 'Cancelled';
    }

    public function isInterWarehouse(): bool
    {
        return $this->from_warehouse_id !== $this->to_warehouse_id;
    }
}