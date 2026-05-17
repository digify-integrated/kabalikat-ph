<?php

namespace App\Http\Controllers;

use App\Models\KitchenRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KitchenRouteController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kitchen_route_id' => ['nullable', 'integer'],
            'kitchen_route_name' => ['required', 'string', 'max:255'],
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
            'kitchen_route_name' => $validated['kitchen_route_name'],
            'last_log_by' => Auth::id(),
        ];

        $kitchenRouteId = $validated['kitchen_route_id'] ?? null;

        if ($kitchenRouteId && KitchenRoute::query()->whereKey($kitchenRouteId)->exists()) {
            $kitchenRoute = KitchenRoute::query()->findOrFail($kitchenRouteId);
            $kitchenRoute->update($payload);
        } else {
            $kitchenRoute = KitchenRoute::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $kitchenRoute->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The kitchen route has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('kitchen_route', 'id')],
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
            $kitchenRoute = KitchenRoute::query()->select(['id'])->findOrFail($detailId);

            $kitchenRoute->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The kitchen route has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('kitchen_route', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            KitchenRoute::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected kitchen routes have been deleted successfully',
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

        $kitchenRoute = DB::table('kitchen_route')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$kitchenRoute) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Kitchen route not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'kitchenRouteName' => $kitchenRoute->kitchen_route_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $kitchenRoutes = DB::table('kitchen_route')
        ->orderBy('kitchen_route_name')
        ->get();

        $response = $kitchenRoutes->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $kitchenRouteId = $row->id;
            $kitchenRouteName = $row->kitchen_route_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $kitchenRouteId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$kitchenRouteId.'">
                    </div>
                ',
                'KITCHEN_ROUTE' => $kitchenRouteName,
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

        $kitchenRoutes = DB::table('kitchen_route')
            ->select(['id', 'kitchen_route_name'])
            ->orderBy('kitchen_route_name')
            ->get();

        $response = $response->concat(
            $kitchenRoutes->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->kitchen_route_name,
            ])
        )->values();

        return response()->json($response);
    }
}
