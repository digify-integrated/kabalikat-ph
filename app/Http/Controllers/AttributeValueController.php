<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class AttributeValueController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attribute_id' => ['required', 'integer', Rule::exists('attribute', 'id')],
            'attribute_value' => ['required', 'string', 'max:255']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $attributeId = $validated['attribute_id'] ?? null;

        $attributeName = (string) Attribute::query()
            ->whereKey($attributeId)
            ->value('attribute_name');

        $payload = [
            'attribute_value' => $validated['attribute_value'],
            'attribute_id' => $attributeId,
            'attribute_name' => $attributeName,
            'last_log_by' => Auth::id(),
        ];

        AttributeValue::query()->create($payload);

        return response()->json([
            'success' => true,
            'message' => 'The attribute value has been saved successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referenceId' => ['required', 'integer', 'min:1', Rule::exists('attribute_value', 'id')],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('referenceId') ?? 'Validation failed',
            ]);
        }

        $referenceId = (int) $validator->validated()['referenceId'];

        DB::transaction(function () use ($referenceId) {
            $attributeValue = AttributeValue::query()->select(['id'])->findOrFail($referenceId);

            $attributeValue->delete();
        });        

        return response()->json([
            'success' => true,
            'message' => 'The attribute value has been deleted successfully',
        ]);
    }

    public function generateTable(Request $request)
    {
        $attributeId = (int) $request->input('attribute_id');
        $pageNavigationNenuId = (int) $request->input('page_navigation_menu_id');

        $attributeValues = DB::table('attribute_value')
        ->where('attribute_id', $attributeId)
        ->orderBy('attribute_value')
        ->get();

        $writeAccess = $request->user()->menuPermissions($pageNavigationNenuId)['write'] ?? 0;
        $logsAccess = $request->user()->menuPermissions($pageNavigationNenuId)['logs'] ?? 0;

        $response = $attributeValues->map(function ($row) use ($writeAccess, $logsAccess)  {
            $attributeValueId = $row->id;
            $attributeValue = $row->attribute_value;

            $deleteButton = '';
            if($writeAccess > 0){
                $deleteButton = '<button class="btn btn-icon btn-light btn-active-light-danger delete-attribute-value" data-reference-id="' . $attributeValueId . '" title="Delete Attribute Value">
                                    <i class="ki-outline ki-trash fs-3 m-0 fs-5"></i>
                                </button>';
            }

            $logNotes = '';
            if($logsAccess > 0){
                $logNotes = '<button class="btn btn-icon btn-light btn-active-light-primary view-attribute-value-log-notes" data-reference-id="' . $attributeValueId . '" data-bs-toggle="modal" data-bs-target="#log-notes-modal" title="View Log Notes">
                                <i class="ki-outline ki-shield-search fs-3 m-0 fs-5"></i>
                            </button>';
            }

            return [
                'VALUE' => $attributeValue,
                'ACTION' => '<div class="d-flex justify-content-end gap-3">
                                '. $logNotes .'
                                '. $deleteButton .'
                            </div>'
            ];
        })->values();

        return response()->json($response);
    }
}
