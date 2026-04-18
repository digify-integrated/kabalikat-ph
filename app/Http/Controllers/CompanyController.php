<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\UploadSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => ['nullable', 'integer'],
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'city_id' => ['required', 'integer', Rule::exists('city', 'id')],
            'tax_id' => ['nullable', 'string'],
            'currency_id' => ['nullable', 'integer', Rule::exists('currency', 'id')],
            'phone' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string'],
            'email' => ['nullable', 'string', 'email'],
            'website' => ['nullable', 'string']
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

        $cityId = (int) $validated['city_id'] ?? null;
        $currencyId = (int) $validated['currency_id'];

        $cityDetails = City::query()->find($cityId);
        $cityName = $cityDetails?->city_name;
        $stateId = $cityDetails?->state_id;
        $stateName = $cityDetails?->state_name;
        $countryId = $cityDetails?->country_id;
        $countryName = $cityDetails?->country_name;

        $currencyName = (string) Currency::query()
            ->whereKey($currencyId)
            ->value('currency_name');

        $payload = [
            'company_name' => $validated['company_name'],
            'address' => $validated['address'] ?? null,
            'city_id' => $cityId,
            'city_name' => $cityName,
            'state_id' => $stateId,
            'state_name' => $stateName,
            'country_id' => $countryId,
            'country_name' => $countryName,
            'tax_id' => $validated['tax_id'] ?? null,
            'currency_id' => $currencyId,
            'currency_name' => $currencyName,
            'phone' => $validated['phone'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'last_log_by' => Auth::id(),
        ];

        $companyId = $validated['company_id'] ?? null;

        if ($companyId && Company::query()->whereKey($companyId)->exists()) {
            $company = Company::query()->findOrFail($companyId);
            $company->update($payload);
        } else {
            $company = Company::query()->create($payload);
        }

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $company->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The company has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function uploadCompanyLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('company', 'id')],
            'image'    => ['required', 'file'],
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
                'notExist' => false,
                'redirect_link' => $link,
                'message' => $validator->errors()->first() ?? 'Validation failed',
            ]);
        }

        $detailId = (int) $request->input('detailId');

        $company = Company::query()->findOrFail($detailId);

        $uploadSettingId = 5;

        $uploadSetting = UploadSetting::query()->findOrFail($uploadSettingId);

        $maxMb = (float) $uploadSetting->max_file_size;
        $maxKb = (int) round($maxMb * 1024);

        $allowedExt = $uploadSetting->uploadSettingFileExtensions()
            ->pluck('file_extension')
            ->map(fn ($e) => strtolower((string) $e))
            ->unique()
            ->values()
            ->all();

        $file = $request->file('image');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the file',
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedExt, true)) {
            return response()->json([
                'success' => false,
                'message' => 'The file uploaded is not supported',
            ]);
        }

        $sizeValidator = Validator::make($request->all(), [
            'image' => ['max:' . $maxKb],
        ]);

        if ($sizeValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The company logo exceeds the maximum allowed size of ' . $maxMb . ' MB',
            ]);
        }

        DB::transaction(function () use ($company, $file, $ext) {
            $existing = (string) ($company->company_logo ?? '');
            if ($existing !== '') {
                $path = ltrim($existing, '/');
                $path = Str::replaceFirst('storage/', '', $path);
                $path = Str::replaceFirst('app/public/', '', $path);
                $path = Str::replaceFirst('public/', '', $path);

                if ($path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }

            $fileName = Str::random(20);
            $fileNew  = $fileName . '.' . $ext;

            $relativePath = "company/{$company->id}/{$fileNew}";
            $file->storeAs("company/{$company->id}", $fileNew, 'public');

            $company->update([
                'company_logo' => $relativePath,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'The company logo has been updated successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', Rule::exists('company', 'id')],
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
            $company = Company::query()->select(['id', 'company_logo'])->findOrFail($detailId);

            $path = ltrim((string) $company->company_logo, '/');
            $path = Str::replaceFirst('storage/', '', $path);
            $path = Str::replaceFirst('app/public/', '', $path);
            $path = Str::replaceFirst('public/', '', $path);

            if ($path !== '') {
                Storage::disk('public')->delete($path);
            }

            $company->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The company has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', Rule::exists('company', 'id')],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $companies = Company::query()
                ->whereIn('id', $ids)
                ->get(['id', 'company_logo']);

            foreach ($companies as $company) {
                $path = ltrim((string) $company->company_logo, '/');

                $path = Str::replaceFirst('storage/', '', $path);
                $path = Str::replaceFirst('app/public/', '', $path);
                $path = Str::replaceFirst('public/', '', $path);

                if ($path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }

            Company::query()->whereIn('id', $ids)->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected companies have been deleted successfully',
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

        $company = DB::table('company')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$company) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'Company not found',
            ]);
        }

        $defaultLogo = asset('assets/media/default/default-company-logo.png');
        $path = trim((string) ($company->company_logo ?? ''));

        $logoUrl = $path !== '' && Storage::disk('public')->exists($path)
            ? Storage::url($path)
            : $defaultLogo;

        return response()->json([
            'success' => true,
            'notExist' => false,
            'companyName' => $company->company_name ?? null,
            'address' => $company->address ?? null,
            'cityId' => $company->city_id ?? null,
            'stateId' => $company->state_id ?? null,
            'countryId' => $company->country_id ?? null,
            'taxId' => $company->tax_id ?? null,
            'currencyId' => $company->currency_id ?? null,
            'phone' => $company->phone ?? null,
            'telephone' => $company->telephone ?? null,
            'email' => $company->email ?? null,
            'website' => $company->website ?? null,
            'companyLogo' => $logoUrl,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $companies = DB::table('company')
        ->orderBy('company_name')
        ->get();

        $response = $companies->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $companyId = $row->id;
            $companyName = $row->company_name;
            $address = $row->address . ', ' . $row->city_name . ', ' . $row->state_name . ', ' . $row->country_name;
            
            $defaultLogo = asset('assets/media/default/default-company-logo.png');

            $path = trim((string) ($row->company_logo ?? ''));

            $logoUrl = $path !== '' && Storage::disk('public')->exists($path)
                ? Storage::url($path)
                : $defaultLogo;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $companyId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$companyId.'">
                    </div>
                ',
                'COMPANY' => '
                    <div class="d-flex align-items-center">
                        <img src="'.$logoUrl.'" alt="company-logo" width="45" />
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$companyName.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$address.'</small>
                            </div>
                        </div>
                    </div>
                ',
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

        $companies = DB::table('company')
            ->select(['id', 'company_name'])
            ->orderBy('company_name')
            ->get();

        $response = $response->concat(
            $companies->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->company_name,
            ])
        )->values();

        return response()->json($response);
    }
}
