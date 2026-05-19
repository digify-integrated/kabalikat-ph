<?php

namespace App\Http\Controllers;

use App\Models\FloorPlanTable;
use App\Models\Product;
use App\Models\ShopOrder;
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
            | QUANTITY
            |--------------------------------------------------------------------------
            */

            'order_qty_input' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            /*
            |--------------------------------------------------------------------------
            | NOTE
            |--------------------------------------------------------------------------
            */

            'order_note' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | EXISTING ORDER
            |--------------------------------------------------------------------------
            */

            'shop_order_id' => [
                'nullable',
                'integer'
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
            | OPEN REGISTER SESSION
            |--------------------------------------------------------------------------
            */

            $shopRegisterSession = ShopRegisterSession::query()

                ->where('shop_register_id', $shopRegister->id)

                /*
                |--------------------------------------------------------------------------
                | OPEN SESSION
                |--------------------------------------------------------------------------
                */

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

            $quantity = round(
                (float) $validated['order_qty_input'],
                2
            );

            /*
            |--------------------------------------------------------------------------
            | PRICING
            |--------------------------------------------------------------------------
            */

            $originalUnitPrice = round(
                (float) $product->base_price,
                2
            );

            $unitPrice = $originalUnitPrice;

            $lineSubtotal = round(
                $quantity * $unitPrice,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | TAX
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
            | ORDER
            |--------------------------------------------------------------------------
            */

            $shopOrder = null;

            /*
            |--------------------------------------------------------------------------
            | EXISTING ORDER
            |--------------------------------------------------------------------------
            */

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

            if (!$shopOrder) {

                $orderNumber =
                    'SO-' .
                    now()->format('YmdHis') .
                    '-' .
                    Str::upper(Str::random(4));

                $shopOrder = ShopOrder::create([

                    /*
                    |--------------------------------------------------------------------------
                    | ORDER
                    |--------------------------------------------------------------------------
                    */

                    'order_number' => $orderNumber,

                    /*
                    |--------------------------------------------------------------------------
                    | REGISTER
                    |--------------------------------------------------------------------------
                    */

                    'shop_register_id' =>
                        $shopRegister->id,

                    'shop_register_name' =>
                        $shopRegister->shop_register_name,

                    /*
                    |--------------------------------------------------------------------------
                    | SESSION
                    |--------------------------------------------------------------------------
                    */

                    'shop_register_session_id' =>
                        $shopRegisterSession->id,

                    /*
                    |--------------------------------------------------------------------------
                    | DEFAULTS
                    |--------------------------------------------------------------------------
                    */

                    'order_type' => 'Walk-in',

                    /*
                    |--------------------------------------------------------------------------
                    | TIMESTAMP
                    |--------------------------------------------------------------------------
                    */

                    'ordered_at' => now(),

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
            | MERGE:
            | - SAME PRODUCT
            | - SAME NOTE
            |
            */

            $existingItem = ShopOrderItem::query()

                ->where('shop_order_id', $shopOrder->id)

                ->where('product_id', $product->id)

                ->where(
                    'order_note',
                    $validated['order_note'] ?? null
                )

                ->whereNotIn('item_status', [
                    'Cancelled',
                ])

                ->first();

            /*
            |--------------------------------------------------------------------------
            | UPDATE EXISTING ITEM
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

                /*
                |--------------------------------------------------------------------------
                | RESET TAX
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
                        $existingItem->line_subtotal / 1.12,
                        2
                    );

                    $existingItem->vat_amount = round(
                        $existingItem->line_subtotal
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
                        $existingItem->line_subtotal;
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
                        $existingItem->line_subtotal;
                }

                /*
                |--------------------------------------------------------------------------
                | TOTAL
                |--------------------------------------------------------------------------
                */

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

                    /*
                    |--------------------------------------------------------------------------
                    | ORDER
                    |--------------------------------------------------------------------------
                    */

                    'shop_order_id' =>
                        $shopOrder->id,

                    /*
                    |--------------------------------------------------------------------------
                    | PRODUCT
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | QUANTITY
                    |--------------------------------------------------------------------------
                    */

                    'quantity' =>
                        $quantity,

                    /*
                    |--------------------------------------------------------------------------
                    | PRICE
                    |--------------------------------------------------------------------------
                    */

                    'original_unit_price' =>
                        $originalUnitPrice,

                    'unit_price' =>
                        $unitPrice,

                    /*
                    |--------------------------------------------------------------------------
                    | TOTALS
                    |--------------------------------------------------------------------------
                    */

                    'line_subtotal' =>
                        $lineSubtotal,

                    'line_total' =>
                        $lineSubtotal,

                    /*
                    |--------------------------------------------------------------------------
                    | TAX
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | NOTES
                    |--------------------------------------------------------------------------
                    */

                    'order_note' =>
                        $validated['order_note'] ?? null,

                    /*
                    |--------------------------------------------------------------------------
                    | STATUS
                    |--------------------------------------------------------------------------
                    */

                    'item_status' => 'Pending',

                    'queued_at' => now(),

                    /*
                    |--------------------------------------------------------------------------
                    | USER
                    |--------------------------------------------------------------------------
                    */

                    'last_log_by' =>
                        Auth::id(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | RECOMPUTE ORDER TOTALS
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

            /*
            |--------------------------------------------------------------------------
            | NET TOTAL
            |--------------------------------------------------------------------------
            */

            $netTotal = round(
                ($totals->gross_total ?? 0),
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
                    round($totals->subtotal ?? 0, 2),

                'vatable_sales' =>
                    round($totals->vatable_sales ?? 0, 2),

                'vat_exempt_sales' =>
                    round($totals->vat_exempt_sales ?? 0, 2),

                'zero_rated_sales' =>
                    round($totals->zero_rated_sales ?? 0, 2),

                'vat_amount' =>
                    round($totals->vat_amount ?? 0, 2),

                'gross_total' =>
                    round($totals->gross_total ?? 0, 2),

                'net_total' =>
                    $netTotal,

                'balance_due' =>
                    $netTotal,
            ]);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Order saved successfully.',

                'shop_order_id' =>
                    $shopOrder->id,

                'order_number' =>
                    $shopOrder->order_number,
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
