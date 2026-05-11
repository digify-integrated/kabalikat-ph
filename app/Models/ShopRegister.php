<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
