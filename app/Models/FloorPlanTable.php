<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FloorPlanTable extends Model
{
    protected $table = 'floor_plan_table';

    protected $fillable = [
        'floor_plan_id',
        'floor_plan_name',
        'table_number',
        'seats',
        'last_log_by'
    ];

    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class, 'floor_plan_id', 'id');
    }
}
