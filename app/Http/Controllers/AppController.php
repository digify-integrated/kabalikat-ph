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
            ->groupBy('am.id', 'am.app_name', 'am.app_logo', 'am.app_description', 'am.order_sequence')
            ->orderBy('am.order_sequence')
            ->orderBy('am.app_name')
            ->get();


        return view('app.index', compact('apps', 'pageTitle'));
    }
}
