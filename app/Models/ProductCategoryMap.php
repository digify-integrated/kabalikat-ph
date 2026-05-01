<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategoryMap extends Model
{
    protected $table = 'product_category_map';

    protected $fillable = [
        'product_id',
        'product_name',
        'product_category_id',
        'product_category_name',
        'last_log_by'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('product_category_id', $categoryId);
    }
}