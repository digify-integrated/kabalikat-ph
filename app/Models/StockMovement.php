<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $table = 'stock_movement';

    protected $fillable = [
        'product_id',
        'product_name',
        'warehouse_id',
        'warehouse_name',
        'inventory_lot_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_number',
        'remarks',
        'last_log_by'
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public const TYPE_IN            = 'IN';
    public const TYPE_OUT           = 'OUT';
    public const TYPE_TRANSFER_IN   = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT  = 'TRANSFER_OUT';
    public const TYPE_ADJUSTMENT    = 'ADJUSTMENT';
    public const TYPE_SALE          = 'SALE';
    public const TYPE_RETURN        = 'RETURN';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function inventoryLot()
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }

    public function isIn(): bool
    {
        return in_array($this->movement_type, [
            self::TYPE_IN,
            self::TYPE_TRANSFER_IN,
            self::TYPE_RETURN,
        ]);
    }

    public function isOut(): bool
    {
        return in_array($this->movement_type, [
            self::TYPE_OUT,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_SALE,
        ]);
    }

    public function isAdjustment(): bool
    {
        return $this->movement_type === self::TYPE_ADJUSTMENT;
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeIncoming($query)
    {
        return $query->whereIn('movement_type', [
            self::TYPE_IN,
            self::TYPE_TRANSFER_IN,
            self::TYPE_RETURN,
        ]);
    }

    public function scopeOutgoing($query)
    {
        return $query->whereIn('movement_type', [
            self::TYPE_OUT,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_SALE,
        ]);
    }
}