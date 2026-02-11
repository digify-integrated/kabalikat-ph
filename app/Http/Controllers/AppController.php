<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AppController extends Controller
{
    public function generateAppTable(Request $request)
    {
        $appId = (int) $request->input('appId'); // from AJAX
        $navigationMenuId = (int) $request->input('navigationMenuId'); // from AJAX

        $apps = DB::table('app')
        ->orderBy('app_name')
        ->get();

        $response = $apps->map(function ($row) use ($appId, $navigationMenuId)  {
            $appId = $row->id;
            $appName = $row->app_name;
            $appDescription = $row->app_description;
            
            $defaultLogo = asset('assets/media/default/app-logo.png');

            $path = trim((string) ($row->app_logo ?? ''));

            $logoUrl = $path !== '' && Storage::disk('public')->exists($path)
                ? Storage::url($path)
                : $defaultLogo;

            $link = route('apps.details', [
                'appId' => $appId,
                'navigationMenuId' => $navigationMenuId,
                'details_id' => $appId,
            ]);

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
