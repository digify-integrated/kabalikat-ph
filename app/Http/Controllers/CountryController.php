<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CountryController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'country_id' => ['nullable', 'integer'],
            'country_name' => ['required', 'string', 'max:255'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $payload = [
            'country_name' => $validated['country_name'],
            'last_log_by' => Auth::id(),
        ];

        $countryId = $validated['country_id'] ?? null;

        if ($countryId && Country::query()->whereKey($countryId)->exists()) {
            $country = Country::query()->findOrFail($countryId);
            $country->update($payload);
        } else {
            $country = Country::query()->create($payload);
        }

        State::query()
            ->where('country_id', $country->id)
            ->update([
                'country_name' => $country->country_name,
                'last_log_by' => Auth::id(),
            ]);

        City::query()
            ->where('country_id', $country->id)
            ->update([
                'country_name' => $country->country_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $country->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The country has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:country,id'],
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
            $country = Country::query()->select(['id'])->findOrFail($detailId);

            $country->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The country has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:country,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Country::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected countries have been deleted successfully',
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

        $country = DB::table('country')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$country) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Country not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'countryName' => $country->country_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $countries = DB::table('country')
        ->orderBy('country_name')
        ->get();

        $response = $countries->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $countryId = $row->id;
            $countryName = $row->country_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $countryId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$countryId.'">
                    </div>
                ',
                'COUNTRY' => $countryName,
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

        $countries = DB::table('country')
            ->select(['id', 'country_name'])
            ->orderBy('country_name')
            ->get();

        $response = $response->concat(
            $countries->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->country_name,
            ])
        )->values();

        return response()->json($response);
    }
}
