<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    protected $table = 'warehouse';

    protected $fillable = [
        'warehouse_name',
        'contact_person',
        'warehouse_status',
        'warehouse_type_id',
        'warehouse_type_name',
        'address',
        'city_id',
        'city_name',
        'state_id',
        'state_name',
        'country_id',
        'country_name',
        'phone',
        'telephone',
        'email',
        'last_log_by'
    ];

    public function warehouseType(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class, 'warehouse_type_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function isActive(): bool
    {
        return $this->warehouse_status === 'Active';
    }

    public function scopeActive($query)
    {
        return $query->where('warehouse_status', 'Active');
    }
}