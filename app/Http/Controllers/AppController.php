<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\NavigationMenu;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AppController extends Controller
{
    public function saveApp(Request $request)
    {
        $validated = $request->validate([
            'app_id' => ['nullable', 'integer'],
            'app_name' => ['required', 'string', 'max:255'],
            'app_description' => ['nullable', 'string'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'navigation_menu_id' => ['required', 'integer', Rule::exists('navigation_menu', 'id')],
            'order_sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $navigationMenuId = (int) $validated['navigation_menu_id'];

        $navigationMenu = NavigationMenu::query()->findOrFail($navigationMenuId);
        $navigationMenuName = (string) ($navigationMenu->navigation_menu_name ?? '');

        $payload = [
            'app_name' => $validated['app_name'],
            'app_description' => $validated['app_description'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'navigation_menu_id' => $navigationMenuId,
            'navigation_menu_name' => $navigationMenuName,
            'order_sequence' => $validated['order_sequence'] ?? 0,
        ];

        $appId = $validated['app_id'] ?? null;

        if ($appId && App::query()->whereKey($appId)->exists()) {
            $app = App::query()->findOrFail($appId);
            $app->update($payload);
        } else {
            $app = App::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $app->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The app has been saved successfully.',
            'redirect_link' => $link,
        ]);
    }

    public function fetchAppDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_id' => ['required', 'integer', 'min:1', 'exists:app,id'],
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('app_id')) {
                return response()->json([
                    'success'  => false,
                    'notExist' => true,
                ]);
            }
        }

        $validated = $validator->validated();

        $app = DB::table('app')
            ->where('id', $validated['app_id'])
            ->first();

        $defaultLogo = asset('assets/media/default/app-logo.png');
        $path = trim((string) ($app->app_logo ?? ''));

        $logoUrl = $path !== '' && Storage::disk('public')->exists($path)
            ? Storage::url($path)
            : $defaultLogo;

        return response()->json([
            'success'          => true,
            'appName'          => $app->app_name ?? null,
            'appDescription'   => $app->app_description ?? null,
            'navigationMenuId' => $app->navigation_menu_id ?? null,
            'appVersion'       => $app->app_version ?? null,
            'orderSequence'    => $app->order_sequence ?? null,
            'appLogo'          => $logoUrl,
        ]);
    }

    public function generateAppTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $apps = DB::table('app')
        ->orderBy('app_name')
        ->get();

        $response = $apps->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $appId = $row->id;
            $appName = $row->app_name;
            $appDescription = $row->app_description;
            
            $defaultLogo = asset('assets/media/default/app-logo.png');

            $path = trim((string) ($row->app_logo ?? ''));

            $logoUrl = $path !== '' && Storage::disk('public')->exists($path)
                ? Storage::url($path)
                : $defaultLogo;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
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

    public function deleteMultipleApp(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:app,id'], // <-- likely fix here
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $apps = App::query()
                ->whereIn('id', $ids)
                ->get(['id', 'app_logo']);

            foreach ($apps as $app) {
                $path = ltrim((string) $app->app_logo, '/');

                $path = Str::replaceFirst('storage/', '', $path);
                $path = Str::replaceFirst('app/public/', '', $path);
                $path = Str::replaceFirst('public/', '', $path);

                if ($path !== '') {
                    Storage::disk('public')->delete($path); // <-- use correct disk
                }
            }

            App::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected apps have been deleted successfully.',
        ]);
    }


}
