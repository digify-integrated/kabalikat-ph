<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NavigationMenuController extends Controller
{
    public function generateNavigationMenuOptions(Request $request)
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
