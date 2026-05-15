<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ShopRegister;
use App\Models\ShopRegisterSession;
use App\Models\ShopSessionDenomination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShopRegisterController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_register_id' => ['nullable', 'integer'],
            'shop_register_name' => ['required', 'string'],
            'company_id' => ['required', 'integer', Rule::exists('company', 'id')],
            'is_restaurant' => ['required', 'string'],
            'shop_register_status' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $companyId = (int) $validated['company_id'];

        $companyName = (string) Company::query()
            ->whereKey($companyId)
            ->value('company_name');        

        $payload = [
            'shop_register_name' => $validated['shop_register_name'],
            'company_id' => $companyId,
            'company_name' => $companyName,
            'is_restaurant' => $validated['is_restaurant'] ?? 'No',
            'shop_register_status' => $validated['shop_register_status'] ?? 'Active',
            'last_log_by' => Auth::id(),
        ];   

        $shopRegister = isset($validated['shop_register_id'])
            ? ShopRegister::query()->find($validated['shop_register_id'])
            : null;

        if ($shopRegister) {
            $shopRegister->update($payload);
        } else {
            $shopRegister = ShopRegister::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $shopRegister->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The shop register has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function saveSession(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'shop_register_id' => [
                'required',
                'integer',
                Rule::exists('shop_register', 'id')
            ],

            'session' => [
                'required',
                Rule::in(['open', 'close'])
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:1000'
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

            /*
            |--------------------------------------------------------------------------
            | SHOP REGISTER
            |--------------------------------------------------------------------------
            */

            $shopRegister = ShopRegister::query()
                ->findOrFail($validated['shop_register_id']);

            $sessionType = strtoupper($validated['session']);

            $remarks = $validated['remarks'] ?? null;

            $userAccountName = (string) User::query()
                ->whereKey(Auth::id())
                ->value('name');


            /*
            |--------------------------------------------------------------------------
            | COMPUTE TOTAL
            |--------------------------------------------------------------------------
            */

            $grandTotal = 0;

            $denominations = [];

            foreach ($request->all() as $key => $value) {

                if (!str_starts_with($key, 'open_')) {
                    continue;
                }

                $quantity = (int) $value;

                if ($quantity <= 0) {
                    continue;
                }

                $denomination = (float) str_replace('_', '.', str_replace('open_', '', $key));

                $lineTotal = $denomination * $quantity;

                $grandTotal += $lineTotal;

                $denominations[] = [
                    'denomination_value' => $denomination,
                    'quantity' => $quantity,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | OPEN SESSION
            |--------------------------------------------------------------------------
            */

            if ($sessionType === 'OPEN') {

                $shopRegisterSession = ShopRegisterSession::query()->create([

                    'shop_register_id' => $shopRegister->id,
                    'shop_register_name' => $shopRegister->shop_register_name,

                    'open_time' => now(),
                    'open_amount' => $grandTotal,
                    'open_remarks' => $remarks,

                    'open_user_account_id' => Auth::id(),
                    'open_user_name' => $userAccountName,

                    'last_log_by' => Auth::id(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | UPDATE REGISTER STATUS
                |--------------------------------------------------------------------------
                */

                $shopRegister->update([
                    'register_status' => 'Open',
                    'last_log_by' => Auth::id(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | SAVE DENOMINATIONS
                |--------------------------------------------------------------------------
                */

                foreach ($denominations as $row) {

                    ShopSessionDenomination::query()->create([

                        'shop_register_session_id' => $shopRegisterSession->id,

                        'count_type' => 'Open',

                        'denomination_value' => $row['denomination_value'],
                        'quantity' => $row['quantity'],

                        'last_log_by' => Auth::id(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CLOSE SESSION
            |--------------------------------------------------------------------------
            */

            if ($sessionType === 'CLOSE') {

                /*
                |--------------------------------------------------------------------------
                | FIND ACTIVE SESSION
                |--------------------------------------------------------------------------
                */

                $shopRegisterSession = ShopRegisterSession::query()
                    ->where('shop_register_id', $shopRegister->id)
                    ->whereNull('close_time')
                    ->latest('id')
                    ->first();

                if (!$shopRegisterSession) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'No active session found.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE SESSION
                |--------------------------------------------------------------------------
                */

                $shopRegisterSession->update([

                    'close_time' => now(),
                    'close_amount' => $grandTotal,
                    'close_remarks' => $remarks,

                    'close_user_account_id' => Auth::id(),
                    'close_user_name' => $userAccountName,

                    'last_log_by' => Auth::id(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | UPDATE REGISTER STATUS
                |--------------------------------------------------------------------------
                */

                $shopRegister->update([
                    'register_status' => 'Closed',
                    'last_log_by' => Auth::id(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | SAVE DENOMINATIONS
                |--------------------------------------------------------------------------
                */

                foreach ($denominations as $row) {

                    ShopSessionDenomination::query()->create([

                        'shop_register_session_id' => $shopRegisterSession->id,

                        'count_type' => 'Close',

                        'denomination_value' => $row['denomination_value'],
                        'quantity' => $row['quantity'],

                        'last_log_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $sessionType === 'OPEN'
                    ? 'Register opened successfully.'
                    : 'Register closed successfully.',
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

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('shop_register', 'id')],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('detailId') ?? 'Validation failed',
            ]);
        }

        $detailId = (int) $validator->validated()['detailId'];

        DB::transaction(function () use ($detailId) {
            $shopRegister = ShopRegister::query()->select(['id'])->findOrFail($detailId);

            $shopRegister->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The shop register has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('shop_register', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            ShopRegister::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected shop registers have been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('shop_register', 'id')],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'notExist' => false,
                'message' => $validator->errors()->first('detailId') ?? 'Validation failed',
            ]);
        }

        $validated = $validator->validated();

        $shopRegister = DB::table('shop_register')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$shopRegister) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Shop register not found',
            ]);
        }

        $warehouseIds = DB::table('shop_register_warehouse')
            ->where('shop_register_id', $shopRegister->id)
            ->pluck('warehouse_id')
            ->toArray();

        $floorPlanIds = DB::table('shop_register_floor_plan')
            ->where('shop_register_id', $shopRegister->id)
            ->pluck('floor_plan_id')
            ->toArray();

        $paymentMethodIds = DB::table('shop_register_payment_method')
            ->where('shop_register_id', $shopRegister->id)
            ->pluck('payment_method_id')
            ->toArray();

        $accessIds = DB::table('shop_register_access')
            ->where('shop_register_id', $shopRegister->id)
            ->pluck('user_account_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'notExist' => false,
            'shopRegisterName' => $shopRegister->shop_register_name ?? null,
            'companyId' => $shopRegister->company_id ?? null,
            'isRestaurant' => $shopRegister->is_restaurant ?? 'No',
            'shopRegisterStatus' => $shopRegister->shop_register_status ?? 'Yes',
            'warehouseId' => $warehouseIds,
            'floorPlanId' => $floorPlanIds,
            'paymentMethodId' => $paymentMethodIds,
            'accessId' => $accessIds,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByCompany = $request->input('filter_by_company');
        $filterByIsRestaurant = $request->input('filter_by_is_restaurant');
        $filterByStatus = $request->input('filter_by_status');

        $shopRegisters = DB::table('shop_register')
        ->when(!empty($filterByCompany), fn($q) => $q->whereIn('company_id', $filterByCompany))
        ->when(!empty($filterByIsRestaurant), function ($q) use ($filterByIsRestaurant) {
            $q->where('is_restaurant', $filterByIsRestaurant);
        })
        ->when(!empty($filterByStatus), function ($q) use ($filterByStatus) {
            $q->where('shop_register_status', $filterByStatus);
        })
        ->orderBy('shop_register_name')
        ->get();

        $response = $shopRegisters->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $shopRegisterId = $row->id;
            $shopRegisterName = $row->shop_register_name;
            $companyName = $row->company_name;
            $isRestaurant = $row->is_restaurant ?? 'No';
            $shopRegisterStatus = $row->shop_register_status;
            $class = $shopRegisterStatus === 'Active' ? 'success' : 'danger';
            $activeBadge = "<span class=\"badge badge-light-{$class}\">{$shopRegisterStatus}</span>";

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $shopRegisterId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$shopRegisterId.'">
                    </div>
                ',
                'SHOP_REGISTER' => $shopRegisterName,
                'COMPANY' => $companyName,
                'IS_RESTAURANT' => $isRestaurant,
                'STATUS' => $activeBadge,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }

    public function generateRegister(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $shopRegisters = DB::table('shop_register')
            ->where('shop_register_status', 'Active')
            ->whereIn('id', function ($query) {
                $query->select('shop_register_id')
                    ->from('shop_register_access')
                    ->where('user_account_id', Auth::id());
            })
            ->get();

        $response = $shopRegisters->map(function ($register) use ($pageAppId, $pageNavigationMenuId) {

            $latestSession = DB::table('shop_register_session')
                ->where('shop_register_id', $register->id)
                ->latest('id')
                ->first();

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $register->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | INITIAL STATE
            |--------------------------------------------------------------------------
            */
            if (!$latestSession) {

                return [
                    'id' => $register->id,
                    'state' => 'INITIAL',

                    'shop_register_name' => $register->shop_register_name,
                    'company_name' => $register->company_name,

                    'link' => $link,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | OPEN STATE
            |--------------------------------------------------------------------------
            */
            if ($register->register_status === 'Open') {

                $duration = Carbon::parse($latestSession->open_time)->diffForHumans(now(), true);

                return [
                    'id' => $register->id,
                    'state' => 'OPEN',

                    'shop_register_name' => $register->shop_register_name,
                    'company_name' => $register->company_name,

                    'open_time' => Carbon::parse($latestSession->open_time)
                        ->format('d M Y · h:i A'),

                    'open_amount' => number_format($latestSession->open_amount, 2),

                    'duration' => $duration,

                    'sales_count' => 0,

                    'link' => $link,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | CLOSED STATE
            |--------------------------------------------------------------------------
            */
            $openTime = Carbon::parse($latestSession->open_time);
            $closeTime = Carbon::parse($latestSession->close_time);

            return [
                'id' => $register->id,
                'state' => 'CLOSED',

                'shop_register_name' => $register->shop_register_name,
                'company_name' => $register->company_name,

                'open_time' => $openTime->format('d M Y · h:i A'),
                'close_time' => $closeTime->format('d M Y · h:i A'),

                'open_amount' => number_format($latestSession->open_amount, 2),
                'close_amount' => number_format($latestSession->close_amount, 2),

                'duration' => $openTime->diffForHumans($closeTime, true),

                'sales_count' => 0,

                'link' => $link,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }
}
