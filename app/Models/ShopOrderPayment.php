<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrderPayment extends Model
{
    protected $table = 'shop_order_payment';

    protected $fillable = [
        'shop_order_id',
        'payment_method_id',
        'payment_method_name',
        'payment_amount',
        'tendered_amount',
        'change_amount',
        'reference_number',
        'reference_name',
        'payment_status',
        'paid_at',
        'voided_at',
        'refunded_at',
        'void_reason',
        'refund_reason',
        'voided_by',
        'voided_by_name',
        'refunded_by',
        'refunded_by_name',
        'remarks',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by', 'id');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by', 'id');
    }
}
