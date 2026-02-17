<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
   public function generateNavigationMenuRolePermissionTable(Request $request)
    {
        $navigationMenuId = (int) $request->input('navigation_menu_id');

        $apps = DB::table('role_permission')
        ->where('navigation_menu_id', $navigationMenuId)
        ->orderBy('navigation_menu_name')
        ->get();

        $response = $apps->map(function ($row)  {
            $appId = $row->id;
            $appName = $row->app_name;
            $appDescription = $row->app_description;

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$appId.'">
                    </div>
                ',
                'APP_NAME' => '
                    <div class="d-flex align-items-center">
                        <img src="'.$logoUrl.'" alt="app-logo" width="45" />
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$appName.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$appDescription.'</small>
                            </div>
                        </div>
                    </div>
                ',
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
