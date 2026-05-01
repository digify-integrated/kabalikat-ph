<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $table = 'attribute';

    protected $fillable = [
        'attribute_name',
        'selection_type',
        'last_log_by'
    ];

    public const TYPE_SINGLE   = 'Single';
    public const TYPE_MULTIPLE = 'Multiple';

    public function values()
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id');
    }

    public function isSingle(): bool
    {
        return $this->selection_type === self::TYPE_SINGLE;
    }

    public function isMultiple(): bool
    {
        return $this->selection_type === self::TYPE_MULTIPLE;
    }

    public function scopeSingle($query)
    {
        return $query->where('selection_type', self::TYPE_SINGLE);
    }

    public function scopeMultiple($query)
    {
        return $query->where('selection_type', self::TYPE_MULTIPLE);
    }
}