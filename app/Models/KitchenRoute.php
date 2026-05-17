<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenRoute extends Model
{
    protected $table = 'kitchen_route';

    protected $fillable = [
        'kitchen_route_name',
        'last_log_by'
    ];

    public function shopRegistePaymentMethod(): HasMany
    {
        return $this->hasMany(ShopRegisterKitchenRoute::class, 'kitchen_route_id', 'id');
    }
}
