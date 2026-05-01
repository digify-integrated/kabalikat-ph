<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';

    protected $fillable = [
        'country_name',
        'last_log_by'
    ];

    public function states()
    {
        return $this->hasMany(State::class, 'country_id');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'country_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->country_name;
    }

    public function scopeAlphabetical($query)
    {
        return $query->orderBy('country_name');
    }
}
