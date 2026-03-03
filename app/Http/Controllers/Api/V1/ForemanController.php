<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceEvidence;
use App\Models\AuditLog;
use App\Models\SiteMember;
use App\Models\SiteWorker;
use App\Models\WorkerClaim;
use Illuminate\Http\Request;

class ForemanController extends Controller
{
    public function roster(Request $request, int $site)
    {
        $this->assertForemanSite($request->user()->id, $site);

        $date = $request->query('date', now()->toDateString());

        $workers = SiteWorker::with('user:id,name,phone')
            ->where('site_id', $site)
            ->whereNull('ended_at')
            ->get()
            ->map(function ($row) use ($site, $date) {
                $attendance = Attendance::where('site_id', $site)
                    ->where('worker_id', $row->user_id)
                    ->whereDate('date', $date)
                    ->first();

                return [
                    'site_worker_id' => $row->id,
                    'worker_id' => $row->user_id,
                    'name' => $row->user?->name,
                    'phone' => $row->user?->phone,
                    'role' => $row->role,
                    'is_foreman' => $row->is_foreman,
                    'attendance' => $attendance,
                ];
            });

        return response()->json([
            'site_id' => $site,
            'date' => $date,
            'workers' => $workers,
        ]);
    }

    public function bulkAttendance(Request $request, int $site)
    {
        $this->assertForemanSite($request->user()->id, $site);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*.worker_id' => ['required', 'exists:users,id'],
            'attendance.*.is_present' => ['required', 'boolean'],
            'attendance.*.hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'attendance.*.check_in' => ['nullable', 'date_format:H:i'],
            'attendance.*.check_out' => ['nullable', 'date_format:H:i'],
        ]);

        foreach ($validated['attendance'] as $row) {
            Attendance::updateOrCreate(
                [
                    'site_id' => $site,
                    'worker_id' => $row['worker_id'],
                    'date' => $validated['date'],
                ],
                [
                    'is_present' => (bool) $row['is_present'],
                    'hours' => $row['hours'] ?? null,
                    'check_in' => $row['check_in'] ?? null,
                    'check_out' => $row['check_out'] ?? null,
                    'source' => 'foreman_web',
                ]
            );
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'foreman.api.attendance.bulk',
            'entity_type' => 'Site',
            'entity_id' => $site,
            'meta' => [
                'date' => $validated['date'],
                'entries' => count($validated['attendance']),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'Attendance bulk update successful.']);
    }

    public function pendingClaims(Request $request, int $site)
    {
        $this->assertForemanSite($request->user()->id, $site);

        $claims = WorkerClaim::with('worker:id,name,phone')
            ->where('site_id', $site)
            ->where('status', 'pending_foreman')
            ->latest('requested_at')
            ->paginate(20);

        return response()->json($claims);
    }

    public function approveClaim(Request $request, int $site, int $claim)
    {
        $this->assertForemanSite($request->user()->id, $site);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $workerClaim = WorkerClaim::where('site_id', $site)
            ->where('id', $claim)
            ->where('status', 'pending_foreman')
            ->firstOrFail();

        if ($validated['action'] === 'approve') {
            $workerClaim->update([
                'status' => 'pending_owner',
                'approved_by_foreman' => $request->user()->id,
                'approved_at' => now(),
            ]);
        } else {
            $workerClaim->update([
                'status' => 'rejected',
                'rejected_by' => $request->user()->id,
                'rejection_reason' => $validated['reason'] ?? 'Rejected by foreman',
            ]);
        }

        return response()->json([
            'message' => 'Claim processed successfully.',
            'claim' => $workerClaim,
        ]);
    }

    public function uploadEvidence(Request $request, int $site, int $attendanceId)
    {
        $this->assertForemanSite($request->user()->id, $site);

        $attendance = Attendance::where('site_id', $site)->findOrFail($attendanceId);

        $validated = $request->validate([
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'gps_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', 'in:web,mobile,ussd'],
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('attendance-evidence', 'public')
            : null;

        $evidence = AttendanceEvidence::create([
            'attendance_id' => $attendance->id,
            'uploaded_by' => $request->user()->id,
            'photo_path' => $photoPath,
            'gps_lat' => $validated['gps_lat'] ?? null,
            'gps_lng' => $validated['gps_lng'] ?? null,
            'note' => $validated['note'] ?? null,
            'source' => $validated['source'] ?? 'mobile',
        ]);

        return response()->json([
            'message' => 'Attendance evidence uploaded successfully.',
            'evidence' => $evidence,
        ], 201);
    }

    private function assertForemanSite(int $userId, int $siteId): void
    {
        $isForeman = SiteWorker::where('user_id', $userId)
            ->where('site_id', $siteId)
            ->where('is_foreman', true)
            ->whereNull('ended_at')
            ->exists()
            || SiteMember::where('user_id', $userId)
                ->where('site_id', $siteId)
                ->where('role', 'foreman')
                ->exists();

        if (!$isForeman) {
            abort(403, 'Foreman scope required for this site.');
        }
    }
}
