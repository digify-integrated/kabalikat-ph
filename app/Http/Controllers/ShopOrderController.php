<?php

namespace App\Http\Controllers;

use App\Models\ChargeType;
use App\Models\DiscountType;
use App\Models\FloorPlanTable;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBOM;
use App\Models\ShopOrder;
use App\Models\ShopOrderAppliedCharge;
use App\Models\ShopOrderAppliedDiscount;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderPayment;
use App\Models\ShopRegister;
use App\Models\ShopRegisterSession;
use App\Models\ShopRegisterWarehouse;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopOrderController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_register_id' => [
                'required',
                'integer',
                Rule::exists('shop_register', 'id'),
            ],

            'modal_product_id' => [
                'required',
                'integer',
                Rule::exists('product', 'id'),
            ],

            'order_qty_input' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'order_note' => [
                'nullable',
                'string',
            ],

            'shop_order_id' => [
                'nullable',
                'integer',
            ],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {

            $validated = $validator->validated();

            /*
            |--------------------------------------------------------------------------
            | REGISTER
            |--------------------------------------------------------------------------
            */

            $shopRegister = ShopRegister::find(
                $validated['shop_register_id']
            );

            if (!$shopRegister) {

                return response()->json([
                    'success' => false,
                    'message' => 'Shop register not found.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | OPEN SESSION
            |--------------------------------------------------------------------------
            */

            $shopRegisterSession = ShopRegisterSession::query()

                ->where('shop_register_id', $shopRegister->id)

                ->whereNull('close_time')

                ->latest('id')

                ->first();

            if (!$shopRegisterSession) {

                return response()->json([
                    'success' => false,
                    'message' => 'No open register session found.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | PRODUCT
            |--------------------------------------------------------------------------
            */

            $product = Product::find(
                $validated['modal_product_id']
            );

            if (!$product) {

                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | COMPUTATION
            |--------------------------------------------------------------------------
            */

            $quantity = round(
                (float) $validated['order_qty_input'],
                2
            );

            $originalUnitPrice = round(
                (float) $product->base_price,
                2
            );

            $unitPrice = $originalUnitPrice;

            $lineSubtotal = round(
                $quantity * $unitPrice,
                2
            );

            $vatableSales = 0;
            $vatExemptSales = 0;
            $zeroRatedSales = 0;
            $vatAmount = 0;

            if ($product->tax_classification === 'Vatable') {

                $vatableSales = round(
                    $lineSubtotal / 1.12,
                    2
                );

                $vatAmount = round(
                    $lineSubtotal - $vatableSales,
                    2
                );
            }

            elseif ($product->tax_classification === 'VAT Exempt') {

                $vatExemptSales = $lineSubtotal;
            }

            elseif ($product->tax_classification === 'Zero Rated') {

                $zeroRatedSales = $lineSubtotal;
            }

            /*
            |--------------------------------------------------------------------------
            | EXISTING ORDER
            |--------------------------------------------------------------------------
            */

            $shopOrder = null;

            if (!empty($validated['shop_order_id'])) {

                $shopOrder = ShopOrder::query()

                    ->whereKey($validated['shop_order_id'])

                    ->where('payment_status', 'Unpaid')

                    ->whereNotIn('order_status', [
                        'Completed',
                        'Cancelled',
                        'Voided',
                    ])

                    ->first();
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE ORDER
            |--------------------------------------------------------------------------
            */

            $isNewOrder = false;

            if (!$shopOrder) {

                $isNewOrder = true;

                $orderNumber =
                    'SO-' .
                    now()->format('YmdHis') .
                    '-' .
                    Str::upper(Str::random(4));

                $shopOrder = ShopOrder::create([

                    'order_number' => $orderNumber,

                    'shop_register_id' =>
                        $shopRegister->id,

                    'shop_register_name' =>
                        $shopRegister->shop_register_name,

                    'shop_register_session_id' =>
                        $shopRegisterSession->id,

                    'order_type' => 'Walk-in',

                    'ordered_at' => now(),

                    'created_by' => Auth::id(),

                    'created_by_name' => Auth::user()->name,

                    'last_log_by' => Auth::id(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | EXISTING ITEM
            |--------------------------------------------------------------------------
            */

            $existingItem = ShopOrderItem::query()

                ->where('shop_order_id', $shopOrder->id)

                ->where('product_id', $product->id)

                ->where(
                    'order_note',
                    $validated['order_note'] ?? null
                )

                ->where('item_status', '!=', 'Cancelled')

                ->first();

            /*
            |--------------------------------------------------------------------------
            | UPDATE ITEM
            |--------------------------------------------------------------------------
            */

            if ($existingItem) {

                $existingItem->quantity = round(
                    $existingItem->quantity + $quantity,
                    2
                );

                $existingItem->line_subtotal = round(
                    $existingItem->quantity
                    * $existingItem->unit_price,
                    2
                );

                $existingItem->vatable_sales = 0;
                $existingItem->vat_exempt_sales = 0;
                $existingItem->zero_rated_sales = 0;
                $existingItem->vat_amount = 0;

                if (
                    $existingItem->tax_classification === 'Vatable'
                ) {

                    $existingItem->vatable_sales = round(
                        $existingItem->line_subtotal / 1.12,
                        2
                    );

                    $existingItem->vat_amount = round(
                        $existingItem->line_subtotal
                        - $existingItem->vatable_sales,
                        2
                    );
                }

                elseif (
                    $existingItem->tax_classification === 'VAT Exempt'
                ) {

                    $existingItem->vat_exempt_sales =
                        $existingItem->line_subtotal;
                }

                elseif (
                    $existingItem->tax_classification === 'Zero Rated'
                ) {

                    $existingItem->zero_rated_sales =
                        $existingItem->line_subtotal;
                }

                $existingItem->line_total =
                    $existingItem->line_subtotal;

                $existingItem->save();
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE ITEM
            |--------------------------------------------------------------------------
            */

            else {

                ShopOrderItem::create([

                    'shop_order_id' =>
                        $shopOrder->id,

                    'product_id' =>
                        $product->id,

                    'product_name' =>
                        $product->product_name,

                    'sku' =>
                        $product->sku,

                    'barcode' =>
                        $product->barcode,

                    'product_type' =>
                        $product->product_type,

                    'quantity' =>
                        $quantity,

                    'original_unit_price' =>
                        $originalUnitPrice,

                    'unit_price' =>
                        $unitPrice,

                    'line_subtotal' =>
                        $lineSubtotal,

                    'line_total' =>
                        $lineSubtotal,

                    'tax_classification' =>
                        $product->tax_classification,

                    'vatable_sales' =>
                        $vatableSales,

                    'vat_exempt_sales' =>
                        $vatExemptSales,

                    'zero_rated_sales' =>
                        $zeroRatedSales,

                    'vat_amount' =>
                        $vatAmount,

                    'order_note' =>
                        $validated['order_note'] ?? null,

                    'queued_at' => now(),

                    'last_log_by' =>
                        Auth::id(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | AUTO APPLY DISCOUNTS
            |--------------------------------------------------------------------------
            */

            if ($isNewOrder) {

                $discounts = DB::table('shop_register_discount')

                    ->join(
                        'discount_type',
                        'discount_type.id',
                        '=',
                        'shop_register_discount.discount_type_id'
                    )

                    ->where(
                        'shop_register_discount.shop_register_id',
                        $shopRegister->id
                    )

                    ->where(
                        'shop_register_discount.automatic_application',
                        'Yes'
                    )

                    ->get();

                foreach ($discounts as $discount) {

                    ShopOrderAppliedDiscount::create([

                        'shop_order_id' =>
                            $shopOrder->id,

                        'discount_type_id' =>
                            $discount->discount_type_id,

                        'discount_type_name' =>
                            $discount->discount_type_name,

                        'value_type' =>
                            $discount->value_type,

                        'discount_value' =>
                            $discount->discount_value,

                        'application_order' =>
                            $discount->application_order,

                        'is_vat_exempt' =>
                            $discount->is_vat_exempt,

                        'last_log_by' =>
                            Auth::id(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | AUTO APPLY CHARGES
                |--------------------------------------------------------------------------
                */

                $charges = DB::table('shop_register_charge')

                    ->join(
                        'charge_type',
                        'charge_type.id',
                        '=',
                        'shop_register_charge.charge_type_id'
                    )

                    ->where(
                        'shop_register_charge.shop_register_id',
                        $shopRegister->id
                    )

                    ->where(
                        'shop_register_charge.automatic_application',
                        'Yes'
                    )

                    ->get();

                foreach ($charges as $charge) {

                    ShopOrderAppliedCharge::create([

                        'shop_order_id' =>
                            $shopOrder->id,

                        'charge_type_id' =>
                            $charge->charge_type_id,

                        'charge_type_name' =>
                            $charge->charge_type_name,

                        'value_type' =>
                            $charge->value_type,

                        'charge_value' =>
                            $charge->charge_value,

                        'application_order' =>
                            $charge->application_order,

                        'tax_type' =>
                            $charge->tax_type,

                        'last_log_by' =>
                            Auth::id(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | RECOMPUTE ITEMS
            |--------------------------------------------------------------------------
            */

            $totals = ShopOrderItem::query()

                ->where('shop_order_id', $shopOrder->id)

                ->where('item_status', '!=', 'Cancelled')

                ->selectRaw('
                    COUNT(*) as total_items,
                    SUM(quantity) as total_quantity,
                    SUM(line_subtotal) as subtotal,
                    SUM(vatable_sales) as vatable_sales,
                    SUM(vat_exempt_sales) as vat_exempt_sales,
                    SUM(zero_rated_sales) as zero_rated_sales,
                    SUM(vat_amount) as vat_amount,
                    SUM(line_total) as gross_total
                ')

                ->first();

            $subtotal = round($totals->subtotal ?? 0, 2);

            /*
            |--------------------------------------------------------------------------
            | DISCOUNT TOTAL
            |--------------------------------------------------------------------------
            */

            $discountTotal = 0;

            $discounts = ShopOrderAppliedDiscount::query()

                ->where('shop_order_id', $shopOrder->id)

                ->get();

            foreach ($discounts as $discount) {

                /*
                |--------------------------------------------------------------------------
                | BASE AMOUNT
                |--------------------------------------------------------------------------
                */

                $baseAmount = $subtotal;

                /*
                |--------------------------------------------------------------------------
                | COMPUTE DISCOUNT
                |--------------------------------------------------------------------------
                */

                $discountAmount = 0;

                if ($discount->value_type === 'Percentage') {

                    $discountAmount = round(
                        ($baseAmount * $discount->discount_value) / 100,
                        2
                    );

                    $discount->discount_rate =
                        $discount->discount_value;
                }

                else {

                    $discountAmount = round(
                        $discount->discount_value,
                        2
                    );

                    $discount->discount_rate = 0;
                }

                /*
                |--------------------------------------------------------------------------
                | VAT EXEMPT
                |--------------------------------------------------------------------------
                */

                if ($discount->is_vat_exempt === 'Yes') {

                    $discount->vat_exempt_amount =
                        $discountAmount;
                }

                /*
                |--------------------------------------------------------------------------
                | SAVE COMPUTED VALUES
                |--------------------------------------------------------------------------
                */

                $discount->discount_amount =
                    $discountAmount;

                $discount->save();

                /*
                |--------------------------------------------------------------------------
                | RUNNING TOTAL
                |--------------------------------------------------------------------------
                */

                $discountTotal += $discountAmount;
            }


            /*
            |--------------------------------------------------------------------------
            | CHARGE TOTAL
            |--------------------------------------------------------------------------
            */

            $chargeTotal = 0;

            $charges = ShopOrderAppliedCharge::query()

                ->where('shop_order_id', $shopOrder->id)

                ->get();

            foreach ($charges as $charge) {

                /*
                |--------------------------------------------------------------------------
                | BASE AMOUNT
                |--------------------------------------------------------------------------
                */

                $baseAmount = $subtotal;

                /*
                |--------------------------------------------------------------------------
                | COMPUTE CHARGE
                |--------------------------------------------------------------------------
                */

                $chargeAmount = 0;

                if ($charge->value_type === 'Percentage') {

                    $chargeAmount = round(
                        ($baseAmount * $charge->charge_value) / 100,
                        2
                    );

                    $charge->charge_rate =
                        $charge->charge_value;
                }

                else {

                    $chargeAmount = round(
                        $charge->charge_value,
                        2
                    );

                    $charge->charge_rate = 0;
                }

                /*
                |--------------------------------------------------------------------------
                | VATABLE CHARGE
                |--------------------------------------------------------------------------
                */

                if ($charge->tax_type === 'Vatable') {

                    $charge->vatable_amount = round(
                        $chargeAmount / 1.12,
                        2
                    );

                    $charge->vat_amount = round(
                        $chargeAmount -
                        $charge->vatable_amount,
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | NON VATABLE
                |--------------------------------------------------------------------------
                */

                else {

                    $charge->vatable_amount = 0;

                    $charge->vat_amount = 0;
                }

                /*
                |--------------------------------------------------------------------------
                | SAVE COMPUTED VALUES
                |--------------------------------------------------------------------------
                */

                $charge->charge_amount =
                    $chargeAmount;

                $charge->save();

                /*
                |--------------------------------------------------------------------------
                | RUNNING TOTAL
                |--------------------------------------------------------------------------
                */

                $chargeTotal += $chargeAmount;
            }

            /*
            |--------------------------------------------------------------------------
            | ROUND TOTALS
            |--------------------------------------------------------------------------
            */

            $discountTotal = round($discountTotal, 2);

            $chargeTotal = round($chargeTotal, 2);

            /*
            |--------------------------------------------------------------------------
            | FINAL TOTALS
            |--------------------------------------------------------------------------
            */

            $grossTotal = round(
                $subtotal + $chargeTotal,
                2
            );

            $netTotal = round(
                $grossTotal - $discountTotal,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE ORDER
            |--------------------------------------------------------------------------
            */

            $shopOrder->update([

                'total_items' =>
                    (int) ($totals->total_items ?? 0),

                'total_quantity' =>
                    round($totals->total_quantity ?? 0, 2),

                'subtotal' =>
                    $subtotal,

                'discount_total' =>
                    $discountTotal,

                'charge_total' =>
                    $chargeTotal,

                'vatable_sales' =>
                    round($totals->vatable_sales ?? 0, 2),

                'vat_exempt_sales' =>
                    round($totals->vat_exempt_sales ?? 0, 2),

                'zero_rated_sales' =>
                    round($totals->zero_rated_sales ?? 0, 2),

                'vat_amount' =>
                    round($totals->vat_amount ?? 0, 2),

                'gross_total' =>
                    $grossTotal,

                'net_total' =>
                    $netTotal,

                'balance_due' =>
                    $netTotal,
            ]);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Order saved successfully.',

                'shop_order_id' => $shopOrder->id,

                'order_number' => $shopOrder->order_number,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),
            ]);
        }
    }

    public function saveOrderType(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],

            'shop_register_id' => [
                'required',
                'integer',
                Rule::exists('shop_register', 'id'),
            ],

            'order_type' => [
                'required',
                'string',
                Rule::in([
                    'Walk-in',
                    'Dine-in',
                    'Take-out',
                    'Delivery',
                ]),
            ],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder = ShopOrder::query()

            ->findOrFail(
                $validated['shop_order_id']
            );

        /*
        |--------------------------------------------------------------------------
        | PAYLOAD
        |--------------------------------------------------------------------------
        */

        $payload = [

            'order_type' =>
                $validated['order_type'],

            'last_log_by' =>
                Auth::id(),
        ];

        /*
        |--------------------------------------------------------------------------
        | RELEASE TABLE
        |--------------------------------------------------------------------------
        |
        | If no longer dine-in:
        | - remove floor plan
        | - remove table assignment
        |
        */

        if ($validated['order_type'] !== 'Dine-in') {

            $payload['floor_plan_id'] = null;

            $payload['floor_plan_name'] = null;

            $payload['floor_plan_table_id'] = null;

            $payload['table_number'] = null;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder->update($payload);

        return response()->json([

            'success' => true,

            'message' =>
                'Order type updated successfully.',

            'order_type' =>
                $shopOrder->order_type,

            'table_removed' =>
                $validated['order_type'] !== 'Dine-in',
        ]);
    }

    public function saveItemQuantity(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],

            'shop_order_item_id' => [
                'required',
                'integer',
                Rule::exists('shop_order_item', 'id'),
            ],

            'action' => [
                'required',
                Rule::in([
                    'increase',
                    'decrease',
                    'delete',
                ]),
            ],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {

            $validated = $validator->validated();

            /*
            |--------------------------------------------------------------------------
            | ORDER ITEM
            |--------------------------------------------------------------------------
            */

            $item = ShopOrderItem::query()

                ->whereKey(
                    $validated['shop_order_item_id']
                )

                ->first();

            if (!$item) {

                return response()->json([
                    'success' => false,
                    'message' => 'Order item not found.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | ACTIONS
            |--------------------------------------------------------------------------
            */

            if ($validated['action'] === 'increase') {

                $item->quantity += 1;
            }

            elseif ($validated['action'] === 'decrease') {

                $item->quantity -= 1;
            }

            /*
            |--------------------------------------------------------------------------
            | DELETE
            |--------------------------------------------------------------------------
            */

            if (
                $validated['action'] === 'delete'
                ||
                $item->quantity <= 0
            ) {

                $item->delete();
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE ITEM
            |--------------------------------------------------------------------------
            */

            else {

                $item->line_subtotal = round(
                    $item->quantity
                    * $item->unit_price,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | RESET TAX
                |--------------------------------------------------------------------------
                */

                $item->vatable_sales = 0;

                $item->vat_exempt_sales = 0;

                $item->zero_rated_sales = 0;

                $item->vat_amount = 0;

                /*
                |--------------------------------------------------------------------------
                | VATABLE
                |--------------------------------------------------------------------------
                */

                if (
                    $item->tax_classification
                    === 'Vatable'
                ) {

                    $item->vatable_sales = round(
                        $item->line_subtotal / 1.12,
                        2
                    );

                    $item->vat_amount = round(
                        $item->line_subtotal
                        - $item->vatable_sales,
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | VAT EXEMPT
                |--------------------------------------------------------------------------
                */

                elseif (
                    $item->tax_classification
                    === 'VAT Exempt'
                ) {

                    $item->vat_exempt_sales =
                        $item->line_subtotal;
                }

                /*
                |--------------------------------------------------------------------------
                | ZERO RATED
                |--------------------------------------------------------------------------
                */

                elseif (
                    $item->tax_classification
                    === 'Zero Rated'
                ) {

                    $item->zero_rated_sales =
                        $item->line_subtotal;
                }

                $item->line_total =
                    $item->line_subtotal;

                $item->save();
            }

            /*
            |--------------------------------------------------------------------------
            | RECOMPUTE ORDER
            |--------------------------------------------------------------------------
            */

            $this->recomputeShopOrder(
                $validated['shop_order_id']
            );

            /*
            |--------------------------------------------------------------------------
            | RETURN UPDATED ORDER
            |--------------------------------------------------------------------------
            */

            $order = $this->buildOrderPayload(
                $validated['shop_order_id']
            );

            DB::commit();

            return response()->json([

                'success' => true,

                'order' => $order,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),
            ]);
        }
    }

    public function saveTable(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],

            'floor_plan_table_id' => [
                'required',
                'integer',
                Rule::exists('floor_plan_table', 'id'),
            ],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $shopOrder = ShopOrder::find(
            $request->shop_order_id
        );

        $table = FloorPlanTable::find(
            $request->floor_plan_table_id
        );

        /*
        |--------------------------------------------------------------------------
        | OCCUPIED CHECK
        |--------------------------------------------------------------------------
        */

        $occupied = ShopOrder::query()

            ->where(
                'floor_plan_table_id',
                $table->id
            )

            ->where('id', '!=', $shopOrder->id)

            ->whereNotIn('order_status', [
                'Completed',
                'Cancelled',
                'Voided',
            ])

            ->exists();

        if ($occupied) {

            return response()->json([
                'success' => false,
                'message' => 'Table is occupied.',
            ]);
        }

        $shopOrder->update([

            'order_type' => 'Dine-in',

            'floor_plan_id' =>
                $table->floor_plan_id,

            'floor_plan_name' =>
                $table->floor_plan_name,

            'floor_plan_table_id' =>
                $table->id,

            'table_number' =>
                $table->table_number,
        ]);

        return response()->json([

            'success' => true,

            'table_number' =>
                $table->table_number,

            'floor_plan_name' =>
                $table->floor_plan_name,
        ]);
    }

    public function saveDiscount(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],

            'discount_type_id' => [
                'required',
                'integer',
                Rule::exists('discount_type', 'id'),
            ],

            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'reference_number' => ['nullable', 'string'],
            'reference_name' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {

            $validated = $validator->validated();

            /*
            |--------------------------------------------------------------------------
            | ORDER
            |--------------------------------------------------------------------------
            */

            $shopOrder = ShopOrder::query()

                ->findOrFail(
                    $validated['shop_order_id']
                );

            /*
            |--------------------------------------------------------------------------
            | DISCOUNT TYPE
            |--------------------------------------------------------------------------
            */

            $discountType = DiscountType::query()

                ->findOrFail(
                    $validated['discount_type_id']
                );

            /*
            |--------------------------------------------------------------------------
            | PREVENT DUPLICATES
            |--------------------------------------------------------------------------
            */

            $alreadyApplied =
                ShopOrderAppliedDiscount::query()

                ->where(
                    'shop_order_id',
                    $shopOrder->id
                )

                ->where(
                    'discount_type_id',
                    $discountType->id
                )

                ->exists();

            if ($alreadyApplied) {

                return response()->json([
                    'success' => false,
                    'message' => 'Discount already applied.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | DISCOUNT VALUE
            |--------------------------------------------------------------------------
            */

            $discountValue =
                $discountType->discount_value;

            /*
            |--------------------------------------------------------------------------
            | VARIABLE DISCOUNT
            |--------------------------------------------------------------------------
            */

            if (
                $discountType->is_variable === 'Yes'
            ) {

                if (
                    !isset($validated['discount_value'])
                ) {

                    return response()->json([
                        'success' => false,
                        'message' =>
                            'Discount value is required.',
                    ]);
                }

                $discountValue = round(
                    (float) $validated['discount_value'],
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE
            |--------------------------------------------------------------------------
            */

            ShopOrderAppliedDiscount::create([

                'shop_order_id' =>
                    $shopOrder->id,

                'discount_type_id' =>
                    $discountType->id,

                'discount_type_name' =>
                    $discountType->discount_type_name,

                'value_type' =>
                    $discountType->value_type,

                'discount_value' =>
                    $discountValue,

                'application_order' =>
                    $discountType->application_order,

                'is_vat_exempt' =>
                    $discountType->is_vat_exempt,
                'reference_number' => $validated['reference_number'] ?? null,
                'reference_name' => $validated['reference_name'] ?? null,
                'remarks' => $validated['remarks'] ?? null,

                // AUDIT
                'applied_by' => Auth::id(),
                'applied_by_name' => Auth::user()->name,

                'last_log_by' =>
                    Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | RECOMPUTE
            |--------------------------------------------------------------------------
            */

            $this->recomputeShopOrder(
                $shopOrder->id
            );

            /*
            |--------------------------------------------------------------------------
            | REFRESH DISCOUNTS
            |--------------------------------------------------------------------------
            */

            $appliedDiscounts =
                ShopOrderAppliedDiscount::query()

                ->where(
                    'shop_order_id',
                    $shopOrder->id
                )

                ->get();

            $appliedIds =
                $appliedDiscounts
                    ->pluck('discount_type_id');

            $availableDiscounts = DB::table(
                'shop_register_discount'
            )

            ->join(
                'discount_type',
                'discount_type.id',
                '=',
                'shop_register_discount.discount_type_id'
            )

            ->where(
                'shop_register_discount.shop_register_id',
                $shopOrder->shop_register_id
            )

            ->whereNotIn(
                'discount_type.id',
                $appliedIds
            )

            ->select(
                'discount_type.*'
            )

            ->get();

            DB::commit();

            return response()->json([

                'success' => true,

                'message' =>
                    'Discount applied successfully.',

                'order' =>
                    $this->buildOrderPayload(
                        $shopOrder->id
                    ),

                'available_discounts' =>
                    $availableDiscounts,

                'applied_discounts' =>
                    $appliedDiscounts,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),
            ]);
        }
    }

    public function saveCharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],
            'charge_type_id' => [
                'required',
                'integer',
                Rule::exists('charge_type', 'id'),
            ],
            'charge_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {

            $validated = $validator->validated();

            /*
            |--------------------------------------------------------------------------
            | ORDER
            |--------------------------------------------------------------------------
            */
            $shopOrder = ShopOrder::query()
                ->findOrFail($validated['shop_order_id']);

            /*
            |--------------------------------------------------------------------------
            | CHARGE TYPE
            |--------------------------------------------------------------------------
            */
            $chargeType = ChargeType::query()
                ->findOrFail($validated['charge_type_id']);

            /*
            |--------------------------------------------------------------------------
            | DUPLICATE CHECK
            |--------------------------------------------------------------------------
            */
            $existingCharge = ShopOrderAppliedCharge::query()
                ->where('shop_order_id', $shopOrder->id)
                ->where('charge_type_id', $chargeType->id)
                ->exists();

            if ($existingCharge) {
                return response()->json([
                    'success' => false,
                    'message' => 'Charge already applied.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | VALUE
            |--------------------------------------------------------------------------
            */
            $chargeValue = $chargeType->charge_value;

            if ($chargeType->is_variable === 'Yes') {
                $chargeValue = round((float) ($validated['charge_value'] ?? 0), 2);
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE
            |--------------------------------------------------------------------------
            */
            ShopOrderAppliedCharge::create([
                'shop_order_id' => $shopOrder->id,
                'charge_type_id' => $chargeType->id,
                'charge_type_name' => $chargeType->charge_type_name,
                'value_type' => $chargeType->value_type,
                'charge_value' => $chargeValue,
                'application_order' => $chargeType->application_order,
                'tax_type' => $chargeType->tax_type,
                'remarks' => $validated['remarks'] ?? null,
                'applied_by' => Auth::id(),
                'applied_by_name' => Auth::user()->name,
                'last_log_by' => Auth::id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | RECOMPUTE
            |--------------------------------------------------------------------------
            */
            $this->recomputeShopOrder($shopOrder->id);

            /*
            |--------------------------------------------------------------------------
            | REFRESH DATA (IMPORTANT)
            |--------------------------------------------------------------------------
            */

            $appliedCharges = ShopOrderAppliedCharge::query()
                ->where('shop_order_id', $shopOrder->id)
                ->get();

            $appliedChargeIds = $appliedCharges->pluck('charge_type_id');

            $availableCharges = DB::table('shop_register_charge')
                ->join('charge_type', 'charge_type.id', '=', 'shop_register_charge.charge_type_id')
                ->where('shop_register_charge.shop_register_id', $shopOrder->shop_register_id)
                ->whereNotIn('charge_type.id', $appliedChargeIds)
                ->select('charge_type.*')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | ALSO KEEP DISCOUNTS (IMPORTANT FOR UI SYNC)
            |--------------------------------------------------------------------------
            */

            $appliedDiscounts = ShopOrderAppliedDiscount::query()
                ->where('shop_order_id', $shopOrder->id)
                ->get();

            $appliedDiscountIds = $appliedDiscounts->pluck('discount_type_id');

            $availableDiscounts = DB::table('shop_register_discount')
                ->join('discount_type', 'discount_type.id', '=', 'shop_register_discount.discount_type_id')
                ->where('shop_register_discount.shop_register_id', $shopOrder->shop_register_id)
                ->whereNotIn('discount_type.id', $appliedDiscountIds)
                ->select('discount_type.*')
                ->get();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Charge applied successfully.',

                'order' => $this->buildOrderPayload($shopOrder->id),

                'available_charges' => $availableCharges,
                'applied_charges' => $appliedCharges,

                'available_discounts' => $availableDiscounts,
                'applied_discounts' => $appliedDiscounts,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function savePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],

            'payments' => [
                'required',
                'array',
                'min:1',
            ],

            'payments.*.payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_method', 'id'),
            ],

            'payments.*.payment_amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payments.*.tendered_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payments.*.reference_number' => ['nullable', 'string', 'max:255'],
            'payments.*.reference_name' => ['nullable', 'string', 'max:255'],
            'payments.*.remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {

            $shopOrder = ShopOrder::query()
                ->with([
                    'items',
                    'shopRegister',
                    'shopRegister.warehouses', // IMPORTANT FIX
                ])
                ->lockForUpdate()
                ->find($validated['shop_order_id']);

            if (!$shopOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ]);
            }

            if ($shopOrder->payment_status === 'Paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already paid.',
                ]);
            }

            [$totalPayment, $totalTendered] =
                $this->calculatePaymentTotals($validated['payments']);

            if (round($totalPayment, 2) < round($shopOrder->balance_due, 2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount is insufficient.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE PAYMENTS
            |--------------------------------------------------------------------------
            */

            foreach ($validated['payments'] as $payment) {

                $paymentMethod = PaymentMethod::find($payment['payment_method_id']);

                if (!$paymentMethod) {
                    continue;
                }

                $amount = (float) $payment['payment_amount'];

                $tendered = (float) ($payment['tendered_amount'] ?? $amount);

                ShopOrderPayment::create([
                    'shop_order_id' => $shopOrder->id,
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method_name' => $paymentMethod->payment_method_name,
                    'payment_amount' => $amount,
                    'tendered_amount' => $tendered,
                    'change_amount' => max(0, $tendered - $amount),
                    'reference_number' => $payment['reference_number'] ?? null,
                    'reference_name' => $payment['reference_name'] ?? null,
                    'remarks' => $payment['remarks'] ?? null,
                    'payment_status' => 'Paid',
                    'paid_at' => now(),
                    'last_log_by' => Auth::id(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE ORDER
            |--------------------------------------------------------------------------
            */

            $shopOrder->update([
                'payment_status' => 'Paid',
                'paid_amount' => $totalPayment,
                'change_amount' => max(0, $totalTendered - $shopOrder->net_total),
                'balance_due' => 0,
                'completed_at' => now(),
                'completed_by' => Auth::id(),
                'completed_by_name' => Auth::user()?->name,
            ]);

            /*
            |--------------------------------------------------------------------------
            | INVENTORY DEDUCTION
            |--------------------------------------------------------------------------
            */

            if ($shopOrder->shopRegister?->is_restaurant === 'No') {

                foreach ($shopOrder->items as $item) {

                    $product = Product::find($item->product_id);

                    if (!$product || $product->track_inventory === 'No') {
                        continue;
                    }

                    $bomItems = ProductBom::where('product_id', $product->id)->get();

                    if ($bomItems->isEmpty()) {

                        $this->deductInventory(
                            product: $product,
                            quantity: $item->quantity,
                            referenceNumber: $shopOrder->order_number,
                            warehouseIds: $shopOrder->shopRegister->warehouses->pluck('warehouse_id')->toArray()
                        );

                        continue;
                    }

                    foreach ($bomItems as $bom) {

                        $bomProduct = Product::find($bom->bom_product_id);

                        if (!$bomProduct || $bomProduct->track_inventory === 'No') {
                            continue;
                        }

                        $this->deductInventory(
                            product: $bomProduct,
                            quantity: $bom->quantity * $item->quantity,
                            referenceNumber: $shopOrder->order_number,
                            warehouseIds: $shopOrder->shopRegister->warehouses->pluck('warehouse_id')->toArray()
                        );
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment successfully completed.',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // IMPORTANT DEBUG FIX
            ]);
        }
    }

    private function calculatePaymentTotals(array $payments): array
    {
        $totalPayment = 0;
        $totalTendered = 0;

        foreach ($payments as $payment) {

            $amount = (float) ($payment['payment_amount'] ?? 0);

            $tendered = (float) (
                $payment['tendered_amount']
                ?? $amount
            );

            $totalPayment += $amount;
            $totalTendered += $tendered;
        }

        return [$totalPayment, $totalTendered];
    }

    private function deductInventory(
        Product $product,
        float $quantity,
        string $referenceNumber,
        array $warehouseIds
    ): void {

        if (empty($warehouseIds)) {
            throw new \Exception("No warehouse assigned to register.");
        }

        $stocks = StockLevel::query()
            ->with('inventoryLot')
            ->where('product_id', $product->id)
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->get();

        if ($stocks->isEmpty()) {
            throw new \Exception("No stock found for: {$product->product_name}");
        }

        $remaining = $quantity;

        foreach ($stocks as $stock) {

            if ($remaining <= 0) {
                break;
            }

            $deduct = min($remaining, $stock->quantity);

            if ($deduct <= 0) {
                continue;
            }

            $stock->decrement('quantity', $deduct);

            $stock->refresh();

            $stock->update([
                'stock_status' => match (true) {
                    $stock->quantity <= 0 => 'Out of Stock',
                    $stock->quantity <= $product->reorder_level => 'Low Stock',
                    default => 'In Stock',
                },
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse_name,
                'inventory_lot_id' => $stock->inventory_lot_id,
                'movement_type' => 'SALE',
                'quantity' => $deduct,
                'reference_type' => 'Shop Order',
                'reference_number' => $referenceNumber,
                'remarks' => 'POS payment deduction',
                'last_log_by' => Auth::id(),
            ]);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock for {$product->product_name}");
        }
    }

    public function deleteDiscount(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'applied_discount_id' => [
                'required',
                'integer',
                Rule::exists(
                    'shop_order_applied_discount',
                    'id'
                ),
            ],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {

            $validated = $validator->validated();

            /*
            |--------------------------------------------------------------------------
            | APPLIED DISCOUNT
            |--------------------------------------------------------------------------
            */

            $appliedDiscount =
                ShopOrderAppliedDiscount::query()

                ->findOrFail(
                    $validated['applied_discount_id']
                );

            $shopOrderId =
                $appliedDiscount->shop_order_id;

            /*
            |--------------------------------------------------------------------------
            | DELETE
            |--------------------------------------------------------------------------
            */

            $appliedDiscount->delete();

            /*
            |--------------------------------------------------------------------------
            | RECOMPUTE
            |--------------------------------------------------------------------------
            */

            $this->recomputeShopOrder(
                $shopOrderId
            );

            /*
            |--------------------------------------------------------------------------
            | REFRESH
            |--------------------------------------------------------------------------
            */

            $shopOrder =
                ShopOrder::query()
                    ->findOrFail($shopOrderId);

            $appliedDiscounts =
                ShopOrderAppliedDiscount::query()

                ->where(
                    'shop_order_id',
                    $shopOrderId
                )

                ->get();

            $appliedIds =
                $appliedDiscounts
                    ->pluck('discount_type_id');

            $availableDiscounts = DB::table(
                'shop_register_discount'
            )

            ->join(
                'discount_type',
                'discount_type.id',
                '=',
                'shop_register_discount.discount_type_id'
            )

            ->where(
                'shop_register_discount.shop_register_id',
                $shopOrder->shop_register_id
            )

            ->whereNotIn(
                'discount_type.id',
                $appliedIds
            )

            ->select(
                'discount_type.*'
            )

            ->get();

            DB::commit();

            return response()->json([

                'success' => true,

                'message' =>
                    'Discount removed successfully.',

                'order' =>
                    $this->buildOrderPayload(
                        $shopOrderId
                    ),

                'available_discounts' =>
                    $availableDiscounts,

                'applied_discounts' =>
                    $appliedDiscounts,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleteCharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_order_applied_charge_id' => [
                'required',
                'integer',
                Rule::exists('shop_order_applied_charge', 'id'),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {

            $validated = $validator->validated();

            /*
            |----------------------------------------------------------------------
            | APPLIED CHARGE
            |----------------------------------------------------------------------
            */

            $appliedCharge = ShopOrderAppliedCharge::query()
                ->findOrFail($validated['shop_order_applied_charge_id']);

            $shopOrderId = $appliedCharge->shop_order_id;

            /*
            |----------------------------------------------------------------------
            | DELETE
            |----------------------------------------------------------------------
            */

            $appliedCharge->delete();

            /*
            |----------------------------------------------------------------------
            | RECOMPUTE
            |----------------------------------------------------------------------
            */

            $this->recomputeShopOrder($shopOrderId);

            /*
            |----------------------------------------------------------------------
            | REFRESH
            |----------------------------------------------------------------------
            */

            $shopOrder = ShopOrder::query()->findOrFail($shopOrderId);

            $appliedCharges = ShopOrderAppliedCharge::query()
                ->where('shop_order_id', $shopOrderId)
                ->get();

            $appliedChargeIds = $appliedCharges->pluck('charge_type_id');

            $availableCharges = DB::table('shop_register_charge')
                ->join('charge_type', 'charge_type.id', '=', 'shop_register_charge.charge_type_id')
                ->where('shop_register_charge.shop_register_id', $shopOrder->shop_register_id)
                ->whereNotIn('charge_type.id', $appliedChargeIds)
                ->select('charge_type.*')
                ->get();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Charge removed successfully.',

                'order' => $this->buildOrderPayload($shopOrderId),

                'available_charges' => $availableCharges,
                'applied_charges' => $appliedCharges,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function fetchFloorPlans(Request $request)
    {
        $floorPlans = DB::table('shop_register_floor_plan')

            ->where(
                'shop_register_id',
                $request->shop_register_id
            )

            ->select(
                'floor_plan_id as id',
                'floor_plan_name'
            )

            ->orderBy('floor_plan_name')

            ->get();

        return response()->json([
            'success' => true,
            'floorPlans' => $floorPlans,
        ]);
    }

    public function fetchFloorTables(Request $request)
    {
        $shopOrder = ShopOrder::find(
            $request->shop_order_id
        );

        $tables = FloorPlanTable::query()

            ->where(
                'floor_plan_id',
                $request->floor_plan_id
            )

            ->orderBy('table_number')

            ->get()

            ->map(function ($table) use ($shopOrder) {

                $occupied = ShopOrder::query()

                    ->where(
                        'floor_plan_table_id',
                        $table->id
                    )

                    ->where('id', '!=', $shopOrder->id)

                    ->whereNotIn('order_status', [
                        'Completed',
                        'Cancelled',
                        'Voided',
                    ])

                    ->exists();

                return [

                    'id' => $table->id,

                    'table_number' =>
                        $table->table_number,

                    'seats' =>
                        $table->seats,

                    'is_occupied' =>
                        $occupied,

                    'is_selected' =>
                        $shopOrder->floor_plan_table_id
                        == $table->id,
                ];
            });

        return response()->json([
            'success' => true,
            'tables' => $tables,
        ]);
    }

    public function fetchDiscounts(Request $request)
    {
        $validated = $request->validate([
            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],
        ]);

        $shopOrder = ShopOrder::findOrFail(
            $validated['shop_order_id']
        );

        $appliedDiscounts =
            ShopOrderAppliedDiscount::query()

            ->where('shop_order_id', $shopOrder->id)

            ->get();

        $appliedIds =
            $appliedDiscounts
                ->pluck('discount_type_id');

        $availableDiscounts = DB::table(
            'shop_register_discount'
        )

        ->join(
            'discount_type',
            'discount_type.id',
            '=',
            'shop_register_discount.discount_type_id'
        )

        ->where(
            'shop_register_discount.shop_register_id',
            $shopOrder->shop_register_id
        )

        ->whereNotIn(
            'discount_type.id',
            $appliedIds
        )

        ->select(
            'discount_type.*'
        )

        ->get();

        return response()->json([

            'success' => true,

            'available_discounts' =>
                $availableDiscounts,

            'applied_discounts' =>
                $appliedDiscounts,
        ]);
    }

    public function fetchCharges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder = ShopOrder::query()
            ->find($validated['shop_order_id']);

        if (!$shopOrder) {

            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | AVAILABLE CHARGES
        |--------------------------------------------------------------------------
        */

        $appliedChargeIds = ShopOrderAppliedCharge::query()
            ->where('shop_order_id', $shopOrder->id)
            ->pluck('charge_type_id');

        $availableCharges = DB::table('shop_register_charge')
            ->join(
                'charge_type',
                'charge_type.id',
                '=',
                'shop_register_charge.charge_type_id'
            )
            ->where(
                'shop_register_charge.shop_register_id',
                $shopOrder->shop_register_id
            )
            ->whereNotIn(
                'shop_register_charge.charge_type_id',
                $appliedChargeIds
            )
            ->select([
                'charge_type.id',
                'charge_type.charge_type_name',
                'charge_type.value_type',
                'charge_type.charge_value',
                'charge_type.is_variable',
                'charge_type.application_order',
                'charge_type.tax_type',
            ])
            ->orderBy('charge_type.charge_type_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | APPLIED CHARGES
        |--------------------------------------------------------------------------
        */

        $appliedCharges = ShopOrderAppliedCharge::query()
            ->where('shop_order_id', $shopOrder->id)
            ->orderBy('id')
            ->get([
                'id',
                'charge_type_id',
                'charge_type_name',
                'value_type',
                'charge_value',
                'application_order',
                'tax_type',
                'charge_rate',
                'charge_amount',

                // ✅ ADDED FIELDS
                'remarks',
                'applied_by_name',
                'applied_by',
            ]);

        return response()->json([
            'success' => true,

            'available_charges' => $availableCharges,

            'applied_charges' => $appliedCharges,
        ]);
    }

    public function fetchPaymentMethods(
        Request $request
    )
    {
        $validator = Validator::make(
            $request->all(),
            [
                'shop_order_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'shop_order',
                        'id'
                    ),
                ],
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message'
                    => $validator
                        ->errors()
                        ->first(),
            ]);
        }

        $validated =
            $validator->validated();

        $shopOrder = ShopOrder::query()
            ->find(
                $validated['shop_order_id']
            );

        $paymentMethods = DB::table(
            'shop_register_payment_method'
        )

        ->where(
            'shop_register_id',
            $shopOrder->shop_register_id
        )

        ->orderBy(
            'payment_method_name'
        )

        ->get([
            'payment_method_id',
            'payment_method_name',
        ]);

        return response()->json([
            'success' => true,

            'order_number'
                => $shopOrder->order_number,

            'balance_due'
                => $shopOrder->balance_due,

            'payment_methods'
                => $paymentMethods,
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [

            /*
            |--------------------------------------------------------------------------
            | SHOP ORDER
            |--------------------------------------------------------------------------
            */

            'shop_order_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('shop_order', 'id'),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | PAGE CONTEXT
        |--------------------------------------------------------------------------
        */

        $pageAppId = (int) $request->input('appId');

        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'notExist' => false,
                'message' =>
                    $validator->errors()->first('shop_order_id')
                    ?? 'Validation failed',
            ]);
        }

        $validated = $validator->validated();

        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder = ShopOrder::query()

            ->whereKey($validated['shop_order_id'])

            ->first();

        /*
        |--------------------------------------------------------------------------
        | ORDER NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$shopOrder) {

            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success' => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message' => 'Order not found.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER ITEMS
        |--------------------------------------------------------------------------
        */

        $items = ShopOrderItem::query()

            ->where('shop_order_id', $shopOrder->id)

            ->orderBy('id')

            ->get([
                'id',

                'product_id',

                'product_name',

                'sku',

                'barcode',

                'quantity',

                'unit_price',

                'original_unit_price',

                'line_subtotal',

                'line_total',

                'tax_classification',

                'vatable_sales',

                'vat_exempt_sales',

                'zero_rated_sales',

                'vat_amount',

                'order_note',

                'item_status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | APPLIED DISCOUNTS
        |--------------------------------------------------------------------------
        */

        $appliedDiscounts = ShopOrderAppliedDiscount::query()

            ->where('shop_order_id', $shopOrder->id)

            ->get([
                'id',
                'discount_type_id',
                'discount_type_name',
                'discount_amount',
            ]);

        /*
        |--------------------------------------------------------------------------
        | APPLIED CHARGES
        |--------------------------------------------------------------------------
        */

        $appliedCharges = ShopOrderAppliedCharge::query()

            ->where('shop_order_id', $shopOrder->id)

            ->get([
                'id',
                'charge_type_id',
                'charge_type_name',
                'charge_amount',
            ]);

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'notExist' => false,

            'order' => $this->buildOrderPayload(
                $shopOrder->id
            ),
        ]);
    }

    private function recomputeShopOrder(int $shopOrderId): void
    {
        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder = ShopOrder::query()
            ->findOrFail($shopOrderId);

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        $items = ShopOrderItem::query()

            ->where('shop_order_id', $shopOrderId)

            ->where('item_status', '!=', 'Cancelled')

            ->get();

        /*
        |--------------------------------------------------------------------------
        | RECOMPUTE ITEM TOTALS
        |--------------------------------------------------------------------------
        */

        foreach ($items as $item) {

            $quantity = round((float) $item->quantity, 2);

            $unitPrice = round((float) $item->unit_price, 2);

            $lineSubtotal = round(
                $quantity * $unitPrice,
                2
            );

            $vatableSales = 0;
            $vatExemptSales = 0;
            $zeroRatedSales = 0;
            $vatAmount = 0;

            /*
            |--------------------------------------------------------------------------
            | TAX CLASSIFICATION
            |--------------------------------------------------------------------------
            */

            if ($item->tax_classification === 'Vatable') {

                $vatableSales = round(
                    $lineSubtotal / 1.12,
                    2
                );

                $vatAmount = round(
                    $lineSubtotal - $vatableSales,
                    2
                );
            }

            elseif ($item->tax_classification === 'VAT Exempt') {

                $vatExemptSales = $lineSubtotal;
            }

            elseif ($item->tax_classification === 'Zero Rated') {

                $zeroRatedSales = $lineSubtotal;
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE ITEM
            |--------------------------------------------------------------------------
            */

            $item->update([

                'line_subtotal' =>
                    $lineSubtotal,

                'line_total' =>
                    $lineSubtotal,

                'vatable_sales' =>
                    $vatableSales,

                'vat_exempt_sales' =>
                    $vatExemptSales,

                'zero_rated_sales' =>
                    $zeroRatedSales,

                'vat_amount' =>
                    $vatAmount,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER TOTALS
        |--------------------------------------------------------------------------
        */

        $totals = ShopOrderItem::query()

            ->where('shop_order_id', $shopOrderId)

            ->where('item_status', '!=', 'Cancelled')

            ->selectRaw('
                COUNT(*) as total_items,
                SUM(quantity) as total_quantity,
                SUM(line_subtotal) as subtotal,
                SUM(vatable_sales) as vatable_sales,
                SUM(vat_exempt_sales) as vat_exempt_sales,
                SUM(zero_rated_sales) as zero_rated_sales,
                SUM(vat_amount) as vat_amount,
                SUM(line_total) as gross_total
            ')

            ->first();

        $subtotal = round(
            (float) ($totals->subtotal ?? 0),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | DISCOUNTS
        |--------------------------------------------------------------------------
        */

        $discountTotal = 0;

        $discounts = ShopOrderAppliedDiscount::query()

            ->where('shop_order_id', $shopOrderId)

            ->orderBy('id')

            ->get();

        foreach ($discounts as $discount) {

            $discountAmount = 0;

            /*
            |--------------------------------------------------------------------------
            | PERCENTAGE
            |--------------------------------------------------------------------------
            */

            if ($discount->value_type === 'Percentage') {

                $discountAmount = round(
                    ($subtotal * $discount->discount_value) / 100,
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | FIXED
            |--------------------------------------------------------------------------
            */

            else {

                $discountAmount = round(
                    $discount->discount_value,
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE APPLIED DISCOUNT
            |--------------------------------------------------------------------------
            */

            $discount->update([

                'discount_rate' =>
                    $discount->value_type === 'Percentage'
                        ? $discount->discount_value
                        : 0,

                'discount_amount' =>
                    $discountAmount,
            ]);

            $discountTotal += $discountAmount;
        }

        /*
        |--------------------------------------------------------------------------
        | CHARGES
        |--------------------------------------------------------------------------
        */

        $chargeTotal = 0;

        $charges = ShopOrderAppliedCharge::query()

            ->where('shop_order_id', $shopOrderId)

            ->orderBy('id')

            ->get();

        foreach ($charges as $charge) {

            $chargeAmount = 0;

            $chargeVatableAmount = 0;

            $chargeVatAmount = 0;

            /*
            |--------------------------------------------------------------------------
            | PERCENTAGE
            |--------------------------------------------------------------------------
            */

            if ($charge->value_type === 'Percentage') {

                $chargeAmount = round(
                    ($subtotal * $charge->charge_value) / 100,
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | FIXED
            |--------------------------------------------------------------------------
            */

            else {

                $chargeAmount = round(
                    $charge->charge_value,
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | TAXABLE CHARGE
            |--------------------------------------------------------------------------
            */

            if ($charge->tax_type === 'Vatable') {

                $chargeVatableAmount = round(
                    $chargeAmount / 1.12,
                    2
                );

                $chargeVatAmount = round(
                    $chargeAmount - $chargeVatableAmount,
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE APPLIED CHARGE
            |--------------------------------------------------------------------------
            */

            $charge->update([

                'charge_rate' =>
                    $charge->value_type === 'Percentage'
                        ? $charge->charge_value
                        : 0,

                'charge_amount' =>
                    $chargeAmount,

                'vatable_amount' =>
                    $chargeVatableAmount,

                'vat_amount' =>
                    $chargeVatAmount,
            ]);

            $chargeTotal += $chargeAmount;
        }

        $discountTotal = round($discountTotal, 2);

        $chargeTotal = round($chargeTotal, 2);

        /*
        |--------------------------------------------------------------------------
        | FINAL TOTALS
        |--------------------------------------------------------------------------
        */

        $grossTotal = round(
            $subtotal + $chargeTotal,
            2
        );

        $netTotal = round(
            $grossTotal - $discountTotal,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder->update([

            'total_items' =>
                (int) ($totals->total_items ?? 0),

            'total_quantity' =>
                round((float) ($totals->total_quantity ?? 0), 2),

            'subtotal' =>
                $subtotal,

            'discount_total' =>
                $discountTotal,

            'charge_total' =>
                $chargeTotal,

            'vatable_sales' =>
                round(
                    (float) ($totals->vatable_sales ?? 0),
                    2
                ),

            'vat_exempt_sales' =>
                round(
                    (float) ($totals->vat_exempt_sales ?? 0),
                    2
                ),

            'zero_rated_sales' =>
                round(
                    (float) ($totals->zero_rated_sales ?? 0),
                    2
                ),

            'vat_amount' =>
                round(
                    (
                        (float) ($totals->vat_amount ?? 0)
                        +
                        ShopOrderAppliedCharge::query()
                            ->where('shop_order_id', $shopOrderId)
                            ->sum('vat_amount')
                    ),
                    2
                ),

            'gross_total' =>
                $grossTotal,

            'net_total' =>
                $netTotal,

            'balance_due' =>
                $netTotal,
        ]);
    }

    private function buildOrderPayload(int $shopOrderId): array
    {
        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder = ShopOrder::query()
            ->findOrFail($shopOrderId);

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        $items = ShopOrderItem::query()

            ->where('shop_order_id', $shopOrderId)

            ->where('item_status', '!=', 'Cancelled')

            ->orderBy('id')

            ->get([
                'id',

                'product_id',

                'product_name',

                'sku',

                'barcode',

                'quantity',

                'unit_price',

                'original_unit_price',

                'line_subtotal',

                'line_total',

                'tax_classification',

                'vatable_sales',

                'vat_exempt_sales',

                'zero_rated_sales',

                'vat_amount',

                'order_note',

                'item_status',
            ]);

        /*
        |--------------------------------------------------------------------------
        | DISCOUNTS
        |--------------------------------------------------------------------------
        */

        $discounts = ShopOrderAppliedDiscount::query()

            ->where('shop_order_id', $shopOrderId)

            ->orderBy('id')

            ->get([
                'id',

                'discount_type_name',

                'value_type',

                'discount_value',

                'discount_rate',

                'discount_amount',

                'application_order',
            ]);

        /*
        |--------------------------------------------------------------------------
        | CHARGES
        |--------------------------------------------------------------------------
        */

        $charges = ShopOrderAppliedCharge::query()

            ->where('shop_order_id', $shopOrderId)

            ->orderBy('id')

            ->get([
                'id',

                'charge_type_name',

                'value_type',

                'charge_value',

                'charge_rate',

                'charge_amount',

                'tax_type',

                'application_order',
            ]);

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return [

            'id' =>
                $shopOrder->id,

            'order_number' =>
                $shopOrder->order_number,

            'order_type' =>
                $shopOrder->order_type,

            'order_status' =>
                $shopOrder->order_status,

            'payment_status' =>
                $shopOrder->payment_status,

            /*
            |--------------------------------------------------------------------------
            | REGISTER
            |--------------------------------------------------------------------------
            */

            'shop_register_id' =>
                $shopOrder->shop_register_id,

            'shop_register_name' =>
                $shopOrder->shop_register_name,

            /*
            |--------------------------------------------------------------------------
            | FLOOR PLAN
            |--------------------------------------------------------------------------
            */

            'floor_plan_id' =>
                $shopOrder->floor_plan_id,

            'floor_plan_name' =>
                $shopOrder->floor_plan_name,

            'floor_plan_table_id' =>
                $shopOrder->floor_plan_table_id,

            'table_number' =>
                $shopOrder->table_number,

            /*
            |--------------------------------------------------------------------------
            | CUSTOMER
            |--------------------------------------------------------------------------
            */

            'customer_name' =>
                $shopOrder->customer_name,

            /*
            |--------------------------------------------------------------------------
            | TOTALS
            |--------------------------------------------------------------------------
            */

            'total_items' =>
                (int) $shopOrder->total_items,

            'total_quantity' =>
                (float) $shopOrder->total_quantity,

            'subtotal' =>
                (float) $shopOrder->subtotal,

            'discount_total' =>
                (float) $shopOrder->discount_total,

            'charge_total' =>
                (float) $shopOrder->charge_total,

            'vatable_sales' =>
                (float) $shopOrder->vatable_sales,

            'vat_exempt_sales' =>
                (float) $shopOrder->vat_exempt_sales,

            'zero_rated_sales' =>
                (float) $shopOrder->zero_rated_sales,

            'vat_amount' =>
                (float) $shopOrder->vat_amount,

            'gross_total' =>
                (float) $shopOrder->gross_total,

            'net_total' =>
                (float) $shopOrder->net_total,

            'paid_amount' =>
                (float) $shopOrder->paid_amount,

            'change_amount' =>
                (float) $shopOrder->change_amount,

            'balance_due' =>
                (float) $shopOrder->balance_due,

            /*
            |--------------------------------------------------------------------------
            | RELATIONS
            |--------------------------------------------------------------------------
            */

            'items' =>
                $items,

            'discounts' =>
                $discounts,

            'charges' =>
                $charges,
        ];
    }
}
