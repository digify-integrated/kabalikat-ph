<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\ShopRegister;
use App\Models\ShopRegisterWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShopRegisterWarehouseController extends Controller
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

        $warehouses = $request->input('warehouse_id') ?? [];

        if (is_string($warehouses)) {
            $warehouses = explode(',', $warehouses);
        }

        if (!empty($warehouses)) {
            ShopRegisterWarehouse::query()
            ->where('shop_register_id', $shopRegisterId)
            ->delete();

            foreach ($warehouses as $warehouseId) {
                $warehouse = Warehouse::find($warehouseId);

                if (!$warehouse) {
                    continue;
                }

                $warehouseName = (string) Warehouse::query()
                ->whereKey($warehouseId)
                ->value('warehouse_name');

                $payload = [
                    'shop_register_id' => $shopRegisterId,
                    'shop_register_name' => $shopRegisterName,
                    'warehouse_id' => $warehouseId,
                    'warehouse_name' => $warehouseName,
                    'last_log_by' => Auth::id(),
                ];

                ShopRegisterWarehouse::query()->create($payload);
            }
        }        

        return response()->json([
            'success' => true,
            'message' => 'The warehouse has been saved successfully',
        ]);
    }
}
