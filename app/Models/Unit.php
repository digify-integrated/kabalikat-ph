<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $table = 'unit';

    protected $fillable = [
        'unit_name',
        'abbreviation',
        'unit_type_id',
        'unit_type_name',
        'last_log_by'
    ];

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id', 'id');
    }

    public function getLabelAttribute(): string
    {
        return "{$this->unit_name} ({$this->abbreviation})";
    }
}
