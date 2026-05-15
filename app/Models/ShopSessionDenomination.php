<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSessionDenomination extends Model
{
    protected $table = 'shop_session_denomination';

    protected $fillable = [
        'shop_register_session_id',
        'count_type',
        'denomination_value',
        'quantity',
        'line_total',
        'last_log_by'
    ];

    public function shopRegisterSession(): BelongsTo
    {
        return $this->belongsTo(ShopRegisterSession::class, 'shop_register_session_id', 'id');
    }
}
