<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => ['nullable', 'integer'],
            'city_name' => ['required', 'string', 'max:255'],
            'state_id' => ['integer'],
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

        $stateId = (int) $validated['state_id'];

        $stateDetails = State::query()->find($stateId);
        $stateName = $stateDetails?->state_name;
        $countryId = $stateDetails?->country_id;
        $countryName = $stateDetails?->country_name;

        $payload = [
            'city_name' => $validated['city_name'],
            'state_id' => $stateId,
            'state_name' => $stateName,
            'country_id' => $countryId,
            'country_name' => $countryName,
            'last_log_by' => Auth::id(),
        ];

        $cityId = $validated['city_id'] ?? null;

        if ($cityId && City::query()->whereKey($cityId)->exists()) {
            $city = City::query()->findOrFail($cityId);
            $city->update($payload);
        } else {
            $city = City::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $city->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The city has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('city', 'id')],
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
            $city = City::query()->select(['id'])->findOrFail($detailId);

            $city->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The city has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('city', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            City::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected citys have been deleted successfully',
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

        $city = DB::table('city')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$city) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'File extension not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'cityName' => $city->city_name ?? null,
            'city' => $city->city ?? null,
            'stateId' => $city->state_id ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByState = $request->input('filter_by_state');
        $filterByCountry = $request->input('filter_by_country');

        $cities = DB::table('city')
        ->when(!empty($filterByState), fn($q) => $q->whereIn('state_id', $filterByState))
        ->when(!empty($filterByCountry), fn($q) => $q->whereIn('country_id', $filterByCountry))
        ->orderBy('city_name')
        ->get();

        $response = $cities->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $cityId = $row->id;
            $cityName = $row->city_name;
            $stateName = $row->state_name;
            $countryName = $row->country_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $cityId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$cityId.'">
                    </div>
                ',
                'CITY' => $cityName,
                'STATE' => $stateName,
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

        $states = DB::table('city')
            ->select(['id', 'city_name', 'state_name', 'country_name'])
            ->orderBy('city_name')
            ->get();

        $response = $response->concat(
            $states->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->city_name . ', ' . $row->state_name . ', ' . $row->country_name,
            ])
        )->values();

        return response()->json($response);
    }
}
