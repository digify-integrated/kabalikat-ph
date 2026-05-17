<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopRegisterSession extends Model
{
    protected $table = 'shop_register_session';

    protected $fillable = [
        'shop_register_id',
        'shop_register_name',
        'open_time',
        'open_amount',
        'open_remarks',
        'open_user_account_id',
        'open_user_name',
        'close_time',
        'close_amount',
        'close_remarks',
        'close_user_account_id',
        'close_user_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function shopRegisterDenomination(): HasMany
    {
        return $this->hasMany(ShopSessionDenomination::class, 'shop_register_session_id', 'id');
    }
}
