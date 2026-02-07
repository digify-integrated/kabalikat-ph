<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller
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

    public function base(Request $request, int $appModuleId, int $navigationMenuId)
    {
        $userId = Auth::id();

        $access = $request->user()->menuPermissions($navigationMenuId);

        $writePermission  = $access['write'];
        $createPermission = $access['create'];
        $deletePermission = $access['delete'];
        $importPermission = $access['import'];
        $exportPermission = $access['export'];
        $logsPermission   = $access['logs'];

        $navigationMenuInfo = DB::table('navigation_menu')
            ->where('id', $navigationMenuId)
            ->select('navigation_menu_name')
            ->first();

        $routeInfo = DB::table('navigation_menu_route')
            ->where('navigation_menu_id', $navigationMenuId)
            ->select('view_file', 'js_file')
            ->first();

        if (! $routeInfo) {
            abort(404);
        }

        $pageTitle = $navigationMenuInfo->navigation_menu_name;
        $viewFile = $routeInfo->view_file;
        $jsFile   = $routeInfo->js_file;

        return view($viewFile, compact(
            'pageTitle',
            'writePermission',
            'createPermission',
            'deletePermission',
            'importPermission',
            'exportPermission',
            'logsPermission',
            'jsFile',
            'appModuleId',
            'navigationMenuId'
        ));
    }

}
