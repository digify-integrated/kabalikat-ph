<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'currency';

    protected $fillable = [
        'currency_name',
        'symbol',
        'shorthand',
        'last_log_by'
    ];

    public function companies()
    {
        return $this->hasMany(Company::class, 'currency_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->currency_name} ({$this->shorthand})";
    }

    public function formatAmount(float $amount): string
    {
        return "{$this->symbol}{$amount}";
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('currency_name');
    }
}
