<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    protected $table = 'supplier';

    protected $fillable = [
        'supplier_name',
        'contact_person',
        'supplier_status',
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

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->supplier_status === 'Active';
    }

    public function isInactive(): bool
    {
        return $this->supplier_status === 'Inactive';
    }

    public function getFullLocationAttribute(): string
    {
        return collect([
            $this->city_name,
            $this->state_name,
            $this->country_name,
        ])->filter()->implode(', ');
    }

    public function scopeActive($query)
    {
        return $query->where('supplier_status', 'Active');
    }

    public function scopeInactive($query)
    {
        return $query->where('supplier_status', 'Inactive');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('supplier_name');
    }
}