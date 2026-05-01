<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    protected $table = 'attribute_value';

    protected $fillable = [
        'attribute_value',
        'attribute_id',
        'attribute_name',
        'last_log_by'
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'id');
    }

    public function belongsToAttribute(string $name): bool
    {
        return $this->attribute_name === $name;
    }

    public function scopeForAttribute($query, int $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }

    public function scopeByValue($query, string $value)
    {
        return $query->where('attribute_value', $value);
    }
}
