<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class InventoryLot extends Model
{
    protected $table = 'inventory_lot';

    protected $fillable = [
        'product_id',
        'product_name',
        'batch_number',
        'cost_per_unit',
        'expiration_date',
        'received_date',
        'last_log_by'
    ];

    protected $casts = [
        'cost_per_unit'  => 'float',
        'expiration_date'=> 'date',
        'received_date'  => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'inventory_lot_id');
    }

    public function hasBatch(): bool
    {
        return !is_null($this->batch_number);
    }

    public function hasExpiration(): bool
    {
        return !is_null($this->expiration_date);
    }

    public function isExpired(): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return Carbon::parse($this->expiration_date)->isPast();
    }
    
    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expiration_date &&
            now()->diffInDays($this->expiration_date, false) <= $days;
    }

    public function scopeFIFO($query)
    {
        return $query->orderBy('received_date');
    }

    public function scopeFEFO($query)
    {
        return $query->orderBy('expiration_date');
    }

    public function scopeNonExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiration_date')
              ->orWhere('expiration_date', '>=', now());
        });
    }
}
