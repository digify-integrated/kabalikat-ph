<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\NavigationMenu;
use App\Models\UploadSetting;
use Illuminate\Support\Facades\Auth;
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

        $navigationMenuName = (string) NavigationMenu::query()
            ->whereKey($navigationMenuId)
            ->value('navigation_menu_name');

        $payload = [
            'app_name' => $validated['app_name'],
            'app_description' => $validated['app_description'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'navigation_menu_id' => $navigationMenuId,
            'navigation_menu_name' => $navigationMenuName,
            'order_sequence' => $validated['order_sequence'] ?? 0,
            'last_log_by' => Auth::id(),
        ];

        $appId = $validated['app_id'] ?? null;

        if ($appId && App::query()->whereKey($appId)->exists()) {
            $app = App::query()->findOrFail($appId);
            $app->update($payload);
        } else {
            $app = App::query()->create($payload);
        }

        NavigationMenu::query()
            ->where('app_id', $app->id)
            ->update([
                'app_name' => $app->app_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $app->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The app has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function uploadAppLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:app,id'],
            'image'    => ['required', 'file'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'notExist' => false,
                'redirect_link' => $link,
                'message' => $validator->errors()->first() ?? 'Validation failed',
            ]);
        }

        $detailId = (int) $request->input('detailId');

        $app = App::query()->findOrFail($detailId);

        $uploadSettingId = 1;

        $uploadSetting = UploadSetting::query()->findOrFail($uploadSettingId);

        $maxMb = (float) $uploadSetting->max_file_size;
        $maxKb = (int) round($maxMb * 1024);

        $allowedExt = $uploadSetting->uploadSettingFileExtensions()
            ->pluck('file_extension')
            ->map(fn ($e) => strtolower((string) $e))
            ->unique()
            ->values()
            ->all();

        $file = $request->file('image');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file',
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedExt, true)) {
            return response()->json([
                'success' => false,
                'message' => 'The file uploaded is not supported',
            ]);
        }

        $sizeValidator = Validator::make($request->all(), [
            'image' => ['max:' . $maxKb],
        ]);

        if ($sizeValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The app logo exceeds the maximum allowed size of ' . $maxMb . ' MB',
            ]);
        }

        DB::transaction(function () use ($app, $file, $ext) {
            $existing = (string) ($app->app_logo ?? '');
            if ($existing !== '') {
                $path = ltrim($existing, '/');
                $path = Str::replaceFirst('storage/', '', $path);
                $path = Str::replaceFirst('app/public/', '', $path);
                $path = Str::replaceFirst('public/', '', $path);

                if ($path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }

            $fileName = Str::random(20);
            $fileNew  = $fileName . '.' . $ext;

            $relativePath = "app/{$app->id}/{$fileNew}";
            $file->storeAs("app/{$app->id}", $fileNew, 'public');

            $app->update([
                'app_logo' => $relativePath,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'The app logo has been updated successfully',
        ]);
    }

    public function fetchAppDetails(Request $request)
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

        $app = DB::table('app')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$app) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'App not found',
            ]);
        }

        $defaultLogo = asset('assets/media/default/app-logo.png');
        $path = trim((string) ($app->app_logo ?? ''));

        $logoUrl = $path !== '' && Storage::disk('public')->exists($path)
            ? Storage::url($path)
            : $defaultLogo;

        return response()->json([
            'success'          => true,
            'notExist'         => false,
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

    public function deleteApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:app,id'],
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
            $app = App::query()->select(['id', 'app_logo'])->findOrFail($detailId);

            $path = ltrim((string) $app->app_logo, '/');
            $path = Str::replaceFirst('storage/', '', $path);
            $path = Str::replaceFirst('app/public/', '', $path);
            $path = Str::replaceFirst('public/', '', $path);

            if ($path !== '') {
                Storage::disk('public')->delete($path);
            }

            $app->delete();
        });        

        $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

        return response()->json([
            'success' => true,
            'redirect_link' => $link,
            'message' => 'The app has been deleted successfully',
        ]);
    }

    public function deleteMultipleApp(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:app,id'],
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
                    Storage::disk('public')->delete($path);
                }
            }

            App::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected apps have been deleted successfully',
        ]);
    }

    public function generateAppOptions(Request $request)
    {
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $apps = DB::table('app')
            ->select(['id', 'app_name'])
            ->orderBy('app_name')
            ->get();

        $response = $response->concat(
            $apps->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->app_name,
            ])
        )->values();

        return response()->json($response);
    }
}
