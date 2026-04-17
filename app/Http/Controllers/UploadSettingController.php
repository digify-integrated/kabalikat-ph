<?php

namespace App\Http\Controllers;

use App\Models\FileExtension;
use App\Models\UploadSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UploadSettingController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'upload_setting_id' => ['nullable', 'integer'],
            'upload_setting_name' => ['required', 'string', 'max:255'],
            'upload_setting_description' => ['required', 'string', 'max:255'],
            'max_file_size' => ['integer'],
        ]);

        $fileExtensionIds = $request->input('file_extension_id') ?? [];

        if (empty($fileExtensionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select the file extensions you wish to assign to the upload setting',
            ]);
        }

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $payload = [
            'upload_setting_name' => $validated['upload_setting_name'],
            'upload_setting_description' => $validated['upload_setting_description'],
            'max_file_size' => $validated['max_file_size'],
            'last_log_by' => Auth::id(),
        ];

        $uploadSettingId = $validated['upload_setting_id'] ?? null;

        if ($uploadSettingId && UploadSetting::query()->whereKey($uploadSettingId)->exists()) {
            $uploadSetting = UploadSetting::query()->findOrFail($uploadSettingId);
            $uploadSetting->update($payload);
        } else {
            $uploadSetting = UploadSetting::query()->create($payload);
        }

        DB::table('upload_setting_file_extension')
            ->where('upload_setting_id', $uploadSetting->id)
            ->delete();

        $uploadSettingName = $uploadSetting->upload_setting_name ?? '';

        foreach ($fileExtensionIds as $fileExtensionId) {
            $fileExtension = FileExtension::find($fileExtensionId);

            if (!$fileExtension) {
                continue;
            }

            DB::table('upload_setting_file_extension')->insert([
                'upload_setting_id' => $uploadSetting->id,
                'upload_setting_name' => $uploadSettingName,
                'file_extension_id' => $fileExtension->id,
                'file_extension_name' => $fileExtension->file_extension_name,
                'file_extension' => $fileExtension->file_extension,
                'last_log_by' => Auth::id()
            ]);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $uploadSetting->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The upload setting has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:upload_setting,id'],
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
            DB::table('upload_setting_file_extension')
            ->where('upload_setting_id', $detailId)
            ->delete();

            $uploadSetting = UploadSetting::query()->select(['id'])->findOrFail($detailId);

            $uploadSetting->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The upload setting has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:upload_setting,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            DB::table('upload_setting_file_extension')
            ->where('upload_setting_id', $ids)
            ->delete();

            UploadSetting::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected upload settings have been deleted successfully',
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

        $uploadSetting = DB::table('upload_setting')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$uploadSetting) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Upload setting not found',
            ]);
        }

        $fileExtensionIds = DB::table('upload_setting_file_extension')
            ->where('upload_setting_id', $uploadSetting->id)
            ->pluck('file_extension_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'notExist' => false,
            'uploadSettingName' => $uploadSetting->upload_setting_name ?? null,
            'uploadSettingDescription' => $uploadSetting->upload_setting_description ?? null,
            'maxFileSize' => $uploadSetting->max_file_size ?? null,
            'fileExtensionId' => $fileExtensionIds,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByFileType = $request->input('filter_by_file_type');

        $uploadSettings = DB::table('upload_setting')
        ->when(!empty($filterByFileType), fn($q) => $q->whereIn('file_type_id', $filterByFileType))
        ->orderBy('upload_setting_name')
        ->get();

        $response = $uploadSettings->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $uploadSettingId = $row->id;
            $uploadSettingName = $row->upload_setting_name;
            $uploadSettingDescription = $row->upload_setting_description;
            $maxFileSize = $row->max_file_size;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $uploadSettingId,
            ]);

            $fileExtensions = DB::table('upload_setting_file_extension')
                ->where('upload_setting_id', $uploadSettingId)
                ->pluck('file_extension');

            $badges = $fileExtensions
            ->chunk(5)
            ->map(function ($group) {
                return '<div class="mb-1">' .
                    $group->map(function ($ext) {
                        return '<span class="badge bg-primary me-1">'.e($ext).'</span>';
                    })->implode('') .
                '</div>';
            })
            ->implode('');

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$uploadSettingId.'">
                    </div>
                ',
                'UPLOAD_SETTING' => '
                    <div class="d-flex align-items-center">
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$uploadSettingName.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$uploadSettingDescription.'</small>
                            </div>
                        </div>
                    </div>',
                'MAX_FILE_SIZE' => $maxFileSize . ' kb',
                'ALLOWED_FILE_EXTENSION' => $badges,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }
}
