<?php

namespace App\Http\Controllers\Foreman;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\SiteProgressLog;
use App\Models\SiteWorker;
use App\Models\WorkerClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $date = $request->input('date', now()->toDateString());

        $foremanSiteIds = $this->foremanSiteIds($user->id);
        $selectedSiteId = (int) ($request->input('site_id', $foremanSiteIds->first() ?? 0));

        $roster = collect();
        $pendingClaims = collect();
        $inventorySummary = [
            'low_stock' => 0,
            'pending_requests' => 0,
            'progress_today' => 0,
        ];

        if ($selectedSiteId && $foremanSiteIds->contains($selectedSiteId)) {
            $roster = SiteWorker::with('user')
                ->where('site_id', $selectedSiteId)
                ->whereNull('ended_at')
                ->orderBy('is_foreman', 'desc')
                ->get();

            $pendingClaims = WorkerClaim::with('worker')
                ->where('site_id', $selectedSiteId)
                ->where('status', 'pending_foreman')
                ->latest('requested_at')
                ->get();

            $inventorySummary['low_stock'] = InventoryStock::where('site_id', $selectedSiteId)
                ->whereColumn('quantity', '<=', 'low_stock_threshold')
                ->where('low_stock_threshold', '>', 0)
                ->count();
            $inventorySummary['pending_requests'] = ProcurementRequest::where('site_id', $selectedSiteId)
                ->where('status', 'requested')
                ->count();
            $inventorySummary['progress_today'] = SiteProgressLog::where('site_id', $selectedSiteId)
                ->whereDate('log_date', now()->toDateString())
                ->count();
        }

        return view('field.dashboard', [
            'mode' => 'foreman',
            'date' => $date,
            'selectedSiteId' => $selectedSiteId,
            'foremanSiteIds' => $foremanSiteIds,
            'roster' => $roster,
            'pendingClaims' => $pendingClaims,
            'inventorySummary' => $inventorySummary,
        ]);
    }

    public function bulkAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array'],
            'attendance.*.worker_id' => ['required', 'exists:users,id'],
            'attendance.*.is_present' => ['required', 'boolean'],
            'attendance.*.hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'attendance.*.check_in' => ['nullable', 'date_format:H:i'],
            'attendance.*.check_out' => ['nullable', 'date_format:H:i'],
        ]);

        // Foremen cannot mark attendance for past dates (before today)
        if ($validated['date'] < now()->toDateString()) {
            return back()->withErrors(['date' => 'Cannot mark or update attendance for past dates. Contact site owner if correction is needed.']);
        }

        $user = auth()->user();
        $this->assertForemanSite($user->id, (int) $validated['site_id']);

        foreach ($validated['attendance'] as $row) {
            Attendance::updateOrCreate(
                [
                    'site_id' => $validated['site_id'],
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
            'user_id' => $user->id,
            'action' => 'foreman.attendance.bulk_mark',
            'entity_type' => 'Site',
            'entity_id' => $validated['site_id'],
            'meta' => [
                'date' => $validated['date'],
                'entries' => count($validated['attendance']),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Attendance updated successfully.');
    }

    public function bulkClaimAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'claim_ids' => ['required', 'array', 'min:1'],
            'claim_ids.*' => ['integer', 'exists:worker_claims,id'],
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $this->assertForemanSite($user->id, (int) $validated['site_id']);

        $claims = WorkerClaim::where('site_id', $validated['site_id'])
            ->whereIn('id', $validated['claim_ids'])
            ->where('status', 'pending_foreman')
            ->get();

        foreach ($claims as $claim) {
            if ($validated['action'] === 'approve') {
                $claim->update([
                    'status' => 'pending_owner',
                    'approved_by_foreman' => $user->id,
                    'approved_at' => now(),
                ]);
            } else {
                $claim->update([
                    'status' => 'rejected',
                    'rejected_by' => $user->id,
                    'rejection_reason' => $validated['reason'] ?? 'Rejected by foreman',
                ]);
            }
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'foreman.claims.bulk_action',
            'entity_type' => 'Site',
            'entity_id' => $validated['site_id'],
            'meta' => [
                'action' => $validated['action'],
                'claims_count' => $claims->count(),
                'reason' => $validated['reason'] ?? null,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Claims updated successfully.');
    }

    public function rosterIndex(Request $request): View
    {
        $user = auth()->user();
        $foremanSiteIds = $this->foremanSiteIds($user->id);
        $selectedSiteId = (int) ($request->input('site_id', $foremanSiteIds->first() ?? 0));
        $date = $request->input('date', now()->toDateString());

        // Ensure date is not in the future
        if ($date > now()->toDateString()) {
            $date = now()->toDateString();
        }

        $roster = collect();
        $attendanceRecords = collect();

        if ($selectedSiteId && $foremanSiteIds->contains($selectedSiteId)) {
            $roster = SiteWorker::with('user')
                ->where('site_id', $selectedSiteId)
                ->whereNull('ended_at')
                ->orderBy('is_foreman', 'desc')
                ->get();

            // Fetch existing attendance for this date
            $attendanceRecords = Attendance::where('site_id', $selectedSiteId)
                ->where('date', $date)
                ->get()
                ->keyBy('worker_id');
        }

        return view('field.roster', [
            'foremanSiteIds' => $foremanSiteIds,
            'selectedSiteId' => $selectedSiteId,
            'date' => $date,
            'roster' => $roster,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }

    public function markAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'worker_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'is_present' => ['required', 'boolean'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $this->assertForemanSite($user->id, (int) $validated['site_id']);

        // Foremen cannot mark or update attendance for past dates (before today)
        if ($validated['date'] < now()->toDateString()) {
            return back()->withErrors(['date' => 'Cannot mark or update attendance for past dates. Contact site owner if correction is needed.']);
        }

        Attendance::updateOrCreate(
            [
                'site_id' => $validated['site_id'],
                'worker_id' => $validated['worker_id'],
                'date' => $validated['date'],
            ],
            [
                'is_present' => (bool) $validated['is_present'],
                'hours' => $validated['hours'] ?? null,
                'check_in' => $validated['check_in'] ?? null,
                'check_out' => $validated['check_out'] ?? null,
                'source' => 'foreman_web',
            ]
        );

        // NEW: Update payouts for open pay cycles in real-time
        $this->updatePayoutsForAttendance(
            (int) $validated['site_id'],
            (int) $validated['worker_id'],
            $validated['date'],
            $validated['hours'] ?? 0
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'foreman.attendance.mark',
            'entity_type' => 'Attendance',
            'entity_id' => $validated['worker_id'],
            'meta' => [
                'site_id' => $validated['site_id'],
                'date' => $validated['date'],
                'is_present' => $validated['is_present'],
                'reason' => $validated['reason'] ?? null,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Attendance marked successfully and payroll updated automatically.');
    }

    /**
     * Update payouts in real-time when attendance is recorded (Option C workflow)
     */
    private function updatePayoutsForAttendance(int $siteId, int $workerId, string $date, float $hours)
    {
        // Find open pay cycle(s) that include this date
        $cycles = \App\Models\PayCycle::where('site_id', $siteId)
            ->where('status', 'open')
            ->whereBetween('start_date', ['2000-01-01', $date])
            ->where('end_date', '>=', $date)
            ->get();

        foreach ($cycles as $cycle) {
            // Get worker's rate
            $worker = SiteWorker::where('user_id', $workerId)
                ->where('site_id', $siteId)
                ->first();

            if (!$worker) continue;

            $hourlyRate = $worker->daily_rate / 8;

            // Find or create payout for this worker in this cycle
            $payout = \App\Models\Payout::firstOrCreate(
                [
                    'pay_cycle_id' => $cycle->id,
                    'worker_id' => $workerId,
                ],
                [
                    'gross_amount' => 0,
                    'platform_fee' => 0,
                    'mpesa_fee' => 0,
                    'net_amount' => 0,
                    'status' => 'pending',
                ]
            );

            // Recalculate ALL hours for this worker in this cycle
            $totalHours = Attendance::where('site_id', $siteId)
                ->where('worker_id', $workerId)
                ->whereBetween('date', [$cycle->start_date, $cycle->end_date])
                ->sum('hours');

            // Update payout amounts
            $grossAmount = $totalHours * $hourlyRate;
            $platformFee = $grossAmount * 0.05;
            $mpesaFee = 25;
            $netAmount = $grossAmount;

            $payout->update([
                'gross_amount' => $grossAmount,
                'platform_fee' => $platformFee,
                'mpesa_fee' => $mpesaFee,
                'net_amount' => $netAmount,
            ]);

            // Recalculate cycle totals
            $totalAmount = $cycle->payouts()->sum('net_amount');
            $workerCount = $cycle->payouts()->distinct('worker_id')->count('worker_id');

            $cycle->update([
                'total_amount' => $totalAmount,
                'worker_count' => $workerCount,
            ]);
        }
    }

    public function claimsApprovalIndex(Request $request): View
    {
        $user = auth()->user();
        $foremanSiteIds = $this->foremanSiteIds($user->id);
        $selectedSiteId = (int) ($request->input('site_id', $foremanSiteIds->first() ?? 0));

        $claims = collect();

        if ($selectedSiteId && $foremanSiteIds->contains($selectedSiteId)) {
            $claims = WorkerClaim::with('worker')
                ->where('site_id', $selectedSiteId)
                ->where('status', 'pending_foreman')
                ->latest('requested_at')
                ->paginate(20);
        }

        return view('field.claims-approval', [
            'foremanSiteIds' => $foremanSiteIds,
            'selectedSiteId' => $selectedSiteId,
            'claims' => $claims,
        ]);
    }

    public function addWorkerIndex(Request $request): View
    {
        $user = auth()->user();
        $foremanSiteIds = $this->foremanSiteIds($user->id);
        $selectedSiteId = (int) ($request->input('site_id', $foremanSiteIds->first() ?? 0));

        return view('field.add-worker', [
            'foremanSiteIds' => $foremanSiteIds,
            'selectedSiteId' => $selectedSiteId,
        ]);
    }

    public function storeWorker(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'worker_id' => ['required', 'exists:users,id'],
        ]);

        $user = auth()->user();
        $this->assertForemanSite($user->id, (int) $validated['site_id']);

        SiteWorker::updateOrCreate(
            [
                'site_id' => $validated['site_id'],
                'user_id' => $validated['worker_id'],
            ],
            [
                'ended_at' => null,
            ]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'foreman.worker.add',
            'entity_type' => 'SiteWorker',
            'entity_id' => $validated['worker_id'],
            'meta' => [
                'site_id' => $validated['site_id'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Worker added to site successfully.');
    }

    private function foremanSiteIds(int $userId)
    {
        $fromSiteWorkers = SiteWorker::where('user_id', $userId)
            ->where('is_foreman', true)
            ->whereNull('ended_at')
            ->pluck('site_id');

        $fromSiteMembers = \App\Models\SiteMember::where('user_id', $userId)
            ->where('role', 'foreman')
            ->pluck('site_id');

        return $fromSiteWorkers->merge($fromSiteMembers)->unique()->values();
    }

    private function assertForemanSite(int $userId, int $siteId): void
    {
        if (!$this->foremanSiteIds($userId)->contains($siteId)) {
            abort(403, 'Unauthorized for this site.');
        }
    }
}
