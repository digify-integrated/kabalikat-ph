<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AppRenderController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $pageTitle = 'Apps';

        $apps = DB::table('app as am')
            ->select([
                'am.id as app_id',
                'am.app_name',
                DB::raw('MIN(nm.id) as navigation_menu_id'),
                'am.app_logo',
                'am.app_description',
                'am.app_version',
                'am.order_sequence',
            ])
            ->join('navigation_menu as nm', 'nm.app_id', '=', 'am.id')
            ->whereExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('role_permission as rp')
                    ->whereColumn('rp.navigation_menu_id', 'nm.id')
                    ->where('rp.read_access', 1)
                    ->whereIn('rp.role_id', function ($q2) use ($userId) {
                        $q2->select('role_id')
                            ->from('role_user_account')
                            ->where('user_account_id', $userId);
                    });
            })
            ->groupBy('am.id', 'am.app_name', 'am.app_logo', 'am.app_description', 'am.app_version', 'am.order_sequence')
            ->orderBy('am.order_sequence')
            ->orderBy('am.app_name')
            ->get();

        return view('app.index', compact('apps', 'pageTitle'));
    }

    private function resolveViewAndJs(int $navigationMenuId, string $routeType): object
    {
        $routeInfo = DB::table('navigation_menu_route')
            ->where([
                'route_type' => $routeType,
                'navigation_menu_id' => $navigationMenuId,
            ])
            ->select('view_file', 'js_file')
            ->first();

        if (! $routeInfo) {
            abort(404);
        }

        return $routeInfo;
    }

    private function resolveMenuMeta(int $navigationMenuId): object
    {
        $menu = DB::table('navigation_menu')
            ->where('id', $navigationMenuId)
            ->select('navigation_menu_name', 'navigation_menu_icon', 'database_table')
            ->first();

        if (! $menu) {
            abort(404);
        }

        return $menu;
    }

    private function permissions(Request $request, int $navigationMenuId): array
    {
        $access = $request->user()->menuPermissions($navigationMenuId);

        return [
            'writePermission'  => (int)($access['write']  ?? 0),
            'createPermission' => (int)($access['create'] ?? 0),
            'deletePermission' => (int)($access['delete'] ?? 0),
            'importPermission' => (int)($access['import'] ?? 0),
            'exportPermission' => (int)($access['export'] ?? 0),
            'logsPermission'   => (int)($access['logs']   ?? 0),
        ];
    }

    private function renderMenuRoute(
        Request $request,
        int $appId,
        int $navigationMenuId,
        string $routeType,
        array $extra = []
    ) {
        $menu = $this->resolveMenuMeta($navigationMenuId);
        $routeInfo = $this->resolveViewAndJs($navigationMenuId, $routeType);
        $perms = $this->permissions($request, $navigationMenuId);

        $iconClass = $menu->navigation_menu_icon ?: 'ki-outline ki-abstract-26';

        return view($routeInfo->view_file, array_merge($perms, [
            'pageTitle' => (string) $menu->navigation_menu_name,
            'iconClass' => $iconClass,
            'jsFile' => $routeInfo->js_file,
            'appId' => $appId,
            'navigationMenuId' => $navigationMenuId,
            'databaseTable' => $menu->database_table ?: '',
        ], $extra));
    }

    public function base(Request $request, int $appId, int $navigationMenuId)
    {
        return $this->renderMenuRoute($request, $appId, $navigationMenuId, 'index');
    }

    public function new(Request $request, int $appId, int $navigationMenuId)
    {
        return $this->renderMenuRoute($request, $appId, $navigationMenuId, 'new', [
            'pageTitleSuffix' => ' - New',
            'isNew' => true,
        ]);
    }

    public function details(Request $request, int $appId, int $navigationMenuId, int $details_id)
    {
        return $this->renderMenuRoute($request, $appId, $navigationMenuId, 'details', [
            'pageTitleSuffix' => ' - Details',
            'detailsId' => $details_id,
            'isDetails' => true,
        ]);
    }

    public function import(Request $request, int $appId, int $navigationMenuId)
    {
        $perms = $this->permissions($request, $navigationMenuId);

        if (($perms['importPermission'] ?? 0) <= 0) {
            abort(404);
        }

        return $this->renderMenuRoute($request, $appId, $navigationMenuId, 'import', [
            'pageTitleSuffix' => ' - Import',
            'isImport' => true,
        ]);
    }
}
