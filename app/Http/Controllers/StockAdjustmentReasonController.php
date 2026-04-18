<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustmentReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockAdjustmentReasonController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_adjustment_reason_id' => ['nullable', 'integer'],
            'stock_adjustment_reason_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $payload = [
            'stock_adjustment_reason_name' => $validated['stock_adjustment_reason_name'],
            'last_log_by' => Auth::id(),
        ];

        $stockAdjustmentReasonId = $validated['stock_adjustment_reason_id'] ?? null;

        if ($stockAdjustmentReasonId && StockAdjustmentReason::query()->whereKey($stockAdjustmentReasonId)->exists()) {
            $stockAdjustmentReason = StockAdjustmentReason::query()->findOrFail($stockAdjustmentReasonId);
            $stockAdjustmentReason->update($payload);
        } else {
            $stockAdjustmentReason = StockAdjustmentReason::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $stockAdjustmentReason->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment reason has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_adjustment_reason', 'id')],
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
            $stockAdjustmentReason = StockAdjustmentReason::query()->select(['id'])->findOrFail($detailId);

            $stockAdjustmentReason->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock adjustment reason has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_adjustment_reason', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            StockAdjustmentReason::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected stock adjustment reasons have been deleted successfully',
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

        $stockAdjustmentReason = DB::table('stock_adjustment_reason')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$stockAdjustmentReason) {
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
            'stockAdjustmentReasonName' => $stockAdjustmentReason->stock_adjustment_reason_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $stockAdjustmentReasons = DB::table('stock_adjustment_reason')
        ->orderBy('stock_adjustment_reason_name')
        ->get();

        $response = $stockAdjustmentReasons->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockAdjustmentReasonId = $row->id;
            $stockAdjustmentReasonName = $row->stock_adjustment_reason_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stockAdjustmentReasonId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stockAdjustmentReasonId.'">
                    </div>
                ',
                'STOCK_ADJUSTMENT_REASON' => $stockAdjustmentReasonName,
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

        $stockAdjustmentReasons = DB::table('stock_adjustment_reason')
            ->select(['id', 'stock_adjustment_reason_name'])
            ->orderBy('stock_adjustment_reason_name')
            ->get();

        $response = $response->concat(
            $stockAdjustmentReasons->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->stock_adjustment_reason_name,
            ])
        )->values();

        return response()->json($response);
    }
}
