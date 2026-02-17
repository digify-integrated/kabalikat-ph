<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AuditLogController extends Controller
{
    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'appId'            => ['nullable', 'integer', 'min:0'],
            'navigationMenuId' => ['nullable', 'integer', 'min:0'],
            'databaseTable'    => ['required', 'string', 'max:255'],
            'referenceId'      => ['required', 'integer', 'min:1'],
        ]);

        $databaseTable = (string) $validated['databaseTable'];
        $referenceId   = (int) $validated['referenceId'];

        // Fetch logs
        $logs = DB::table('audit_log')
        ->select(['log', 'changed_by', 'created_at'])
        ->where('table_name', $databaseTable)
        ->where('reference_id', $referenceId)
        ->orderByDesc('created_at')
        ->get();

        if ($logs->isEmpty()) {
            return response()->json([
                'success'   => true,
                'log_notes' => '<div class="mb-0">
                                    <div class="card card-bordered w-100">
                                        <div class="card-body">
                                            <p class="fw-normal fs-6 text-gray-700 m-0">
                                                No log notes found.
                                            </p>
                                        </div>
                                    </div>
                                </div>',
            ]);
        }

        // Collect user IDs from logs and load users in one query (avoid N+1)
        $userIds = $logs->pluck('changed_by')->filter()->unique()->values()->all();

        $usersById = DB::table('users')
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'profile_picture'])
            ->keyBy('id');

        $count = $logs->count();

        $logNote = $logs->values()->map(function ($row, $index) use ($count, $usersById) {
            $log       = (string) ($row->log ?? '');
            $changedBy = (int) ($row->changed_by ?? 0);
            $createdAt = Carbon::parse($row->created_at);
            $logHtml = strip_tags($log, '<br><br/>');
            
            $timeElapsed = $createdAt->diffInHours(now()) >= 24
                        ? $createdAt->format('M j, Y \a\t h:i:s A')
                        : $createdAt->diffForHumans();

            $user = $usersById->get($changedBy);
            $fileAs = (string) ($user->name ?? 'Unknown User');

            $defaultProfile = asset('assets/media/default/default-avatar.jpg');

            $path = trim((string) ($user->profile_picture ?? ''));

            $profilePicture = $path !== '' && Storage::disk('public')->exists($path)
                ? Storage::url($path)
                : $defaultProfile;

            $marginClass = ($index === $count - 1) ? 'mb-0' : 'mb-9';

            return '<div class="timeline-item">
                        <div class="timeline-line"></div>
                        <div class="timeline-icon">
                            <i class="ki-outline ki-message-text-2 fs-2 text-gray-500"></i>
                        </div>
                        <div class="timeline-content ' . $marginClass . ' mt-n1">
                            <div class="pe-3 mb-5">
                                <div class="fs-6 fw-semibold mb-2">' .$logHtml . '</div>
                                <div class="d-flex align-items-center mt-1 fs-6">
                                    <div class="text-muted me-2 fs-7">
                                        Logged: ' . e($timeElapsed) . ' by
                                    </div>
                                    <div class="symbol symbol-circle symbol-25px me-2">
                                        <img src="' . e($profilePicture) . '" alt="img" />
                                    </div>
                                    <span class="text-primary fw-bold me-1 fs-7">' . e($fileAs) . '</span>
                                </div>
                            </div>
                        </div>
                    </div>';
        })->implode('');

        return response()->json([
            'success'   => true,
            'log_notes' => $logNote
        ]);
    }
}
