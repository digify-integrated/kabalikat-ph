<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopRegisterKitchenRoute extends Model
{
    protected $table = 'shop_register_kitchen_route';

    protected $fillable = [
        'shop_register_id',
        'shop_register_name',
        'kitchen_route_id',
        'kitchen_route_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function kitchenRoute(): BelongsTo
    {
        return $this->belongsTo(KitchenRoute::class, 'kitchen_route_id', 'id');
    }
}
