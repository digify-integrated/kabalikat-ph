<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $table = 'product_category';

    protected $fillable = [
        'product_category_name',
        'last_log_by'
    ];

    public function productMaps(): HasMany
    {
        return $this->hasMany(ProductCategoryMap::class, 'product_category_id', 'id');
    }

    public function getProductCountAttribute(): int
    {
        return $this->productMaps()->count();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('product_category_name');
    }
}