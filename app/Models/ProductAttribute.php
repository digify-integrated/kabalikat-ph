<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    protected $table = 'product_attribute';

    protected $fillable = [
        'product_id',
        'product_name',
        'attribute_id',
        'attribute_name',
        'last_log_by'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'id');
    }

    public function isVariantAttribute(): bool
    {
        return $this->attribute?->selection_type === Attribute::TYPE_MULTIPLE
            || $this->attribute?->selection_type === Attribute::TYPE_SINGLE;
    }

    public function getAttributeDisplayName(): string
    {
        return $this->attribute_name;
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForAttribute($query, int $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }
}