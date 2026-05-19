<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrder extends Model
{
    protected $table = 'shop_order';

    protected $fillable = [
        'order_number',
        'order_type',
        'shop_register_id',
        'shop_register_name',
        'shop_register_session_id',
        'floor_plan_id',
        'floor_plan_name',
        'floor_plan_table_id',
        'table_number',
        'customer_name',
        'order_status',
        'payment_status',
        'total_items',
        'total_quantity',
        'subtotal',
        'discount_total',
        'charge_total',
        'vatable_sales',
        'vat_exempt_sales',
        'zero_rated_sales',
        'vat_amount',
        'gross_total',
        'net_total',
        'paid_amount',
        'change_amount',
        'balance_due',
        'ordered_at',
        'completed_at',
        'cancelled_at',
        'created_by',
        'created_by_name',
        'completed_by',
        'completed_by_name',
        'cancelled_by',
        'cancelled_by_name',
        'last_log_by'
    ];

    public function shopRegister(): BelongsTo
    {
        return $this->belongsTo(ShopRegister::class, 'shop_register_id', 'id');
    }

    public function shopRegisterSession(): BelongsTo
    {
        return $this->belongsTo(ShopRegisterSession::class, 'shop_register_session_id', 'id');
    }

    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class, 'floor_plan_id', 'id');
    }

    public function floorPlanTable(): BelongsTo
    {
        return $this->belongsTo(FloorPlanTable::class, 'floor_plan_table_id', 'id');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_account_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by', 'id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'id');
    }
}
