<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountType extends Model
{
    protected $table = 'discount_type';

    protected $fillable = [
        'discount_type_name',
        'value_type',
        'discount_value',
        'is_variable',
        'application_order',
        'is_vat_exempt',
        'last_log_by'
    ];

    public function shopRegisterCharge(): HasMany
    {
        return $this->hasMany(ShopRegisterDiscount::class, 'discount_type_id', 'id');
    }
}
