<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAddon extends Model
{
    protected $table = 'product_addon';

    protected $fillable = [
        'product_id',
        'product_name',
        'addon_product_id',
        'addon_product_name',
        'max_quantity',
        'last_log_by'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function addonProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'addon_product_id', 'id');
    }

    public function isUnlimited(): bool
    {
        return (float) $this->max_quantity <= 0;
    }

    public function allowsQuantity(int $quantity): bool
    {
        return $this->isUnlimited() || $quantity <= $this->max_quantity;
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForAddonProduct($query, int $addonProductId)
    {
        return $query->where('addon_product_id', $addonProductId);
    }
}
