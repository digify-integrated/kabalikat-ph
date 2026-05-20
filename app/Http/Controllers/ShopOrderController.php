<?php

namespace App\Http\Controllers;

use App\Models\FloorPlanTable;
use App\Models\Product;
use App\Models\ShopOrder;
use App\Models\ShopOrderAppliedCharge;
use App\Models\ShopOrderAppliedDiscount;
use App\Models\ShopOrderItem;
use App\Models\ShopRegister;
use App\Models\ShopRegisterSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
            'shop_order_id' => ['required', 'integer', Rule::exists('shop_order', 'id')],
            'shop_register_id' => ['required', 'integer', Rule::exists('shop_register', 'id')],
            'order_type' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $payload = [
            'order_type' => $validated['order_type'],
            'last_log_by' => Auth::id(),
        ];

        $shopOrderId = $validated['shop_order_id'] ?? null;

        $shopOrder = ShopOrder::query()->findOrFail($shopOrderId);
        $shopOrder->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'The order preset has been saved successfully',
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

            'order' => [

                /*
                |--------------------------------------------------------------------------
                | ORDER
                |--------------------------------------------------------------------------
                */

                'id' => $shopOrder->id,

                'order_number' => $shopOrder->order_number,

                'order_type' => $shopOrder->order_type,

                'order_status' => $shopOrder->order_status,

                'payment_status' => $shopOrder->payment_status,

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
                | TABLE
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
                | DISCOUNTS
                |--------------------------------------------------------------------------
                */

                'applied_discounts' => $appliedDiscounts,

                /*
                |--------------------------------------------------------------------------
                | CHARGES
                |--------------------------------------------------------------------------
                */

                'applied_charges' => $appliedCharges,

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
                | ITEMS
                |--------------------------------------------------------------------------
                */

                'items' => $items,
            ],
        ]);
    }
}
