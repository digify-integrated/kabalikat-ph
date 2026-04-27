<?php

namespace App\Http\Controllers;

use App\Models\StockTransferReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StockTransferReasonController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_transfer_reason_id' => ['nullable', 'integer'],
            'stock_transfer_reason_name' => ['required', 'string', 'max:255'],
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
            'stock_transfer_reason_name' => $validated['stock_transfer_reason_name'],
            'last_log_by' => Auth::id(),
        ];

        $stockTransferReasonId = $validated['stock_transfer_reason_id'] ?? null;

        if ($stockTransferReasonId && StockTransferReason::query()->whereKey($stockTransferReasonId)->exists()) {
            $stockTransferReason = StockTransferReason::query()->findOrFail($stockTransferReasonId);
            $stockTransferReason->update($payload);
        } else {
            $stockTransferReason = StockTransferReason::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $stockTransferReason->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer reason has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('stock_transfer_reason', 'id')],
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
            $stockTransferReason = StockTransferReason::query()->select(['id'])->findOrFail($detailId);

            $stockTransferReason->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The stock transfer reason has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('stock_transfer_reason', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            StockTransferReason::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected stock transfer reasons have been deleted successfully',
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

        $stockTransferReason = DB::table('stock_transfer_reason')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$stockTransferReason) {
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
            'stockTransferReasonName' => $stockTransferReason->stock_transfer_reason_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $stockTransferReasons = DB::table('stock_transfer_reason')
        ->orderBy('stock_transfer_reason_name')
        ->get();

        $response = $stockTransferReasons->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $stockTransferReasonId = $row->id;
            $stockTransferReasonName = $row->stock_transfer_reason_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $stockTransferReasonId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$stockTransferReasonId.'">
                    </div>
                ',
                'STOCK_TRANSFER_REASON' => $stockTransferReasonName,
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

        $stockTransferReasons = DB::table('stock_transfer_reason')
            ->select(['id', 'stock_transfer_reason_name'])
            ->orderBy('stock_transfer_reason_name')
            ->get();

        $response = $response->concat(
            $stockTransferReasons->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->stock_transfer_reason_name,
            ])
        )->values();

        return response()->json($response);
    }
}
