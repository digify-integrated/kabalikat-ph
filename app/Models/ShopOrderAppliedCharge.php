<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrderAppliedCharge extends Model
{
    protected $table = 'shop_order_applied_charge';

    protected $fillable = [
        'shop_order_id',
        'charge_type_id',
        'charge_type_name',
        'value_type',
        'charge_value',
        'application_order',
        'tax_type',
        'charge_rate',
        'charge_amount',
        'vatable_amount',
        'vat_amount',
        'remarks',
        'applied_by',
        'applied_by_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function chargeType(): BelongsTo
    {
        return $this->belongsTo(ChargeType::class, 'charge_type_id', 'id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by', 'id');
    }
}
