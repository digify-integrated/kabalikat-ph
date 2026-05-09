<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method_id' => ['nullable', 'integer'],
            'payment_method_name' => ['required', 'string', 'max:255'],
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
            'payment_method_name' => $validated['payment_method_name'],
            'last_log_by' => Auth::id(),
        ];

        $paymentMethodId = $validated['payment_method_id'] ?? null;

        if ($paymentMethodId && PaymentMethod::query()->whereKey($paymentMethodId)->exists()) {
            $paymentMethod = PaymentMethod::query()->findOrFail($paymentMethodId);
            $paymentMethod->update($payload);
        } else {
            $paymentMethod = PaymentMethod::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $paymentMethod->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The payment method has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('payment_method', 'id')],
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
            $paymentMethod = PaymentMethod::query()->select(['id'])->findOrFail($detailId);

            $paymentMethod->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The payment method has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('payment_method', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            PaymentMethod::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected payment methods have been deleted successfully',
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

        $paymentMethod = DB::table('payment_method')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$paymentMethod) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Payment method not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'paymentMethodName' => $paymentMethod->payment_method_name ?? null,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $paymentMethods = DB::table('payment_method')
        ->orderBy('payment_method_name')
        ->get();

        $response = $paymentMethods->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $paymentMethodId = $row->id;
            $paymentMethodName = $row->payment_method_name;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $paymentMethodId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$paymentMethodId.'">
                    </div>
                ',
                'PAYMENT_METHOD' => $paymentMethodName,
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

        $paymentMethods = DB::table('payment_method')
            ->select(['id', 'payment_method_name'])
            ->orderBy('payment_method_name')
            ->get();

        $response = $response->concat(
            $paymentMethods->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->payment_method_name,
            ])
        )->values();

        return response()->json($response);
    }
}
