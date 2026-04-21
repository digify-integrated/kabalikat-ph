<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
