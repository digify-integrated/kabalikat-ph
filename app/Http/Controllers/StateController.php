<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StateController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'state_id' => ['nullable', 'integer'],
            'state_name' => ['required', 'string', 'max:255'],
            'country_id' => ['integer'],
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

        $countryId = (int) $validated['country_id'];

        $countryName = (string) Country::query()
            ->whereKey($countryId)
            ->value('country_name');

        $payload = [
            'state_name' => $validated['state_name'],
            'country_id' => $countryId,
            'country_name' => $countryName,
            'last_log_by' => Auth::id(),
        ];

        $stateId = $validated['state_id'] ?? null;

        if ($stateId && State::query()->whereKey($stateId)->exists()) {
            $state = State::query()->findOrFail($stateId);
            $state->update($payload);
        } else {
            $state = State::query()->create($payload);
        }

        City::query()
            ->where('state_id', $state->id)
            ->update([
                'state_name' => $state->state_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $state->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The state has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('state', 'id')],
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
            $state = State::query()->select(['id'])->findOrFail($detailId);

            $state->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The state has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('state', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            State::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected states have been deleted successfully',
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

        $state = DB::table('state')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$state) {
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
            'stateName' => $state->state_name ?? null,
            'state' => $state->state ?? null,
            'countryId' => $state->country_id ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByCountry = $request->input('filter_by_country');

        $states = DB::table('state')
        ->when(!empty($filterByCountry), fn($q) => $q->whereIn('country_id', $filterByCountry))
        ->orderBy('state_name')
        ->get();

        $response = $states->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stateId = $row->id;
            $stateName = $row->state_name;
            $countryName = $row->country_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stateId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stateId.'">
                    </div>
                ',
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

        $countries = DB::table('state')
            ->select(['id', 'state_name'])
            ->orderBy('state_name')
            ->get();

        $response = $response->concat(
            $countries->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->state_name,
            ])
        )->values();

        return response()->json($response);
    }
}
