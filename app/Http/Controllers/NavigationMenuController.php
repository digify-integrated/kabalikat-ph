<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuRoute;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NavigationMenuController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'navigation_menu_id' => ['nullable', 'integer'],
            'navigation_menu_name' => ['required', 'string', 'max:255'],
            'app_id' => ['required', 'integer', Rule::exists('app', 'id')],
            'parent_id' => ['nullable', 'integer'],
            'navigation_menu_icon' => ['nullable', 'string', 'max:255'],
            'order_sequence' => ['nullable', 'integer', 'min:0'],
            'table_name' => ['nullable', 'string', 'max:100'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $appId = (int) $validated['app_id'];

        $appName = (string) App::query()
            ->whereKey($appId)
            ->value('app_name');

        $parentId = (int) $validated['parent_id'];

        $parentName = (string) NavigationMenu::query()
            ->whereKey($parentId)
            ->value('navigation_menu_name');

        $payload = [
            'navigation_menu_name' => $validated['navigation_menu_name'],
            'navigation_menu_icon' => $validated['navigation_menu_icon'],
            'app_id' => $appId,
            'app_name' => $appName,
            'parent_navigation_menu_id' => $parentId,
            'parent_navigation_menu_name' => $parentName,
            'database_table' => $validated['table_name'],
            'order_sequence' => $validated['order_sequence'] ?? 0,
            'last_log_by' => Auth::id(),
        ];

        $navigationMenuId = $validated['navigation_menu_id'] ?? null;

        if ($navigationMenuId && NavigationMenu::query()->whereKey($navigationMenuId)->exists()) {
            $navigationMenu = NavigationMenu::query()->findOrFail($navigationMenuId);
            $navigationMenu->update($payload);
        } else {
            $navigationMenu = NavigationMenu::query()->create($payload);
        }

        App::query()
            ->where('navigation_menu_id', $navigationMenu->id)
            ->update([
                'navigation_menu_name' => $navigationMenu->navigation_menu_name,
                'last_log_by' => Auth::id(),
            ]);

        NavigationMenu::query()
            ->where('parent_navigation_menu_id', $navigationMenu->id)
            ->update([
                'parent_navigation_menu_name' => $navigationMenu->navigation_menu_name,
                'last_log_by' => Auth::id(),
            ]);

        RolePermission::query()
            ->where('navigation_menu_id', $navigationMenu->id)
            ->update([
                'navigation_menu_name' => $navigationMenu->navigation_menu_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $navigationMenu->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The navigation menu has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function saveRoute(Request $request)
    {
        $validated = $request->validate([
            'navigation_menu_id'   => ['required', 'integer'],

            'index_view_file'      => ['nullable', 'string', 'max:255'],
            'index_js_file'        => ['nullable', 'string', 'max:255'],

            'new_view_file'        => ['nullable', 'string', 'max:255'],
            'new_js_file'          => ['nullable', 'string', 'max:255'],

            'details_view_file'    => ['nullable', 'string', 'max:255'],
            'details_js_file'      => ['nullable', 'string', 'max:255'],

            'import_view_file'     => ['nullable', 'string', 'max:255'],
            'import_js_file'       => ['nullable', 'string', 'max:255'],
        ]);

        $navigationMenuId = (int) $validated['navigation_menu_id'];
        $userId = Auth::id() ?? 1;

        $payloadByType = [
            'index' => [
                'view_file'    => $validated['index_view_file'] ?? null,
                'js_file'      => $validated['index_js_file'] ?? null,
                'last_log_by'  => $userId,
            ],
            'new' => [
                'view_file'    => $validated['new_view_file'] ?? null,
                'js_file'      => $validated['new_js_file'] ?? null,
                'last_log_by'  => $userId,
            ],
            'details' => [
                'view_file'    => $validated['details_view_file'] ?? null,
                'js_file'      => $validated['details_js_file'] ?? null,
                'last_log_by'  => $userId,
            ],
            'import' => [
                'view_file'    => $validated['import_view_file'] ?? null,
                'js_file'      => $validated['import_js_file'] ?? null,
                'last_log_by'  => $userId,
            ],
        ];

        DB::transaction(function () use ($navigationMenuId, $payloadByType) {
            foreach ($payloadByType as $routeType => $values) {
                NavigationMenuRoute::updateOrCreate(
                    [
                        'navigation_menu_id' => $navigationMenuId,
                        'route_type'         => $routeType,
                    ],
                    $values
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'The navigation menu route has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:navigation_menu,id'],
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
            $navigationMenu = NavigationMenu::query()->select(['id'])->findOrFail($detailId);

            $navigationMenu->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The navigation menu has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:navigation_menu,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            NavigationMenu::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected navigation menus have been deleted successfully',
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

        $navigationMenu = DB::table('navigation_menu')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$navigationMenu) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Navigation menu not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'navigationMenuName' => $navigationMenu->navigation_menu_name ?? null,
            'navigationMenuIcon' => $navigationMenu->navigation_menu_icon ?? null,
            'appId' => $navigationMenu->app_id ?? null,
            'parentNavigationMenuId' => $navigationMenu->parent_navigation_menu_id == 0 ? '' : $navigationMenu->parent_navigation_menu_id,
            'databaseTable' => $navigationMenu->database_table ?? null,
            'orderSequence' => $navigationMenu->order_sequence ?? null,
        ]);
    }

    public function fetchRouteDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'notExist' => false,
                'message' => $validator->errors()->first('detailId') ?? 'Validation failed',
            ]);
        }

        $validated = $validator->validated();

        $routes = DB::table('navigation_menu_route')
            ->where('navigation_menu_id', $validated['detailId']) // ✅ use the FK column
            ->whereIn('route_type', ['index', 'details', 'new', 'import'])
            ->get()
            ->keyBy('route_type');

        return response()->json([
            'success' => true,
            'notExist' => false,

            'indexViewFile'   => $routes['index']->view_file   ?? null,
            'indexJSFile'     => $routes['index']->js_file     ?? null,

            'detailsViewFile' => $routes['details']->view_file ?? null,
            'detailsJSFile'   => $routes['details']->js_file   ?? null,

            'newViewFile'     => $routes['new']->view_file     ?? null,
            'newJSFile'       => $routes['new']->js_file       ?? null,

            'importViewFile'  => $routes['import']->view_file  ?? null,
            'importJSFile'    => $routes['import']->js_file    ?? null,
        ]);

    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByApp = $request->input('filter_by_app');
        $filterByParentMenu = $request->input('filter_by_parent_menu');

        $navigationMenus = DB::table('navigation_menu')
        ->when(!empty($filterByApp), function ($q) use ($filterByApp) {
            $q->whereIn('app_id', $filterByApp);
        })
        ->when(!empty($filterByParentMenu), function ($q) use ($filterByParentMenu) {
            $q->whereIn('parent_navigation_menu_id', $filterByParentMenu);
        })
        ->orderBy('navigation_menu_name')
        ->get();

        $response = $navigationMenus->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $navigationMenuId = $row->id;
            $navigationMenuName = $row->navigation_menu_name;
            $appName = $row->app_name;
            $parentNavigationMenuName = $row->parent_navigation_menu_name;
            $orderSequence = $row->order_sequence;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $navigationMenuId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$navigationMenuId.'">
                    </div>
                ',
                'NAVIGATION_MENU' => $navigationMenuName,
                'APP_NAME' => $appName,
                'PARENT_NAME' => $parentNavigationMenuName,
                'ORDER_SEQUENCE' => $orderSequence,
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

        $apps = DB::table('navigation_menu')
            ->select(['id', 'navigation_menu_name'])
            ->orderBy('navigation_menu_name')
            ->get();

        $response = $response->concat(
            $apps->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->navigation_menu_name,
            ])
        )->values();

        return response()->json($response);
    }
}
