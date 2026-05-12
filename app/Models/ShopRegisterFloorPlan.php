<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopRegisterFloorPlan extends Model
{
    protected $table = 'shop_register_floor_plan';

    protected $fillable = [
        'shop_register_id',
        'shop_register_name',
        'floor_plan_id',
        'floor_plan_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class, 'floor_plan_id', 'id');
    }
}
