<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBOM extends Model
{
    protected $table = 'product_bom';

    protected $fillable = [
        'product_id',
        'product_name',
        'bom_product_id',
        'bom_product_name',
        'quantity',
        'stock_policy',
        'last_log_by'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function bomProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bom_product_id', 'id');
    }

    public function isStrictStock(): bool
    {
        return $this->stock_policy === 'Strict';
    }

    public function allowsNegativeStock(): bool
    {
        return $this->stock_policy === 'Allow Negative';
    }

    public function totalRequired(int $productionQuantity): float
    {
        return $this->quantity * $productionQuantity;
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeUsedInProduct($query, int $bomProductId)
    {
        return $query->where('bom_product_id', $bomProductId);
    }
}
