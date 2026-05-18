<?php

namespace App\Http\Controllers;

use App\Models\FloorPlanTable;
use App\Models\Product;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShopOrderController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [

            /*
            |--------------------------------------------------------------------------
            | REGISTER
            |--------------------------------------------------------------------------
            */

            'shop_register_id' => [
                'required',
                'integer',
                Rule::exists('shop_register', 'id'),
            ],

            /*
            |--------------------------------------------------------------------------
            | PRODUCT
            |--------------------------------------------------------------------------
            */

            'modal_product_id' => [
                'required',
                'integer',
                Rule::exists('product', 'id'),
            ],

            /*
            |--------------------------------------------------------------------------
            | ORDER
            |--------------------------------------------------------------------------
            */

            'order_qty_input' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'order_note' => [
                'nullable',
                'string',
                'max:1000',
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

            $shopRegister = ShopRegister::query()
                ->whereKey($validated['shop_register_id'])
                ->first();

            if (!$shopRegister) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop register not found.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | PRODUCT
            |--------------------------------------------------------------------------
            */

            $product = Product::query()
                ->whereKey($validated['modal_product_id'])
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | QUANTITY
            |--------------------------------------------------------------------------
            */

            $quantity = (float) $validated['order_qty_input'];

            /*
            |--------------------------------------------------------------------------
            | PRICE
            |--------------------------------------------------------------------------
            */

            $unitPrice = (float) $product->base_price;

            $lineSubtotal = round(
                $quantity * $unitPrice,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | VAT COMPUTATION
            |--------------------------------------------------------------------------
            */

            $vatableSales = 0;
            $vatExemptSales = 0;
            $zeroRatedSales = 0;
            $vatAmount = 0;

            /*
            |--------------------------------------------------------------------------
            | VATABLE
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | VAT EXEMPT
            |--------------------------------------------------------------------------
            */

            elseif ($product->tax_classification === 'VAT Exempt') {

                $vatExemptSales = $lineSubtotal;
            }

            /*
            |--------------------------------------------------------------------------
            | ZERO RATED
            |--------------------------------------------------------------------------
            */

            elseif ($product->tax_classification === 'Zero Rated') {

                $zeroRatedSales = $lineSubtotal;
            }

            /*
            |--------------------------------------------------------------------------
            | OPEN ORDER
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | We DO NOT filter by floor plan anymore
            | because table assignment is handled separately.
            |
            */

            $shopOrder = ShopOrder::query()

                ->where('shop_register_id', $shopRegister->id)

                ->whereIn('order_status', [
                    'Draft',
                    'Pending Payment',
                ])

                ->latest('id')

                ->first();

            /*
            |--------------------------------------------------------------------------
            | CREATE ORDER
            |--------------------------------------------------------------------------
            */

            if (!$shopOrder) {

                $orderNumber = 'SO-' . now()->format('YmdHis');

                $shopOrder = ShopOrder::create([

                    /*
                    |--------------------------------------------------------------------------
                    | REGISTER
                    |--------------------------------------------------------------------------
                    */

                    'shop_register_id' => $shopRegister->id,

                    'shop_register_name' => $shopRegister->shop_register_name,

                    /*
                    |--------------------------------------------------------------------------
                    | ORDER
                    |--------------------------------------------------------------------------
                    */

                    'order_number' => $orderNumber,

                    /*
                    |--------------------------------------------------------------------------
                    | DEFAULT STATE
                    |--------------------------------------------------------------------------
                    */

                    'order_type' => 'Walk-in',

                    'order_status' => 'Draft',

                    /*
                    |--------------------------------------------------------------------------
                    | FLOOR PLAN
                    |--------------------------------------------------------------------------
                    |
                    | NULL FIRST
                    | Will be updated later
                    |
                    */

                    'floor_plan_table_id' => null,

                    'floor_plan_table_name' => null,

                    /*
                    |--------------------------------------------------------------------------
                    | TOTALS
                    |--------------------------------------------------------------------------
                    */

                    'subtotal_amount' => 0,

                    'discount_amount' => 0,

                    'charge_amount' => 0,

                    'vatable_sales' => 0,

                    'vat_exempt_sales' => 0,

                    'zero_rated_sales' => 0,

                    'vat_amount' => 0,

                    'gross_amount' => 0,

                    'net_amount' => 0,

                    'paid_amount' => 0,

                    'change_amount' => 0,

                    /*
                    |--------------------------------------------------------------------------
                    | USER
                    |--------------------------------------------------------------------------
                    */

                    'created_by' => Auth::id(),

                    'created_by_name' => Auth::user()->name,

                    'last_log_by' => Auth::id(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | EXISTING ITEM
            |--------------------------------------------------------------------------
            |
            | Merge:
            | - same product
            | - same note
            |
            */

            $existingItem = ShopOrderItem::query()

                ->where('shop_order_id', $shopOrder->id)

                ->where('product_id', $product->id)

                ->where(
                    'order_note',
                    $validated['order_note'] ?? null
                )

                ->first();

            /*
            |--------------------------------------------------------------------------
            | UPDATE EXISTING ITEM
            |--------------------------------------------------------------------------
            */

            if ($existingItem) {

                $existingItem->quantity += $quantity;

                $existingItem->subtotal_amount = round(
                    $existingItem->quantity
                    * $existingItem->unit_price,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | RESET
                |--------------------------------------------------------------------------
                */

                $existingItem->vatable_sales = 0;
                $existingItem->vat_exempt_sales = 0;
                $existingItem->zero_rated_sales = 0;
                $existingItem->vat_amount = 0;

                /*
                |--------------------------------------------------------------------------
                | VATABLE
                |--------------------------------------------------------------------------
                */

                if (
                    $existingItem->tax_classification === 'Vatable'
                ) {

                    $existingItem->vatable_sales = round(
                        $existingItem->subtotal_amount / 1.12,
                        2
                    );

                    $existingItem->vat_amount = round(
                        $existingItem->subtotal_amount
                        - $existingItem->vatable_sales,
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | VAT EXEMPT
                |--------------------------------------------------------------------------
                */

                elseif (
                    $existingItem->tax_classification === 'VAT Exempt'
                ) {

                    $existingItem->vat_exempt_sales =
                        $existingItem->subtotal_amount;
                }

                /*
                |--------------------------------------------------------------------------
                | ZERO RATED
                |--------------------------------------------------------------------------
                */

                elseif (
                    $existingItem->tax_classification === 'Zero Rated'
                ) {

                    $existingItem->zero_rated_sales =
                        $existingItem->subtotal_amount;
                }

                /*
                |--------------------------------------------------------------------------
                | TOTAL
                |--------------------------------------------------------------------------
                */

                $existingItem->total_amount =
                    $existingItem->subtotal_amount;

                $existingItem->save();
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE ITEM
            |--------------------------------------------------------------------------
            */

            else {

                ShopOrderItem::create([

                    /*
                    |--------------------------------------------------------------------------
                    | ORDER
                    |--------------------------------------------------------------------------
                    */

                    'shop_order_id' => $shopOrder->id,

                    'order_number' => $shopOrder->order_number,

                    /*
                    |--------------------------------------------------------------------------
                    | PRODUCT
                    |--------------------------------------------------------------------------
                    */

                    'product_id' => $product->id,

                    'product_name' => $product->product_name,

                    /*
                    |--------------------------------------------------------------------------
                    | QUANTITY
                    |--------------------------------------------------------------------------
                    */

                    'quantity' => $quantity,

                    /*
                    |--------------------------------------------------------------------------
                    | PRICE
                    |--------------------------------------------------------------------------
                    */

                    'unit_price' => $unitPrice,

                    /*
                    |--------------------------------------------------------------------------
                    | TAX
                    |--------------------------------------------------------------------------
                    */

                    'tax_classification' =>
                        $product->tax_classification,

                    /*
                    |--------------------------------------------------------------------------
                    | TOTALS
                    |--------------------------------------------------------------------------
                    */

                    'subtotal_amount' => $lineSubtotal,

                    'discount_amount' => 0,

                    'charge_amount' => 0,

                    'vatable_sales' => $vatableSales,

                    'vat_exempt_sales' => $vatExemptSales,

                    'zero_rated_sales' => $zeroRatedSales,

                    'vat_amount' => $vatAmount,

                    'total_amount' => $lineSubtotal,

                    /*
                    |--------------------------------------------------------------------------
                    | NOTES
                    |--------------------------------------------------------------------------
                    */

                    'order_note' =>
                        $validated['order_note'] ?? null,

                    /*
                    |--------------------------------------------------------------------------
                    | USER
                    |--------------------------------------------------------------------------
                    */

                    'last_log_by' => Auth::id(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | RECOMPUTE ORDER TOTALS
            |--------------------------------------------------------------------------
            */

            $totals = ShopOrderItem::query()

                ->where('shop_order_id', $shopOrder->id)

                ->selectRaw('
                    SUM(subtotal_amount) as subtotal_amount,
                    SUM(discount_amount) as discount_amount,
                    SUM(charge_amount) as charge_amount,
                    SUM(vatable_sales) as vatable_sales,
                    SUM(vat_exempt_sales) as vat_exempt_sales,
                    SUM(zero_rated_sales) as zero_rated_sales,
                    SUM(vat_amount) as vat_amount,
                    SUM(total_amount) as total_amount
                ')

                ->first();

            /*
            |--------------------------------------------------------------------------
            | UPDATE ORDER
            |--------------------------------------------------------------------------
            */

            $shopOrder->update([

                'subtotal_amount' =>
                    round($totals->subtotal_amount ?? 0, 2),

                'discount_amount' =>
                    round($totals->discount_amount ?? 0, 2),

                'charge_amount' =>
                    round($totals->charge_amount ?? 0, 2),

                'vatable_sales' =>
                    round($totals->vatable_sales ?? 0, 2),

                'vat_exempt_sales' =>
                    round($totals->vat_exempt_sales ?? 0, 2),

                'zero_rated_sales' =>
                    round($totals->zero_rated_sales ?? 0, 2),

                'vat_amount' =>
                    round($totals->vat_amount ?? 0, 2),

                'gross_amount' =>
                    round($totals->total_amount ?? 0, 2),

                'net_amount' =>
                    round($totals->total_amount ?? 0, 2),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order added successfully.',
                'shop_order_id' => $shopOrder->id,
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
}
