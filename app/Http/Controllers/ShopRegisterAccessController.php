<?php

namespace App\Http\Controllers;

use App\Models\ShopRegister;
use App\Models\ShopRegisterAccess;
use App\Models\ShopRegisterPaymentMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShopRegisterAccessController extends Controller
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

        $userAccounts = $request->input('user_account_id') ?? [];

        if (is_string($userAccounts)) {
            $userAccounts = explode(',', $userAccounts);
        }

        if (!empty($userAccounts)) {
            ShopRegisterAccess::query()
            ->where('shop_register_id', $shopRegisterId)
            ->delete();

            foreach ($userAccounts as $userAccountId) {
                $userAccount = User::find($userAccountId);

                if (!$userAccount) {
                    continue;
                }

                $userAccountName = (string) User::query()
                ->whereKey($userAccountId)
                ->value('name');

                $payload = [
                    'shop_register_id' => $shopRegisterId,
                    'shop_register_name' => $shopRegisterName,
                    'user_account_id' => $userAccountId,
                    'user_name' => $userAccountName,
                    'last_log_by' => Auth::id(),
                ];

                ShopRegisterAccess::query()->create($payload);
            }
        }        

        return response()->json([
            'success' => true,
            'message' => 'The access has been saved successfully',
        ]);
    }
}
