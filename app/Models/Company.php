<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';

    protected $fillable = [
        'company_name',
        'company_logo',
        'address',
        'city_id',
        'city_name',
        'state_id',
        'state_name',
        'country_id',
        'country_name',
        'tax_id',
        'currency_id',
        'currency_name',
        'phone',
        'telephone',
        'email',
        'website',
        'last_log_by'
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getFullAddressAttribute(): string
    {
        return trim("{$this->address}, {$this->city_name}, {$this->state_name}, {$this->country_name}");
    }

    public function getContactEmailAttribute(): ?string
    {
        return $this->email;
    }

    public function scopeByCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }
}
