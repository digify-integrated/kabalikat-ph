<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLevel extends Model
{
    protected $table = 'stock_level';

    protected $fillable = [
        'product_id',
        'product_name',
        'warehouse_id',
        'warehouse_name',
        'inventory_lot_id',
        'stock_status',
        'quantity',
        'last_log_by'
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public const STATUS_IN_STOCK  = 'In Stock';
    public const STATUS_LOW_STOCK = 'Low Stock';
    public const STATUS_OUT       = 'Out of Stock';

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

    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    public function isLowStock(): bool
    {
        if (!$this->product) return false;

        return $this->quantity > 0 &&
               $this->quantity <= $this->product->reorder_level;
    }

    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    public function recalculateStatus(): void
    {
        if ($this->isOutOfStock()) {
            $this->stock_status = self::STATUS_OUT;
        } elseif ($this->isLowStock()) {
            $this->stock_status = self::STATUS_LOW_STOCK;
        } else {
            $this->stock_status = self::STATUS_IN_STOCK;
        }
    }

    public function addStock(float $qty): void
    {
        $this->quantity += $qty;
        $this->recalculateStatus();
        $this->save();
    }

    public function deductStock(float $qty): void
    {
        $this->quantity -= $qty;
        $this->recalculateStatus();
        $this->save();
    }

    public function scopeAvailable($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
