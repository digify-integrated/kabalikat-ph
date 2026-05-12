<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopRegisterCharge extends Model
{
    protected $table = 'shop_register_access';

    protected $fillable = [
        'shop_register_id',
        'shop_register_name',
        'charge_type_id',
        'charge_type_name',
        'automatic_application',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ChargeType::class, 'charge_type_id', 'id');
    }
}
