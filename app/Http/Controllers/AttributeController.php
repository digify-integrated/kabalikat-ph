<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attribute_id' => ['nullable', 'integer'],
            'attribute_name' => ['required', 'string', 'max:255'],
            'selection_type' => ['required', 'string', 'max:255']
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
            'attribute_name' => $validated['attribute_name'],
            'selection_type' => $validated['selection_type'],
            'last_log_by' => Auth::id(),
        ];

        $attributeId = $validated['attribute_id'] ?? null;

        if ($attributeId && Attribute::query()->whereKey($attributeId)->exists()) {
            $attribute = Attribute::query()->findOrFail($attributeId);
            $attribute->update($payload);
        } else {
            $attribute = Attribute::query()->create($payload);
        }

        AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->update([
                'attribute_name' => $attribute->attribute_name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $attribute->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The attribute has been saved successfully',
            'redirect_link' => $link,
        ]);
    }
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('attribute', 'id')],
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
            $attribute = Attribute::query()->select(['id'])->findOrFail($detailId);

            $attribute->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The attribute has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('attribute', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            Attribute::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected attributes have been deleted successfully',
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

        $attribute = DB::table('attribute')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$attribute) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Attribute not found',
            ]);
        }
        

        return response()->json([
            'success' => true,
            'notExist' => false,
            'attributeName' => $attribute->attribute_name ?? null,
            'selectionType' => $attribute->selection_type ?? null
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $attributes = DB::table('attribute')
        ->orderBy('attribute_name')
        ->get();

        $response = $attributes->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $attributeId = $row->id;
            $attributeName = $row->attribute_name;
            $selectionType = $row->selection_type;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $attributeId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$attributeId.'">
                    </div>
                ',
                'ATTRIBUTE' => $attributeName,
                'SELECTION_TYPE' => $selectionType,
                'LINK' => $link,
            ];
        })->values();

        return response()->json($response);
    }

    public function generateProductAttributeOptions(Request $request)
    {
        $productId = $request->input('product_id');
        $multiple = filter_var($request->input('multiple', false), FILTER_VALIDATE_BOOLEAN);

        $response = collect();

        if (!$multiple) {
            $response->push([
                'id'   => '',
                'text' => '--',
            ]);
        }

        $states = DB::table('attribute')
            ->select(['id', 'attribute_name'])
            ->whereNotIn('id', function ($query) use ($productId) {
                $query->select('attribute_id')
                    ->from('product_attribute')
                    ->where('product_id', $productId);
            })
            ->orderBy('attribute_name')
            ->get();

        $response = $response->concat(
            $states->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->attribute_name,
            ])
        )->values();

        return response()->json($response);
    }
}
