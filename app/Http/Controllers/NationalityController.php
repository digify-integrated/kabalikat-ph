<?php

namespace App\Http\Controllers;

use App\Models\Nationality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NationalityController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'nationality_id' => ['nullable', 'integer'],
            'nationality_name' => ['required', 'string', 'max:255'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $payload = [
            'nationality_name' => $validated['nationality_name'],
            'last_log_by' => Auth::id(),
        ];

        $nationalityId = $validated['nationality_id'] ?? null;

        if ($nationalityId && Nationality::query()->whereKey($nationalityId)->exists()) {
            $nationality = Nationality::query()->findOrFail($nationalityId);
            $nationality->update($payload);
        } else {
            $nationality = Nationality::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $nationality->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The nationality has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:nationality,id'],
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
            $nationality = Nationality::query()->select(['id'])->findOrFail($detailId);

            $nationality->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The nationality has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:nationality,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Nationality::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected nationalities have been deleted successfully',
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

        $nationality = DB::table('nationality')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$nationality) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Nationality not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'nationalityName' => $nationality->nationality_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $nationalities = DB::table('nationality')
        ->orderBy('nationality_name')
        ->get();

        $response = $nationalities->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $nationalityId = $row->id;
            $nationalityName = $row->nationality_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $nationalityId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$nationalityId.'">
                    </div>
                ',
                'NATIONALITY' => $nationalityName,
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

        $nationalities = DB::table('nationality')
            ->select(['id', 'nationality_name'])
            ->orderBy('nationality_name')
            ->get();

        $response = $response->concat(
            $nationalities->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->nationality_name,
            ])
        )->values();

        return response()->json($response);
    }
}
