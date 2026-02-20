<?php

namespace App\Http\Controllers;

use App\Models\RoleUserAccount;
use App\Models\UploadSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => ['nullable', 'integer'],
            'user_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255'],
            'password'   => [$request->filled('user_id') ? 'nullable' : 'required', 'string', 'min:8'],
        ]);

        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        $payload = [
            'name'    => $validated['user_name'],
            'email'        => $validated['email'] ?? null,
            'last_log_by'  => Auth::id(),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }        

        $userId = $validated['user_id'] ?? null;

        if ($userId && User::query()->whereKey($userId)->exists()) {
            $user = User::query()->findOrFail($userId);
            $user->update($payload);
        } else {
            $user = User::query()->create($payload);
        }

        RoleUserAccount::query()
            ->where('user_account_id', $user->id)
            ->update([
                'user_name' => $user->name,
                'last_log_by' => Auth::id(),
            ]);

        $link = route('apps.details', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
            'details_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The user has been saved successfully',
            'redirect_link' => $link,
        ]);
    }

    public function uploadProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:users,id'],
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

        $user = User::query()->findOrFail($detailId);

        $uploadSettingId = 4;

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
                'message' => 'The profile picture exceeds the maximum allowed size of ' . $maxMb . ' MB',
            ]);
        }

        DB::transaction(function () use ($user, $file, $ext) {
            $existing = (string) ($user->profile_picture ?? '');
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

            $relativePath = "user/{$user->id}/{$fileNew}";
            $file->storeAs("user/{$user->id}", $fileNew, 'public');

            $user->update([
                'profile_picture' => $relativePath,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'The profile picture has been updated successfully',
        ]);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:users,id'],
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

        if(Auth::id() == $detailId){
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete the user account you are currently logged in as',
            ]);
        }

        DB::transaction(function () use ($detailId) {
            $user = User::query()->select(['id', 'profile_picture'])->findOrFail($detailId);

            $path = ltrim((string) $user->profile_picture, '/');
            $path = Str::replaceFirst('storage/', '', $path);
            $path = Str::replaceFirst('app/public/', '', $path);
            $path = Str::replaceFirst('public/', '', $path);

            if ($path !== '') {
                Storage::disk('public')->delete($path);
            }

            $user->delete();
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The user has been deleted successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:app,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $authId = Auth::id();

            $ids = array_values(array_diff($ids, [$authId])); // remove current user id
            if (empty($ids)) return;

            $users = User::query()
                ->whereIn('id', $ids)
                ->get(['id', 'profile_picture']);

            foreach ($users as $user) {
                $existing = (string) ($user->profile_picture ?? '');
                if ($existing === '') continue;

                $path = ltrim($existing, '/');
                $path = Str::replaceFirst('storage/', '', $path);
                $path = Str::replaceFirst('app/public/', '', $path);
                $path = Str::replaceFirst('public/', '', $path);

                if ($path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }

            User::query()->whereIn('id', $ids)->delete();
        });


        return response()->json([
            'success' => true,
            'message' => 'The selected users have been deleted successfully',
        ]);
    }

    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:users,id'],
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
            $user = User::query()->select(['id'])->findOrFail($detailId);

            $user->update(['status' => 'Active']);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The user has been activated successfully',
            'redirect_link' => $link,
        ]);
    }

    public function activateMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:app,id'],
        ]);

        $ids = $validated['selected_id'];

        DB::transaction(function () use ($ids) {
            $users = User::query()
                ->whereIn('id', $ids)
                ->get(['id']);

            User::query()->whereIn('id', $ids)->update(['status' => 'Active']);
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected users have been deleted successfully',
        ]);
    }

    public function deactivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => ['required', 'integer', 'min:1', 'exists:users,id'],
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

        if(Auth::id() == $detailId){
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate the user account you are currently logged in as',
            ]);
        }

        DB::transaction(function () use ($detailId) {
            $user = User::query()->select(['id'])->findOrFail($detailId);

            $user->update(['status' => 'Inactive']);
        });        

        $link = route('apps.base', [
            'appId' => $pageAppId,
            'navigationMenuId' => $pageNavigationMenuId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The user has been deactivated successfully',
            'redirect_link' => $link,
        ]);
    }

    public function deactivateMultiple(Request $request)
    {
        $validated = $request->validate([
            'selected_id'   => ['required', 'array', 'min:1'],
            'selected_id.*' => ['integer', 'distinct', 'exists:app,id'],
        ]);

        $ids = $validated['selected_id'];

       DB::transaction(function () use ($ids) {
            $authId = Auth::id();

            $ids = array_values(array_diff($ids, [$authId]));

            if (empty($ids)) {
                return;
            }

            User::query()
                ->whereIn('id', $ids)
                ->update(['status' => 'Inactive']);
        });

        return response()->json([
            'success' => true,
            'message' => 'The selected users have been deleted successfully',
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

        $user = DB::table('users')
            ->where('id', $validated['detailId'])
            ->first();

        if (!$user) {
            $link = route('apps.base', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
            ]);

            return response()->json([
                'success'  => false,
                'notExist' => true,
                'redirect_link' => $link,
                'message'  => 'User not found',
            ]);
        }

        $defaultProfilePicture = asset('assets/media/default/default-avatar.jpg');
        $path = trim((string) ($user->profile_picture ?? ''));

        $profilePictureUrl = $path !== '' && Storage::disk('public')->exists($path)
            ? Storage::url($path)
            : $defaultProfilePicture;

        return response()->json([
            'success'        => true,
            'notExist'       => false,
            'name'           => $user->name ?? null,
            'email'          => $user->email ?? null,
            'profilePicture' => $profilePictureUrl,
        ]);
    }

    public function generateTable(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');
        $filterByStatus = $request->input('filter_by_user_status');

        $users = DB::table('users')
        ->when(!empty($filterByStatus), function ($q) use ($filterByStatus) {
            $q->where('status', $filterByStatus);
        })
        ->orderBy('name')
        ->get();

        $response = $users->map(function ($row) use ($pageAppId, $pageNavigationMenuId)  {
            $userId = $row->id;
            $name = $row->name;
            $email = $row->email;
            $status = $row->status;
            $class = $status === 'Active' ? 'success' : 'danger';
            $activeBadge = "<span class=\"badge badge-light-{$class}\">{$status}</span>";
            
            $defaultProfilePicture = asset('assets/media/default/default-avatar.jpg');

            $path = trim((string) ($row->profile_picture ?? ''));

            $profilePictureLUrl = $path !== '' && Storage::disk('public')->exists($path)
                ? Storage::url($path)
                : $defaultProfilePicture;

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $userId,
            ]);

            return [
                'CHECK_BOX' => '
                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                        <input class="form-check-input datatable-checkbox-children" type="checkbox" value="'.$userId.'">
                    </div>
                ',
                'USER' => '
                    <div class="d-flex align-items-center">
                        <img src="'.$profilePictureLUrl.'" alt="app-logo" width="45" />
                        <div class="ms-3">
                            <div class="user-meta-info">
                                <h6 class="mb-0">'.$name.'</h6>
                                <small class="text-wrap fs-7 text-gray-500">'.$email.'</small>
                            </div>
                        </div>
                    </div>
                ',
                'STATUS' => $activeBadge,
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

        $users = DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $response = $response->concat(
            $users->map(fn ($row) => [
                'id'   => $row->id,
                'text' => $row->name,
            ])
        )->values();

        return response()->json($response);
    }
}
