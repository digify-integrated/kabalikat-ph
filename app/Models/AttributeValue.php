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

    /**
     * Pivot row belongs to an attribute.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'id');
    }
}
