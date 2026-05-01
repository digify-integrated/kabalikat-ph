<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitType extends Model
{
    protected $table = 'unit_type';

    protected $fillable = [
        'unit_type_name',
        'last_log_by'
    ];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'unit_type_id');
    }

    public function getLabelAttribute(): string
    {
        return $this->unit_type_name;
    }
}