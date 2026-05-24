<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrderRequest extends Model
{
    protected $table = 'shop_order_request';

    protected $fillable = [
        'shop_order_id',
        'order_number',
        'request_type',
        'request_status',
        'request_reason',
        'requested_by',
        'requested_by_name',
        'requested_at',
        'approved_by',
        'approved_by_name',
        'approved_at',
        'approval_remarks',
        'rejected_by',
        'rejected_by_name',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_by_name',
        'cancelled_at',
        'cancellation_reason',
        'last_log_by'
    ];
 
    public function shopOrder(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id');
    }
}
