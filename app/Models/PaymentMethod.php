<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_method';

    protected $fillable = [
        'payment_method_name',
        'last_log_by'
    ];
}
