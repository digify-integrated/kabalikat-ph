<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopRegisterProduct extends Model
{
    protected $table = 'shop_register_product';

    protected $fillable = [
        'shop_register_id',
        'shop_register_name',
        'product_id',
        'product_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
