<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $table = 'payment_method';

    protected $fillable = [
        'payment_method_name',
        'last_log_by'
    ];

    public function shopRegistePaymentMethod(): HasMany
    {
        return $this->hasMany(ShopRegisterPaymentMethod::class, 'payment_method_id', 'id');
    }
}
