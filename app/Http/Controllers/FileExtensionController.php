<?php

namespace App\Http\Controllers;

use App\Models\FileExtension;
use App\Models\FileType;
use App\Models\UploadSettingFileExtension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class FileExtensionController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'file_extension_id' => ['nullable', 'integer'],
            'file_extension_name' => ['required', 'string', 'max:255'],
            'file_extension' => ['required', 'string', 'max:255'],
            'file_type_id' => ['integer'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $fileTypeId = (int) $validated['file_type_id'];

        $fileTypeName = (string) FileType::query()
            ->whereKey($fileTypeId)
            ->value('file_type_name');

        $payload = [
            'file_extension_name' => $validated['file_extension_name'],
            'file_extension' => $validated['file_extension'],
            'file_type_id' => $fileTypeId,
            'file_type_name' => $fileTypeName,
            'last_log_by' => Auth::id(),
        ];

        $fileExtensionId = $validated['file_extension_id'] ?? null;

        if ($fileExtensionId && FileExtension::query()->whereKey($fileExtensionId)->exists()) {
            $fileExtension = FileExtension::query()->findOrFail($fileExtensionId);
            $fileExtension->update($payload);
        } else {
            $fileExtension = FileExtension::query()->create($payload);
        }

        UploadSettingFileExtension::query()
            ->where('file_extension_id', $fileExtension->id)
            ->update([
                'file_extension_name' => $fileExtension->file_extension_name,
                'file_extension' => $fileExtension->file_extension,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $fileExtension->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The file extension has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:file_extension,id'],
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
            $fileExtension = FileExtension::query()->select(['id'])->findOrFail($detailId);

            $fileExtension->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The file extension has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:file_extension,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            FileExtension::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected file extensions have been deleted successfully',
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

        $fileExtension = DB::table('file_extension')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$fileExtension) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'File extension not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'fileExtensionName' => $fileExtension->file_extension_name ?? null,
            'fileExtension' => $fileExtension->file_extension ?? null,
            'fileTypeId' => $fileExtension->file_type_id ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByFileType = $request->input('filter_by_file_type');

        $fileExtensions = DB::table('file_extension')
        ->when(!empty($filterByFileType), function ($q) use ($filterByFileType) {
            $q->where('file_type_id', $filterByFileType);
        })
        ->orderBy('file_extension_name')
        ->get();

        $response = $fileExtensions->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $fileExtensionId = $row->id;
            $fileExtensionName = $row->file_extension_name;
            $fileTypeName = $row->file_type_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $fileExtensionId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$fileExtensionId.'">
                    </div>
                ',
                'FILE_EXTENSION' => $fileExtensionName,
                'FILE_TYPE' => $fileTypeName,
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

        $fileTypes = DB::table('file_extension')
            ->select(['id', 'file_extension_name'])
            ->orderBy('file_extension_name')
            ->get();

        $response = $response->concat(
            $fileTypes->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->file_extension_name,
            ])
        )->values();

        return response()->json($response);
    }
}
