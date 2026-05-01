<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'product';

    protected $fillable = [
        'product_name',
        'product_description',
        'product_image',
        'product_status',
        'sku',
        'barcode',
        'product_type',
        'base_price',
        'cost_price',
        'inventory_flow',
        'tax_classification',
        'track_inventory',
        'is_variant',
        'is_addon',
        'batch_tracking',
        'expiration_tracking',
        'parent_product_id',
        'parent_product_name',
        'variant_signature',
        'reorder_level',
        'base_unit_id',
        'base_unit_name',
        'base_unit_abbreviation',
        'last_log_by'
    ];

    protected $casts = [
        'base_price'      => 'float',
        'cost_price'      => 'float',
        'reorder_level'   => 'float',
    ];

    public const STATUS_ACTIVE   = 'Active';
    public const STATUS_INACTIVE = 'Inactive';

    public const TYPE_GOODS   = 'Goods';
    public const TYPE_SERVICE = 'Service';

    public const INVENTORY_FIFO   = 'FIFO';
    public const INVENTORY_FEFO   = 'FEFO';
    public const INVENTORY_LIFO   = 'LIFO';
    public const INVENTORY_MANUAL = 'Manual';

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function variants()
    {
        return $this->hasMany(Product::class, 'parent_product_id')
            ->orderBy('variant_signature');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    public function productBOMs(): HasMany
    {
        return $this->hasMany(ProductBOM::class, 'product_id');
    }

    public function productAddons(): HasMany
    {
        return $this->hasMany(ProductAddon::class, 'product_id');
    }

    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategoryMap::class, 'product_id');
    }

    public function isVariant(): bool
    {
        return $this->is_variant === 'Yes';
    }

    public function isParent(): bool
    {
        return is_null($this->parent_product_id);
    }

    public function tracksInventory(): bool
    {
        return $this->track_inventory === 'Yes';
    }

    public function usesBatch(): bool
    {
        return $this->batch_tracking === 'Yes';
    }

    public function usesExpiration(): bool
    {
        return $this->expiration_tracking === 'Yes';
    }

    public function scopeActive($query)
    {
        return $query->where('product_status', self::STATUS_ACTIVE);
    }

    public function scopeParentOnly($query)
    {
        return $query->whereNull('parent_product_id');
    }

    public function scopeVariantsOnly($query)
    {
        return $query->whereNotNull('parent_product_id');
    }
}
