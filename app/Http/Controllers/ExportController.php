<?php

namespace App\Http\Controllers;

use App\Exports\GenericTableExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function exportList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_name' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid table name.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tableName = $request->input('table_name');

        $dbName = DB::getDatabaseName();

        $fields = DB::table('information_schema.columns')
            ->select('column_name')
            ->where('table_schema', $dbName)
            ->where('table_name', $tableName)
            ->orderBy('ordinal_position')
            ->get();

        $response = $fields->map(fn ($row) => [
            'id'   => $row->column_name,
            'text' => $row->column_name,
        ])->values();

        return response()->json($response);
    }

    public function exportData(Request $request)
    {
        // 1) Validate inputs
        $validated = $request->validate([
            'export_to'     => ['required', 'in:csv,xlsx,pdf'],
            'export_id'     => ['required', 'array', 'min:1'],
            'export_id.*'   => ['integer'],
            'table_column'  => ['required', 'array', 'min:1'],
            'table_column.*'=> ['string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
            'table_name'    => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

        $exportTo     = $validated['export_to'];
        $ids          = $validated['export_id'];
        $columns      = $validated['table_column'];
        $tableName    = $validated['table_name'];

        // 2) Strongly recommended: whitelist allowed tables
        // Prevent users from exporting arbitrary tables.
        $allowedTables = [
            // 'users', 'orders', ...
        ];
        if (!empty($allowedTables) && !in_array($tableName, $allowedTables, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Table not allowed.',
            ], 403);
        }

        // 3) Ensure requested columns really exist in that table
        $dbName = DB::getDatabaseName();
        $existingColumns = DB::table('information_schema.columns')
            ->where('table_schema', $dbName)
            ->where('table_name', $tableName)
            ->pluck('column_name')
            ->all();

        $columns = array_values(array_intersect($columns, $existingColumns));
        if (empty($columns)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid columns selected.',
            ], 422);
        }

        // 4) Fetch data safely (no raw column SQL strings)
        // Assumes your table has an "id" primary key.
        $rows = DB::table($tableName)
            ->select($columns)
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        // 5) Filename
        $cleanTable = Str::slug($tableName, '_');
        $timestamp  = now()->format('Y-m-d_Hi');
        $baseName   = "{$cleanTable}_report_{$timestamp}";

        // 6) Export
        if ($exportTo === 'pdf') {
            // Create a simple PDF from a Blade view
            $pdf = Pdf::loadView('exports.table', [
                'tableName' => $tableName,
                'columns'   => $columns,
                'rows'      => $rows,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($baseName . '.pdf');
        }

        // CSV / XLSX via Laravel Excel
        $export = new GenericTableExport($columns, $rows);

        if ($exportTo === 'csv') {
            return Excel::download($export, $baseName . '.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);
        }

        // xlsx
        return Excel::download($export, $baseName . '.xlsx');
    }
}
