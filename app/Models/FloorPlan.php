<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FloorPlan extends Model
{
    protected $table = 'floor_plan';

    protected $fillable = [
        'floor_plan_name',
        'last_log_by'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FloorPlanTable::class, 'floor_plan_id');
    }

    public function shopRegisteFloorPlan(): HasMany
    {
        return $this->hasMany(ShopRegisterFloorPlan::class, 'floor_plan_id', 'id');
    }
}
