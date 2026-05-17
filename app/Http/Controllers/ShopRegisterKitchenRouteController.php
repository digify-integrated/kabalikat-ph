<?php

namespace App\Http\Controllers;

use App\Models\KitchenRoute;
use App\Models\ShopRegister;
use App\Models\ShopRegisterKitchenRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShopRegisterKitchenRouteController extends Controller
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

        $kitchenRoutes = $request->input('kitchen_route_id') ?? [];

        if (is_string($kitchenRoutes)) {
            $kitchenRoutes = explode(',', $kitchenRoutes);
        }

        if (!empty($kitchenRoutes)) {
            ShopRegisterKitchenRoute::query()
            ->where('shop_register_id', $shopRegisterId)
            ->delete();

            foreach ($kitchenRoutes as $kitchenRouteId) {
                $kitchenRoute = KitchenRoute::find($kitchenRouteId);

                if (!$kitchenRoute) {
                    continue;
                }

                $kitchenRouteName = (string) KitchenRoute::query()
                ->whereKey($kitchenRouteId)
                ->value('kitchen_route_name');

                $payload = [
                    'shop_register_id' => $shopRegisterId,
                    'shop_register_name' => $shopRegisterName,
                    'kitchen_route_id' => $kitchenRouteId,
                    'kitchen_route_name' => $kitchenRouteName,
                    'last_log_by' => Auth::id(),
                ];

                ShopRegisterKitchenRoute::query()->create($payload);
            }
        }        

        return response()->json([
            'success' => true,
            'message' => 'The kitchen route has been saved successfully',
        ]);
    }
}
