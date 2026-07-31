<?php

namespace App\Http\Controllers;

use App\Models\ChargeType;
use App\Models\DiscountType;
use App\Models\FloorPlanTable;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShopOrder;
use App\Models\ShopOrderAppliedCharge;
use App\Models\ShopOrderAppliedDiscount;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderPayment;
use App\Models\ShopRegister;
use App\Models\ShopRegisterSession;
use App\Models\StockLevel;
use App\Models\StockMovement;
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
            'shop_register_id' => ['required', 'integer', Rule::exists('shop_register', 'id')],
            'modal_product_id' => ['nullable', 'integer', Rule::exists('product', 'id')],
            'barcode'          => ['nullable', 'string'], // Added barcode integration route parameter
            'order_qty_input'  => ['required', 'numeric', 'min:0.01'],
            'order_note'       => ['nullable', 'string'],
            'shop_order_id'    => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        // Enforce that either a clear structural product ID or a scanned barcode is provided
        if (empty($validated['modal_product_id']) && empty($validated['barcode'])) {
            return response()->json(['success' => false, 'message' => 'No product targeted for execution.']);
        }

        DB::beginTransaction();

        try {
            $shopRegister = ShopRegister::find($validated['shop_register_id']);
            if (!$shopRegister) {
                return response()->json(['success' => false, 'message' => 'Shop register not found.']);
            }

            $shopRegisterSession = ShopRegisterSession::query()
                ->where('shop_register_id', $shopRegister->id)
                ->whereNull('close_time')
                ->latest('id')
                ->first();

            if (!$shopRegisterSession) {
                return response()->json(['success' => false, 'message' => 'No open register session found.']);
            }

            // 💡 BARCODE RESOLUTION LOGIC
            if (!empty($validated['barcode'])) {
                $product = Product::query()
                    ->where('barcode', $validated['barcode'])
                    ->where('product_status', 'Active')
                    ->where('show_on_pos', 'Yes')
                    ->first();

                if (!$product) {
                    return response()->json(['success' => false, 'message' => "Product code '{$validated['barcode']}' not found."]);
                }
            } else {
                $product = Product::find($validated['modal_product_id']);
            }

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product context reference corrupted.']);
            }

            $quantity          = round((float) $validated['order_qty_input'], 2);
            $originalUnitPrice = round((float) $product->base_price, 2);
            $unitPrice         = $originalUnitPrice;
            $lineSubtotal      = round($quantity * $unitPrice, 2);

            $vatableSales   = 0;
            $vatExemptSales = 0;
            $zeroRatedSales = 0;
            $vatAmount      = 0;

            if ($product->tax_classification === 'Vatable') {
                $vatableSales = round($lineSubtotal / 1.12, 2);
                $vatAmount    = round($lineSubtotal - $vatableSales, 2);
            } elseif ($product->tax_classification === 'VAT Exempt') {
                $vatExemptSales = $lineSubtotal;
            } elseif ($product->tax_classification === 'Zero Rated') {
                $zeroRatedSales = $lineSubtotal;
            }

            $shopOrder = null;
            if (!empty($validated['shop_order_id'])) {
                $shopOrder = ShopOrder::query()
                    ->whereKey($validated['shop_order_id'])
                    ->where('payment_status', 'Unpaid')
                    ->whereNotIn('order_status', ['Completed', 'Cancelled', 'Voided'])
                    ->first();
            }

            $isNewOrder = false;
            if (!$shopOrder) {
                $isNewOrder = true;
                $orderNumber = 'SO-' . now()->format('ymd') . '-' . Str::upper(Str::random(4));
                $orderType = ($shopRegister->is_restaurant === 'Yes') ? 'Dine-in' : 'Walk-in';

                $shopOrder = ShopOrder::create([
                    'order_number'             => $orderNumber,
                    'shop_register_id'         => $shopRegister->id,
                    'shop_register_name'       => $shopRegister->shop_register_name,
                    'shop_register_session_id' => $shopRegisterSession->id,
                    'order_type'               => $orderType,
                    'ordered_at'               => now(),
                    'created_by'               => Auth::id(),
                    'created_by_name'          => Auth::user()->name,
                    'last_log_by'              => Auth::id(),
                ]);
            }

            $existingItem = ShopOrderItem::query()
                ->where('shop_order_id', $shopOrder->id)
                ->where('product_id', $product->id)
                ->where('order_note', $validated['order_note'] ?? null)
                ->where('item_status', '!=', 'Cancelled')
                ->first();

            if ($existingItem) {
                $existingItem->quantity      = round($existingItem->quantity + $quantity, 2);
                $existingItem->line_subtotal = round($existingItem->quantity * $existingItem->unit_price, 2);

                $existingItem->vatable_sales    = 0;
                $existingItem->vat_exempt_sales = 0;
                $existingItem->zero_rated_sales = 0;
                $existingItem->vat_amount       = 0;

                if ($existingItem->tax_classification === 'Vatable') {
                    $existingItem->vatable_sales = round($existingItem->line_subtotal / 1.12, 2);
                    $existingItem->vat_amount    = round($existingItem->line_subtotal - $existingItem->vatable_sales, 2);
                } elseif ($existingItem->tax_classification === 'VAT Exempt') {
                    $existingItem->vat_exempt_sales = $existingItem->line_subtotal;
                } elseif ($existingItem->tax_classification === 'Zero Rated') {
                    $existingItem->zero_rated_sales = $existingItem->line_subtotal;
                }

                $existingItem->line_total = $existingItem->line_subtotal;
                $existingItem->save();
            } else {
                ShopOrderItem::create([
                    'shop_order_id'       => $shopOrder->id,
                    'product_id'          => $product->id,
                    'product_name'        => $product->product_name,
                    'sku'                 => $product->sku,
                    'barcode'             => $product->barcode,
                    'product_type'        => $product->product_type,
                    'quantity'            => $quantity,
                    'original_unit_price' => $originalUnitPrice,
                    'unit_price'          => $unitPrice,
                    'line_subtotal'       => $lineSubtotal,
                    'line_total'          => $lineSubtotal,
                    'tax_classification'  => $product->tax_classification,
                    'vatable_sales'       => $vatableSales,
                    'vat_exempt_sales'    => $vatExemptSales,
                    'zero_rated_sales'    => $zeroRatedSales,
                    'vat_amount'          => $vatAmount,
                    'order_note'          => $validated['order_note'] ?? null,
                    'queued_at'           => now(),
                    'last_log_by'         => Auth::id(),
                ]);
            }

            if ($isNewOrder) {
                $discounts = DB::table('shop_register_discount')
                    ->join('discount_type', 'discount_type.id', '=', 'shop_register_discount.discount_type_id')
                    ->where('shop_register_discount.shop_register_id', $shopRegister->id)
                    ->where('shop_register_discount.automatic_application', 'Yes')
                    ->get();

                foreach ($discounts as $discount) {
                    ShopOrderAppliedDiscount::create([
                        'shop_order_id'      => $shopOrder->id,
                        'discount_type_id'   => $discount->discount_type_id,
                        'discount_type_name' => $discount->discount_type_name,
                        'value_type'         => $discount->value_type,
                        'discount_value'     => $discount->discount_value,
                        'application_order'  => $discount->application_order,
                        'is_vat_exempt'      => $discount->is_vat_exempt,
                        'applied_by'         => Auth::id(),
                        'applied_by_name'    => Auth::user()->name,
                        'last_log_by'        => Auth::id(),
                    ]);
                }

                $charges = DB::table('shop_register_charge')
                    ->join('charge_type', 'charge_type.id', '=', 'shop_register_charge.charge_type_id')
                    ->where('shop_register_charge.shop_register_id', $shopRegister->id)
                    ->where('shop_register_charge.automatic_application', 'Yes')
                    ->get();

                foreach ($charges as $charge) {
                    ShopOrderAppliedCharge::create([
                        'shop_order_id'     => $shopOrder->id,
                        'charge_type_id'    => $charge->charge_type_id,
                        'charge_type_name'  => $charge->charge_type_name,
                        'value_type'        => $charge->value_type,
                        'charge_value'      => $charge->charge_value,
                        'application_order' => $charge->application_order,
                        'tax_type'          => $charge->tax_type,
                        'applied_by'        => Auth::id(),
                        'applied_by_name'   => Auth::user()->name,
                        'last_log_by'       => Auth::id(),
                    ]);
                }
            }

            $this->recomputeShopOrder($shopOrder->id);

            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => 'Order saved successfully.',
                'shop_order_id' => $shopOrder->id,
                'order_number'  => $shopOrder->order_number,
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
            'shop_order_id' => ['required', 'integer', Rule::exists('shop_order', 'id')],
            'shop_order_item_id' => ['required', 'integer', Rule::exists('shop_order_item', 'id')],
            'action' => ['required', Rule::in(['increase', 'decrease', 'delete'])],
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
            // 1. LOCK THE PARENT ORDER FIRST to eliminate race conditions across endpoints
            $shopOrder = ShopOrder::query()->lockForUpdate()->findOrFail($validated['shop_order_id']);

            // 2. LOCK THE TARGET LINE ITEM
            $item = ShopOrderItem::query()
                ->where('shop_order_id', $shopOrder->id)
                ->whereKey($validated['shop_order_item_id'])
                ->lockForUpdate()
                ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Order item not found.',
                ]);
            }

            // 3. QUANTITY FLOOR FLOOD VALIDATION
            if (in_array($validated['action'], ['decrease', 'delete'])) {
                $minAllowedQuantity = DB::table('kitchen_ticket_item')
                    ->where('shop_order_item_id', $item->id)
                    ->whereIn('item_status', ['Preparing', 'Ready', 'Served'])
                    ->sum('quantity');

                $targetQuantity = ($validated['action'] === 'delete') ? 0 : ($item->quantity - 1);

                if ($targetQuantity < $minAllowedQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => sprintf(
                            "Cannot reduce quantity further. The kitchen has already processed %d unit(s) of this item.",
                            $minAllowedQuantity
                        ),
                    ]);
                }
            }

            // 4. EXECUTE QUANTITY MUTATION
            if ($validated['action'] === 'increase') {
                $item->quantity += 1;
            } elseif ($validated['action'] === 'decrease') {
                $item->quantity -= 1;
            }

            // 5. MUTATE LINE VALUES AND TAXES
            if ($validated['action'] === 'delete' || $item->quantity <= 0) {
                $item->quantity = 0;
                $item->line_subtotal = 0;
                $item->vatable_sales = 0;
                $item->vat_exempt_sales = 0;
                $item->zero_rated_sales = 0;
                $item->vat_amount = 0;
                $item->line_total = 0;
                $item->item_status = 'Cancelled';
                $item->cancelled_at = now();
                $item->cancelled_by = auth()->id();
                $item->cancelled_by_name = auth()->user()->name ?? 'System';
                $item->cancellation_reason = 'Cancelled by cashier';
                $item->save();
            } else {
                $item->line_subtotal = round($item->quantity * $item->unit_price, 2);
                $item->vatable_sales = 0;
                $item->vat_exempt_sales = 0;
                $item->zero_rated_sales = 0;
                $item->vat_amount = 0;

                if ($item->tax_classification === 'Vatable') {
                    $item->vatable_sales = round($item->line_subtotal / 1.12, 2);
                    $item->vat_amount = round($item->line_subtotal - $item->vatable_sales, 2);
                } elseif ($item->tax_classification === 'VAT Exempt') {
                    $item->vat_exempt_sales = $item->line_subtotal;
                } elseif ($item->tax_classification === 'Zero Rated') {
                    $item->zero_rated_sales = $item->line_subtotal;
                }

                $item->line_total = $item->line_subtotal;
                $item->save();
            }

            // 6. SYNC / INVALIDATE INCONSISTENT DISCOUNTS BEFORE RUNNING MAIN RECOMPUTE ENGINE
            // If a custom share discount exceeds the new total order amount, drop it or clear it.
            $newOrderSubtotal = (float) ShopOrderItem::query()
                ->where('shop_order_id', $shopOrder->id)
                ->where('item_status', '!=', 'Cancelled')
                ->sum('line_subtotal');

            // Wipe or force a recalculation flag if the applied absolute values break constraints
            DB::table('shop_order_applied_discount')
                ->where('shop_order_id', $shopOrder->id)
                ->where('custom_discountable_amount', '>', $newOrderSubtotal)
                ->delete(); // Or handle graceful recalculation inside your engine

            // 7. WRAP UP ENGINE PIPELINES
            $this->recomputeShopOrder($shopOrder->id);
            $order = $this->buildOrderPayload($shopOrder->id);

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
                'message' => 'Quantity Update Error: ' . $e->getMessage(),
            ], 422);
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
            'shop_order_id' => ['required', 'integer', Rule::exists('shop_order', 'id')],
            'discount_type_id' => ['required', 'integer', Rule::exists('discount_type', 'id')],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'custom_discountable_amount' => ['nullable', 'numeric', 'min:0'], 
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

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // PESSIMISTIC LOCK: Lock the master order state immediately
            $shopOrder = ShopOrder::query()->lockForUpdate()->findOrFail($validated['shop_order_id']);
            
            $currentSubtotal = (float) ShopOrderItem::query()
                ->where('shop_order_id', $shopOrder->id)
                ->where('item_status', '!=', 'Cancelled')
                ->sum('line_subtotal');

            // Guard Boundary Limit
            if (!empty($validated['custom_discountable_amount'])) {
                $inputAmount = round((float)$validated['custom_discountable_amount'], 2);
                $maxAllowed = round($currentSubtotal, 2);

                if ($inputAmount > $maxAllowed) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "The custom share amount (₱" . number_format($inputAmount, 2) . ") cannot exceed the total order subtotal (₱" . number_format($maxAllowed, 2) . ").",
                    ]);
                }
            }

            $shopRegister = DB::table('shop_register')->where('id', $shopOrder->shop_register_id)->first();
            $discountType = DiscountType::query()->findOrFail($validated['discount_type_id']);

            $alreadyApplied = ShopOrderAppliedDiscount::query()
                ->where('shop_order_id', $shopOrder->id)
                ->where('discount_type_id', $discountType->id)
                ->exists();

            if ($alreadyApplied) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Discount already applied.',
                ]);
            }

            $discountValue = $discountType->discount_value;
            if ($discountType->is_variable === 'Yes') {
                if (!isset($validated['discount_value'])) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Discount value is required.',
                    ]);
                }
                $discountValue = round((float) $validated['discount_value'], 2);
            }

            $customAmount = null;
            $calculatedDiscountAmount = 0;
            $calculatedVatExemptAmount = 0;

            if (
                $shopRegister && 
                $shopRegister->is_restaurant === 'Yes' && 
                !empty($validated['custom_discountable_amount']) && 
                (float)$validated['custom_discountable_amount'] > 0
            ) {
                $customAmount = round((float)$validated['custom_discountable_amount'], 2);
                $netOfVat = $customAmount / 1.12;
                $rateFactor = ($discountType->value_type === 'Percentage') ? ($discountValue / 100) : 1;
                
                if ($discountType->value_type === 'Percentage') {
                    $calculatedDiscountAmount = round($netOfVat * $rateFactor, 2);
                } else {
                    $calculatedDiscountAmount = round(min($discountValue, $netOfVat), 2);
                }

                if ($discountType->is_vat_exempt === 'Yes') {
                    // Keep this as the Net of VAT value to match the recompute lifecycle expectations
                    $calculatedVatExemptAmount = round($netOfVat, 2);
                }
            } else {
                if ($discountType->value_type === 'Percentage') {
                    $calculatedDiscountAmount = round(($currentSubtotal * $discountValue) / 100, 2);
                } else {
                    $calculatedDiscountAmount = round($discountValue, 2);
                }
                $calculatedVatExemptAmount = 0; 
            }

            ShopOrderAppliedDiscount::create([
                'shop_order_id' => $shopOrder->id,
                'discount_type_id' => $discountType->id,
                'discount_type_name' => $discountType->discount_type_name,
                'value_type' => $discountType->value_type,
                'discount_value' => $discountValue,
                'application_order' => $discountType->application_order,
                'is_vat_exempt' => $discountType->is_vat_exempt,
                'custom_discountable_amount' => $customAmount,
                'discount_amount' => $calculatedDiscountAmount, 
                'vat_exempt_amount' => $calculatedVatExemptAmount,
                'reference_number' => $validated['reference_number'] ?? null,
                'reference_name' => $validated['reference_name'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'applied_by' => Auth::id(),
                'applied_by_name' => Auth::user()->name,
                'last_log_by' => Auth::id(),
            ]);

            // Triggers calculation cycle
            $this->recomputeShopOrder($shopOrder->id);

            $appliedDiscounts = ShopOrderAppliedDiscount::query()->where('shop_order_id', $shopOrder->id)->get();
            $appliedIds = $appliedDiscounts->pluck('discount_type_id');

            $availableDiscounts = DB::table('shop_register_discount')
                ->join('discount_type', 'discount_type.id', '=', 'shop_register_discount.discount_type_id')
                ->where('shop_register_discount.shop_register_id', $shopOrder->shop_register_id)
                ->whereNotIn('discount_type.id', $appliedIds)
                ->select('discount_type.*')
                ->get();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Discount applied successfully.',
                'order' => $this->buildOrderPayload($shopOrder->id),
                'available_discounts' => $availableDiscounts,
                'applied_discounts' => $appliedDiscounts,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
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

    public function saveCustomer(Request $request)
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

                'customer_name' => [
                    'nullable',
                    'string',
                    'max:255',
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

        /*
        |--------------------------------------------------------------------------
        | LOCKED ORDERS
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $shopOrder->payment_status,
                ['Paid', 'Refunded']
            )
            ||
            in_array(
                $shopOrder->order_status,
                ['Cancelled', 'Voided']
            )
        ) {

            return response()->json([
                'success' => false,
                'message'
                    => 'Customer can no longer be modified.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $shopOrder->update([
            'customer_name'
                => $validated['customer_name'] ?: null,
        ]);

        return response()->json([
            'success' => true,

            'customer_name'
                => $shopOrder->customer_name,
        ]);
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
                    'shopRegister.warehouses',
                ])
                ->lockForUpdate() // Protects order status from race conditions
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

            [$totalPayment, $totalTendered] = $this->calculatePaymentTotals($validated['payments']);

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
                'order_status' => 'Completed',
                'paid_amount' => $totalPayment,
                'change_amount' => max(0, $totalTendered - $shopOrder->net_total),
                'balance_due' => 0,
                'completed_at' => now(),
                'completed_by' => Auth::id(),
                'completed_by_name' => Auth::user()?->name,
            ]);

            /*
            |--------------------------------------------------------------------------
            | INVENTORY DEDUCTION (Retail/Non-Restaurant Context Only)
            |--------------------------------------------------------------------------
            */
            if ($shopOrder->shopRegister?->is_restaurant === 'No') {
                
                // Extract from eager-loaded relationship to avoid sub-querying inside loop
                $warehouseIds = $shopOrder->shopRegister->warehouses->pluck('warehouse_id')->toArray();

                if (empty($warehouseIds)) {
                    throw new \Exception('No warehouse assigned to register.');
                }

                foreach ($shopOrder->items as $item) {
                    $product = Product::find($item->product_id);

                    if (!$product) {
                        continue;
                    }

                    // 🌟 REVISED: Pass directly to the recursive orchestrator method.
                    // It safely processes single items, BOM combos, and deep nested raw ingredients.
                    $this->deductInventory(
                        product: $product,
                        quantity: (float) $item->quantity,
                        referenceNumber: $shopOrder->order_number,
                        warehouseIds: $warehouseIds,
                        remarks: 'POS payment deduction'
                    );
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
                'message' => 'Payment Error: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function deductInventory(Product $product, float $quantity, string $referenceNumber, array $warehouseIds, ?string $remarks = null)
    {
        // Case A: The item tracks inventory directly itself
        if ($product->track_inventory === 'Yes') {
            $this->executeStockDeduction($product, $quantity, $referenceNumber, $warehouseIds, $remarks);
            return;
        }

        // Case B: Does not track inventory — resolve recipe/components down the tree
        $this->deductBomComponents($product->id, $quantity, $referenceNumber, $warehouseIds, $remarks);
    }

    private function deductBomComponents(int $productId, float $parentQuantity, string $referenceNumber, array $warehouseIds, ?string $remarks = null)
    {
        // Fetch immediate child items for the current item configuration level
        $bomItems = DB::table('product_bom')
            ->where('product_id', $productId)
            ->get();

        // If an item does not track inventory and has no recipe breakdown, drop out gracefully
        if ($bomItems->isEmpty()) {
            return;
        }

        foreach ($bomItems as $bomItem) {
            // Compound multiplier calculation (Parent Ordered Qty * Component Formula Qty)
            $requiredQuantity = $parentQuantity * $bomItem->quantity;

            $component = Product::find($bomItem->bom_product_id);
            
            if (!$component) {
                throw new \Exception("BOM Ingredient ID {$bomItem->bom_product_id} missing from catalog definition.");
            }

            if ($component->track_inventory === 'Yes') {
                // Base physical raw material found — apply direct table line stock deduction
                $this->executeStockDeduction($component, $requiredQuantity, $referenceNumber, $warehouseIds, $remarks);
            } else {
                // Nested BOM detected (e.g. Burger Combo -> Burger -> Patty) — drill down further
                $this->deductBomComponents($component->id, $requiredQuantity, $referenceNumber, $warehouseIds, $remarks);
            }
        }
    }
    
    private function executeStockDeduction(Product $product, float $quantity, string $referenceNumber, array $warehouseIds, ?string $remarks = null)
    {
        $query = StockLevel::query()
            ->with('inventoryLot')
            ->where('product_id', $product->id)
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('quantity', '>', 0);

        $flowStrategy = $product->inventory_flow ?? 'FIFO';

        switch ($flowStrategy) {
            case 'LIFO':
                $query->orderBy('id', 'desc'); 
                break;

            case 'FEFO':
                $query->join('inventory_lot', 'stock_level.inventory_lot_id', '=', 'inventory_lot.id')
                    ->select('stock_level.*') 
                    ->orderBy('inventory_lot.expiration_date', 'asc')
                    ->orderBy('stock_level.id', 'asc'); 
                break;

            case 'FIFO':
            case 'Manual':
        default:
                $query->orderBy('id', 'asc'); 
                break;
        }

        $stocks = $query->get();

        if ($stocks->isEmpty()) {
            throw new \Exception("No stock records found for product: {$product->product_name}.");
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

            // Atomic DB structural decrement
            $stock->decrement('quantity', $deduct);
            $stock->refresh();

            $stock->update([
                'stock_status' => match (true) {
                    $stock->quantity <= 0 => 'Out of Stock',
                    $stock->quantity <= ($product->reorder_level ?? 0) => 'Low Stock',
                    default => 'In Stock',
                },
            ]);

            $finalRemarks = sprintf("%s (%s)", $remarks ?? 'POS Payment Deduction', $flowStrategy);

            StockMovement::create([
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse_name ?? 'Unknown Warehouse',
                'inventory_lot_id' => $stock->inventory_lot_id,
                'movement_type' => 'SALE',
                'quantity' => $deduct,
                'reference_type' => 'Shop Order',
                'reference_number' => $referenceNumber,
                'remarks' => $finalRemarks,
                'last_log_by' => Auth::id() ?? 1,
            ]);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Insufficient physical stock available for ingredient [{$product->product_name}]. Missing {$remaining} items.");
        }
    }

    public function cancelOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_order_id' => [
                'required',
                'integer',
                Rule::exists('shop_order', 'id'),
            ],
            'void_reason' => [
                'required',
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

        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            // Fetch order and lock row for update to prevent race conditions
            $shopOrder = ShopOrder::query()
                ->lockForUpdate()
                ->findOrFail($validated['shop_order_id']);

            /*
            |--------------------------------------------------------------------------
            | GUARD: PRE-EXISTING IMMUTABLE FINALIZE BLOCKS
            |--------------------------------------------------------------------------
            */
            if (in_array($shopOrder->order_status, ['Cancelled', 'Voided'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This order has already been canceled or voided.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | RESTAURANT RULES: KITCHEN PREPARATION STATES AUDIT
            |--------------------------------------------------------------------------
            | Join across shop_register to see if this register functions as a kitchen printer unit.
            */
            $registerContext = DB::table('shop_register')
                ->where('id', $shopOrder->shop_register_id)
                ->first();

            if ($registerContext && $registerContext->is_restaurant === 'Yes') {
                
                // 1. Check if any master kitchen ticket header has advanced past "Queued" or "Cancelled"
                $hasActiveKitchenTickets = DB::table('kitchen_ticket')
                    ->where('shop_order_id', $shopOrder->id)
                    ->whereIn('ticket_status', ['Preparing', 'Ready', 'Completed'])
                    ->exists();

                // 2. Check if any individual line items on the kitchen screens are being worked on or served
                $hasActiveKitchenItems = DB::table('kitchen_ticket_item')
                    ->join('kitchen_ticket', 'kitchen_ticket_item.kitchen_ticket_id', '=', 'kitchen_ticket.id')
                    ->where('kitchen_ticket.shop_order_id', $shopOrder->id)
                    ->whereIn('kitchen_ticket_item.item_status', ['Preparing', 'Ready', 'Served'])
                    ->exists();

                if ($hasActiveKitchenTickets || $hasActiveKitchenItems) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot cancel order. Food preparation has already started or items have been served.',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | REVENUE STATE DECORATOR
            |--------------------------------------------------------------------------
            | If order was paid, status tracks as 'Voided' (requires cash count reconciliation).
            | If unpaid, tracking sets to 'Cancelled'.
            */
            $targetStatus = ($shopOrder->payment_status === 'Paid') ? 'Voided' : 'Cancelled';
            $timestamp = now();

            // Perform transaction updates
            $shopOrder->update([
                'order_status' => $targetStatus,
                'cancelled_at' => $timestamp,
                'cancelled_by' => Auth::id(),
                'cancelled_by_name' => Auth::user()?->name ?? 'System Cashier',
                'voided_by' => ($targetStatus === 'Voided') ? Auth::id() : null,
                'voided_by_name' => ($targetStatus === 'Voided') ? (Auth::user()?->name ?? 'System Cashier') : null,
                'void_reason' => $validated['void_reason'],
                'last_log_by' => Auth::id(),
            ]);

            // Cascade cancellation update down to individual order line items
            DB::table('shop_order_item')
                ->where('shop_order_id', $shopOrder->id)
                ->where('item_status', '!=', 'Cancelled')
                ->update([
                    'item_status' => 'Cancelled',
                    'cancelled_at' => $timestamp,
                    'cancelled_by' => Auth::id(),
                    'cancelled_by_name' => Auth::user()?->name ?? 'System Cashier',
                    'cancellation_reason' => $validated['void_reason'],
                    'last_log_by' => Auth::id(),
                    'updated_at' => $timestamp,
                ]);

            // Cascade update down to any open kitchen tickets linked to this order
            DB::table('kitchen_ticket')
                ->where('shop_order_id', $shopOrder->id)
                ->where('ticket_status', '!=', 'Cancelled')
                ->update([
                    'ticket_status' => 'Cancelled',
                    'cancelled_at' => $timestamp,
                    'last_log_by' => Auth::id(),
                    'updated_at' => $timestamp,
                ]);

            // Cascade update down to any open kitchen ticket line items
            DB::table('kitchen_ticket_item')
                ->join('kitchen_ticket', 'kitchen_ticket_item.kitchen_ticket_id', '=', 'kitchen_ticket.id')
                ->where('kitchen_ticket.shop_order_id', $shopOrder->id)
                ->where('kitchen_ticket_item.item_status', '!=', 'Cancelled')
                ->update([
                    'kitchen_ticket_item.item_status' => 'Cancelled',
                    'kitchen_ticket_item.cancelled_at' => $timestamp,
                    'kitchen_ticket_item.last_log_by' => Auth::id(),
                    'kitchen_ticket_item.updated_at' => $timestamp,
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Order successfully status marked as {$targetStatus}.",
                'order_status' => $targetStatus,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'An internal database error occurred while processing your cancellation.',
                'error' => $e->getMessage(),
            ], 500);
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

        // Fetch register configuration parameters to get the active workflow style layout
        $shopRegister = DB::table('shop_register')
            ->where('id', $shopOrder->shop_register_id)
            ->select('is_restaurant')
            ->first();

        $isRestaurant = ($shopRegister && $shopRegister->is_restaurant === 'Yes') ? 'Yes' : 'No';

        $appliedDiscounts = ShopOrderAppliedDiscount::query()
            ->where('shop_order_id', $shopOrder->id)
            ->get();

        $appliedIds = $appliedDiscounts->pluck('discount_type_id');

        $availableDiscounts = DB::table('shop_register_discount')
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
            ->select('discount_type.*')
            ->get();

        return response()->json([
            'success'             => true,
            'is_restaurant'       => $isRestaurant,
            'available_discounts' => $availableDiscounts,
            'applied_discounts'   => $appliedDiscounts,
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

    public function fetchPaymentMethods(Request $request)
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

    public function fetchHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_register_id' => [
                'required',
                'integer',
                Rule::exists('shop_register', 'id'),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $session = ShopRegisterSession::query()
            ->where('shop_register_id', $validated['shop_register_id'])
            ->whereNull('close_time')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active session found.',
            ]);
        }

        $orders = ShopOrder::query()
            ->where('shop_register_session_id', $session->id)
            ->latest()
            ->limit(200)
            ->get([
                'id',
                'order_number',
                'customer_name',
                'order_type',
                'order_status', // Already optimized and included in data dictionary responses
                'payment_status',
                'net_total',
                'floor_plan_name',
                'table_number',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'orders' => $orders,
        ]);
    }

    public function fetchOrder(Request $request)
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

        $order = ShopOrder::query()
            ->with(['items'])
            ->find($request->shop_order_id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER LOCK LOGIC
        |--------------------------------------------------------------------------
        */

        $isLocked =
            $order->payment_status === 'Paid'
            || $order->order_status === 'Cancelled'
            || $order->order_status === 'Voided'
            || $order->payment_status === 'Refunded';

        return response()->json([
            'success' => true,
            'order' => $order,
            'is_locked' => $isLocked,
            'lock_reason' => $this->getOrderLockReason($order),
        ]);
    }

    private function getOrderLockReason($order): ?string
    {
        if ($order->order_status === 'Cancelled') {
            return 'This order has been cancelled.';
        }

        if ($order->order_status === 'Voided') {
            return 'This order has been voided.';
        }

        if ($order->payment_status === 'Refunded') {
            return 'This order has been refunded.';
        }

        if ($order->payment_status === 'Paid') {
            return 'This order has already been paid.';
        }

        return null;
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
        | 1. FETCH MASTER ORDER & ACTIVE ITEMS
        |--------------------------------------------------------------------------
        */
        $shopOrder = ShopOrder::query()->findOrFail($shopOrderId);

        $items = ShopOrderItem::query()
            ->where('shop_order_id', $shopOrderId)
            ->where('item_status', '!=', 'Cancelled')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. RE-CALCULATE ITEM SUB-TOTALS & CLASSIFY BASE SALES
        |--------------------------------------------------------------------------
        */
        $subtotal              = 0;
        $baseVatableSubtotal   = 0;
        $baseVatExemptSubtotal = 0;
        $baseZeroRatedSubtotal = 0;

        foreach ($items as $item) {
            $quantity     = round((float) $item->quantity, 2);
            $unitPrice    = round((float) $item->unit_price, 2);
            $lineSubtotal = round($quantity * $unitPrice, 2);

            $vatableSales   = 0;
            $vatExemptSales = 0;
            $zeroRatedSales = 0;
            $vatAmount      = 0;

            if ($item->tax_classification === 'Vatable') {
                $vatableSales        = round($lineSubtotal / 1.12, 2);
                $vatAmount           = round($lineSubtotal - $vatableSales, 2);
                $baseVatableSubtotal += $lineSubtotal;
            } elseif ($item->tax_classification === 'VAT Exempt') {
                $vatExemptSales        = $lineSubtotal;
                $baseVatExemptSubtotal += $lineSubtotal;
            } elseif ($item->tax_classification === 'Zero Rated') {
                $zeroRatedSales        = $lineSubtotal;
                $baseZeroRatedSubtotal += $lineSubtotal;
            }

            $item->update([
                'line_subtotal'    => $lineSubtotal,
                'line_total'       => $lineSubtotal,
                'vatable_sales'    => $vatableSales,
                'vat_exempt_sales' => $vatExemptSales,
                'zero_rated_sales' => $zeroRatedSales,
                'vat_amount'       => $vatAmount,
            ]);

            $subtotal += $lineSubtotal;
        }

        $subtotal = round($subtotal, 2);

        /*
        |--------------------------------------------------------------------------
        | 3. CATEGORIZE APPLIED DISCOUNTS
        |--------------------------------------------------------------------------
        */
        $discounts = ShopOrderAppliedDiscount::query()
            ->where('shop_order_id', $shopOrderId)
            ->get();

        $vatExemptDiscounts = $discounts->filter(function ($d) {
            return $d->is_vat_exempt === 'Yes' 
                || str_contains(strtolower($d->discount_type_name), 'senior') 
                || str_contains(strtolower($d->discount_type_name), 'pwd');
        });

        $regularDiscounts = $discounts->reject(function ($d) {
            return $d->is_vat_exempt === 'Yes' 
                || str_contains(strtolower($d->discount_type_name), 'senior') 
                || str_contains(strtolower($d->discount_type_name), 'pwd');
        });

        $totalDiscountAmount   = 0;
        $grossVatExemptShifted = 0;

        /*
        |--------------------------------------------------------------------------
        | 4. COMPUTE SENIOR / PWD / VAT-EXEMPT DISCOUNTS FIRST
        |--------------------------------------------------------------------------
        */
        foreach ($vatExemptDiscounts as $discount) {
            $rawAmount = $discount->custom_discountable_amount;

            // Gamitin ang custom amount kung may pinalo; kung wala, huwag umapaw sa natitirang vatable subtotal
            if (!is_null($rawAmount) && (float)$rawAmount > 0) {
                $pool = round((float)$rawAmount, 2);
            } else {
                $pool = max(0, $baseVatableSubtotal - $grossVatExemptShifted);
            }

            $pool = min($pool, max(0, $baseVatableSubtotal - $grossVatExemptShifted));
            $grossVatExemptShifted += $pool;

            $netVatExempt = round($pool / 1.12, 2);

            $rate = $discount->value_type === 'Percentage' ? ($discount->discount_value ?: 20.00) : 0;
            $discAmount = ($discount->value_type === 'Percentage')
                ? round($netVatExempt * ($rate / 100), 2)
                : round(min($discount->discount_value, $netVatExempt), 2);

            $discount->update([
                'discount_amount'            => $discAmount,
                'custom_discountable_amount' => $pool > 0 ? $pool : null,
                'vat_exempt_amount'          => $netVatExempt,
            ]);

            $totalDiscountAmount += $discAmount;
        }

        /*
        |--------------------------------------------------------------------------
        | 5. COMPUTE REGULAR DISCOUNTS (REDUCING REMAINING VATABLE BASE)
        |--------------------------------------------------------------------------
        */
        $remainingVatableGross = max(0, $baseVatableSubtotal - $grossVatExemptShifted);

        foreach ($regularDiscounts as $discount) {
            if ($discount->value_type === 'Percentage') {
                $discAmount = round(($remainingVatableGross * $discount->discount_value) / 100, 2);
            } else {
                $discAmount = round(min($discount->discount_value, $remainingVatableGross), 2);
            }

            $discount->update([
                'discount_amount' => $discAmount,
            ]);

            $totalDiscountAmount += $discAmount;
        }

        /*
        |--------------------------------------------------------------------------
        | 6. FINAL TAX & SALES BREAKDOWN COMPUTATION
        |--------------------------------------------------------------------------
        */
        $regularDiscountTotal = $regularDiscounts->sum('discount_amount');
        $netVatableGross      = max(0, $remainingVatableGross - $regularDiscountTotal);

        $finalVatableSales   = round($netVatableGross / 1.12, 2);
        $finalVatAmount      = round($netVatableGross - $finalVatableSales, 2);
        $finalVatExemptSales = round($baseVatExemptSubtotal + ($grossVatExemptShifted / 1.12), 2);
        $finalZeroRatedSales = round($baseZeroRatedSubtotal, 2);

        /*
        |--------------------------------------------------------------------------
        | 7. PROCESS SERVICE CHARGES
        |--------------------------------------------------------------------------
        */
        $chargeTotal          = 0;
        $chargeVatAccumulator = 0;
        $charges             = ShopOrderAppliedCharge::query()->where('shop_order_id', $shopOrderId)->get();

        foreach ($charges as $charge) {
            $chargeAmount = ($charge->value_type === 'Percentage') 
                ? round(($subtotal * $charge->charge_value) / 100, 2) 
                : round($charge->charge_value, 2);

            $chargeVatableAmount = 0;
            $chargeVatAmount     = 0;

            if ($charge->tax_type === 'Vatable') {
                $chargeVatableAmount = round($chargeAmount / 1.12, 2);
                $chargeVatAmount     = round($chargeAmount - $chargeVatableAmount, 2);
            }

            $charge->update([
                'charge_rate'    => ($charge->value_type === 'Percentage') ? $charge->charge_value : 0,
                'charge_amount'  => $chargeAmount,
                'vatable_amount' => $chargeVatableAmount,
                'vat_amount'     => $chargeVatAmount,
            ]);

            $chargeTotal          += $chargeAmount;
            $chargeVatAccumulator += $chargeVatAmount;
        }

        $totalCombinedVat = round($finalVatAmount + $chargeVatAccumulator, 2);

        /*
        |--------------------------------------------------------------------------
        | 8. SAVE UPDATED ORDER MASTER RECORD
        |--------------------------------------------------------------------------
        */
        $grossTotal = round($subtotal + $chargeTotal, 2);
        $netTotal   = round(($subtotal - $totalDiscountAmount) + $chargeTotal, 2);

        $shopOrder->update([
            'total_items'      => (int) $items->count(),
            'total_quantity'   => round((float) $items->sum('quantity'), 2),
            'subtotal'         => $subtotal,
            'discount_total'   => round($totalDiscountAmount, 2),
            'charge_total'     => round($chargeTotal, 2),
            'vatable_sales'    => $finalVatableSales,
            'vat_exempt_sales' => $finalVatExemptSales,
            'zero_rated_sales' => $finalZeroRatedSales,
            'vat_amount'       => $totalCombinedVat,
            'gross_total'      => $grossTotal,
            'net_total'        => $netTotal,
        ]);
    }

    private function buildOrderPayload(int $shopOrderId): array
    {
        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        $shopOrder = ShopOrder::query()->findOrFail($shopOrderId);

        $shopOrder->refresh();

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        $items = ShopOrderItem::query()

            ->where('shop_order_id', $shopOrderId)

            ->where('quantity', '>', '0')

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

    public function generateOptions(Request $request)
    {
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $fileExtensions = DB::table('shop_order')
            ->select(['id', 'order_number'])
            ->where('payment_status', 'Paid')
            ->orderBy('order_number')
            ->get();

        $response = $response->concat(
            $fileExtensions->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->order_number,
            ])
        )->values();

        return response()->json($response);
    }
}
