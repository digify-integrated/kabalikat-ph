<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrderAppliedDiscount extends Model
{
    protected $table = 'shop_order_applied_discount';

    protected $fillable = [
        'shop_order_id',
        'discount_type_id',
        'discount_type_name',
        'value_type',
        'discount_value',
        'application_order',
        'is_vat_exempt',
        'discount_rate',
        'discount_amount',
        'vat_exempt_amount',
        'reference_number',
        'reference_name',
        'remarks',
        'applied_by',
        'applied_by_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function discountType(): BelongsTo
    {
        return $this->belongsTo(DiscountType::class, 'discount_type_id', 'id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by', 'id');
    }
}
