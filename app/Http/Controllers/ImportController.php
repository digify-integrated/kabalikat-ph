<?php

namespace App\Http\Controllers;

use App\Models\UploadSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    public function importPreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_file' => ['required', 'file'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?? 'Validation failed.',
            ]);
        }

        $uploadSettingId = 3;
        $uploadSetting = UploadSetting::query()->findOrFail($uploadSettingId);

        $maxMb = (float) $uploadSetting->max_file_size;
        $maxKb = (int) round($maxMb * 1024);

        $allowedExt = $uploadSetting->uploadSettingFileExtensions()
            ->pluck('file_extension')
            ->map(fn ($e) => strtolower((string) $e))
            ->unique()
            ->values()
            ->all();

        $file = $request->file('import_file');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file.',
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedExt, true)) {
            return response()->json([
                'success' => false,
                'message' => 'The file uploaded is not supported.',
            ]);
        }

        // Laravel validator "max" for file is in kilobytes
        $sizeValidator = Validator::make($request->all(), [
            'import_file' => ['max:' . $maxKb],
        ]);

        if ($sizeValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The file exceeds the maximum allowed size of ' . $maxMb . ' MB.',
            ]);
        }

        // =========================
        // Revised here: CSV preview
        // =========================
        try {
            $path = $file->getRealPath();

            if (!$path || !is_readable($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to read the uploaded file.',
                ]);
            }

            [$headers, $rows] = $this->readCsvForPreview($path);

            if (empty($headers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The CSV file appears to be empty or invalid.',
                ]);
            }

            $html = $this->buildCsvPreviewTableHtml($headers, $rows);

            return response()->json([
                'success' => true,
                'message' => '',
                'data' => [
                    'preview' => $html,
                    'link' => $link,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process the file. ' . $e->getMessage(),
            ]);
        }

    }

    private function readCsvForPreview(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [[], []];
        }

        // If your CSV uses a different delimiter/enclosure, adjust here.
        $delimiter = ',';
        $enclosure = '"';
        $escape    = '\\';

        $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
        if (!is_array($headers)) {
            fclose($handle);
            return [[], []];
        }

        // Remove UTF-8 BOM on first header cell if present
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];
        }

        $headers = array_map(
            fn ($h) => trim((string) $h),
            $headers
        );

        $rows = [];
        $colCount = max(1, count($headers));

        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
            // Skip lines that are completely empty
            $allEmpty = true;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) {
                continue;
            }

            // Normalize to header count
            $row = array_slice(array_pad($row, $colCount, ''), 0, $colCount);
            $rows[] = $row;
        }

        fclose($handle);

        return [$headers, $rows];
    }

    /**
     * Build a <thead>/<tbody> HTML string (escaped) for preview.
     */
    private function buildCsvPreviewTableHtml(array $headers, array $rows): string
    {
        $escape = fn ($value) => e((string) $value);

        $html = '<thead class="text-center"><tr>';
        foreach ($headers as $header) {
            $html .= '<th class="fw-bold">' . $escape($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $escape($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>';

        return $html;
    }
}
