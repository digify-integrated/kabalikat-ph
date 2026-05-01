<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseType extends Model
{
    protected $table = 'warehouse_type';

    protected $fillable = [
        'warehouse_type_name',
        'last_log_by'
    ];

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'warehouse_type_id');
    }

    public function getLabelAttribute(): string
    {
        return $this->warehouse_type_name;
    }
}