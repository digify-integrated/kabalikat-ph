<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustment';

    protected $fillable = [
        'reference_number',
        'stock_adjustment_status',
        'stock_adjustment_reason_id',
        'stock_adjustment_reason_name',
        'remarks',
        'for_approval_date',
        'approved_date',
        'cancellation_date',
        'set_to_draft_date',
        'last_log_by'
    ];

    public function reason(): BelongsTo
    {
        return $this->belongsTo(
            StockAdjustmentReason::class,
            'stock_adjustment_reason_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            StockAdjustmentItems::class,
            'stock_adjustment_id'
        );
    }

    public function isDraft(): bool
    {
        return $this->stock_adjustment_status === 'Draft';
    }

    public function isForApproval(): bool
    {
        return $this->stock_adjustment_status === 'For Approval';
    }

    public function isApproved(): bool
    {
        return $this->stock_adjustment_status === 'Approved';
    }

    public function isCancelled(): bool
    {
        return $this->stock_adjustment_status === 'Cancelled';
    }
}