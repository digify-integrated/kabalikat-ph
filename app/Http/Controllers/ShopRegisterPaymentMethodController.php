<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\ShopRegister;
use App\Models\ShopRegisterPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShopRegisterPaymentMethodController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_register_id' => ['required', 'integer', Rule::exists('shop_register', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $shopRegisterId = $validated['shop_register_id'] ?? null;

        $shopRegisterName = (string) ShopRegister::query()
            ->whereKey($shopRegisterId)
            ->value('shop_register_name');

        $paymentMethods = $request->input('payment_method_id') ?? [];

        if (is_string($paymentMethods)) {
            $paymentMethods = explode(',', $paymentMethods);
        }

        if (!empty($paymentMethods)) {
            ShopRegisterPaymentMethod::query()
            ->where('shop_register_id', $shopRegisterId)
            ->delete();

            foreach ($paymentMethods as $paymentMethodId) {
                $paymentMethod = PaymentMethod::find($paymentMethodId);

                if (!$paymentMethod) {
                    continue;
                }

                $paymentMethodName = (string) PaymentMethod::query()
                ->whereKey($paymentMethodId)
                ->value('payment_method_name');

                $payload = [
                    'shop_register_id' => $shopRegisterId,
                    'shop_register_name' => $shopRegisterName,
                    'payment_method_id' => $paymentMethodId,
                    'payment_method_name' => $paymentMethodName,
                    'last_log_by' => Auth::id(),
                ];

                ShopRegisterPaymentMethod::query()->create($payload);
            }
        }        

        return response()->json([
            'success' => true,
            'message' => 'The payment method has been saved successfully',
        ]);
    }
}
