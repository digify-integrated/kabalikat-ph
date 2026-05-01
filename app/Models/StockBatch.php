<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    protected $table = 'stock_batch';

    protected $fillable = [
        'reference_number',
        'warehouse_id',
        'warehouse_name',
        'stock_batch_status',
        'remarks',
        'for_approval_date',
        'approved_date',
        'cancellation_date',
        'set_to_draft_date',
        'last_log_by'
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockBatchItems::class, 'stock_batch_id');
    }

    public function isDraft(): bool
    {
        return $this->stock_batch_status === 'Draft';
    }

    public function isApproved(): bool
    {
        return $this->stock_batch_status === 'Approved';
    }

    public function isCancelled(): bool
    {
        return $this->stock_batch_status === 'Cancelled';
    }
}
