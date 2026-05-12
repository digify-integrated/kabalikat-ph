<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopRegister extends Model
{
    protected $table = 'shop_register';

    protected $fillable = [
        'shop_register_name',
        'company_id',
        'company_name',
        'is_restaurant',
        'shop_register_status',
        'register_status',
        'archived_date',
        'last_log_by'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function shopRegisterAccess(): HasMany
    {
        return $this->hasMany(ShopRegisterAccess::class, 'shop_register_id', 'id');
    }

    public function shopRegisterCharge(): HasMany
    {
        return $this->hasMany(ShopRegisterCharge::class, 'shop_register_id', 'id');
    }

    public function shopRegisterDiscount(): HasMany
    {
        return $this->hasMany(ShopRegisterDiscount::class, 'shop_register_id', 'id');
    }

    public function shopRegisterFloorPlan(): HasMany
    {
        return $this->hasMany(ShopRegisterFloorPlan::class, 'shop_register_id', 'id');
    }

    public function shopRegisterPaymentMethod(): HasMany
    {
        return $this->hasMany(ShopRegisterPaymentMethod::class, 'shop_register_id', 'id');
    }

    public function shopRegisterWarehouse(): HasMany
    {
        return $this->hasMany(ShopRegisterWarehouse::class, 'shop_register_id', 'id');
    }
}
