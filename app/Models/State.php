<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $table = 'state';

    protected $fillable = [
        'state_name',
        'country_id',
        'country_name',
        'last_log_by'
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'state_id', 'id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->state_name;
    }

    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('state_name');
    }
}
