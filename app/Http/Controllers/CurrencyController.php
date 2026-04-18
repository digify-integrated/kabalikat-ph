<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => ['nullable', 'integer'],
            'currency_name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:255'],
            'shorthand' => ['required', 'string', 'max:255']
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

        $payload = [
            'currency_name' => $validated['currency_name'],
            'symbol' => $validated['symbol'],
            'shorthand' => $validated['shorthand'],
            'last_log_by' => Auth::id(),
        ];

        $currencyId = $validated['currency_id'] ?? null;

        if ($currencyId && Currency::query()->whereKey($currencyId)->exists()) {
            $currency = Currency::query()->findOrFail($currencyId);
            $currency->update($payload);
        } else {
            $currency = Currency::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $currency->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The currency has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('currency', 'id')],
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
            $currency = Currency::query()->select(['id'])->findOrFail($detailId);

            $currency->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The currency has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('currency', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Currency::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected nationalities have been deleted successfully',
        ]);
    }

    public function fetchDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1'],
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

        $currency = DB::table('currency')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$currency) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Currency not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'currencyName' => $currency->currency_name ?? null,
            'symbol' => $currency->symbol ?? null,
            'shorthand' => $currency->shorthand ?? null
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $nationalities = DB::table('currency')
        ->orderBy('currency_name')
        ->get();

        $response = $nationalities->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $currencyId = $row->id;
            $currencyName = $row->currency_name;
            $symbol = $row->symbol;
            $shorthand = $row->shorthand;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $currencyId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$currencyId.'">
                    </div>
                ',
                'CURRENCY' => $currencyName,
                'SYMBOL' => $symbol,
                'SHORTHAND' => $shorthand,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
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

        $nationalities = DB::table('currency')
            ->select(['id', 'currency_name'])
            ->orderBy('currency_name')
            ->get();

        $response = $response->concat(
            $nationalities->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->currency_name,
            ])
        )->values();

        return response()->json($response);
    }
}
