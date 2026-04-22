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
}
