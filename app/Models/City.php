<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'city';

    protected $fillable = [
        'city_name',
        'state_id',
        'state_name',
        'country_id',
        'country_name',
        'last_log_by'
    ];

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function getFullLocationAttribute(): string
    {
        return "{$this->city_name}, {$this->state_name}, {$this->country_name}";
    }

    public function scopeForState($query, int $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }
}
