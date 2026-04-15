<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CityController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'file_type_id' => ['nullable', 'integer'],
            'file_type_name' => ['required', 'string', 'max:255'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $payload = [
            'file_type_name' => $validated['file_type_name'],
            'last_log_by' => Auth::id(),
        ];

        $fileTypeId = $validated['file_type_id'] ?? null;

        if ($fileTypeId && FileType::query()->whereKey($fileTypeId)->exists()) {
            $fileType = FileType::query()->findOrFail($fileTypeId);
            $fileType->update($payload);
        } else {
            $fileType = FileType::query()->create($payload);
        }

        FileExtension::query()
            ->where('file_type_id', $fileType->id)
            ->update([
                'file_type_name' => $fileType->file_type_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $fileType->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The file type has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:file_type,id'],
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
            $fileType = FileType::query()->select(['id'])->findOrFail($detailId);

            $fileType->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The file type has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:file_type,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            FileType::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected file types have been deleted successfully',
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

        $fileType = DB::table('file_type')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$fileType) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'File type not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'fileTypeName' => $fileType->file_type_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $fileTypes = DB::table('file_type')
        ->orderBy('file_type_name')
        ->get();

        $response = $fileTypes->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $fileTypeId = $row->id;
            $fileTypeName = $row->file_type_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $fileTypeId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$fileTypeId.'">
                    </div>
                ',
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

        $fileTypes = DB::table('file_type')
            ->select(['id', 'file_type_name'])
            ->orderBy('file_type_name')
            ->get();

        $response = $response->concat(
            $fileTypes->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->file_type_name,
            ])
        )->values();

        return response()->json($response);
    }
}
