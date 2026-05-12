<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopRegisterAccess extends Model
{
    protected $table = 'shop_register_access';

    protected $fillable = [
        'shop_register_id',
        'shop_register_name',
        'user_account_id',
        'user_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_account_id', 'id');
    }
}
