<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    protected $table = 'unit_conversion';

    protected $fillable = [
        'from_unit_id',
        'from_unit_name',
        'to_unit_id',
        'to_unit_name',
        'conversion_factor',
        'last_log_by'
    ];

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    public function convert(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('from_unit_id', $unitId);
    }
}