<?php

namespace App\Http\Controllers\Foreman;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\SiteProgressLog;
use App\Models\SiteWorker;
use App\Models\User;
use App\Models\WorkerClaim;
use App\Services\MpesaFeeService;
use Illuminate\Database\QueryException;
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

    public function bulkMarkAttendance(Request $request): RedirectResponse
    {
        $input = $request->all();
        
        if (isset($input['attendance']) && is_array($input['attendance'])) {
            foreach ($input['attendance'] as $key => $record) {
                // Convert empty strings to null for time fields
                if (isset($record['check_in']) && (trim($record['check_in']) === '' || $record['check_in'] === null)) {
                    $input['attendance'][$key]['check_in'] = null;
                } else if (isset($record['check_in']) && !empty($record['check_in'])) {
                    // HTML5 time input sends H:i format, convert to H:i:s for validation
                    if (strlen($record['check_in']) === 5) { // "HH:mm" is 5 chars
                        $input['attendance'][$key]['check_in'] = $record['check_in'] . ':00';
                    }
                }
                
                if (isset($record['check_out']) && (trim($record['check_out']) === '' || $record['check_out'] === null)) {
                    $input['attendance'][$key]['check_out'] = null;
                } else if (isset($record['check_out']) && !empty($record['check_out'])) {
                    // HTML5 time input sends H:i format, convert to H:i:s for validation
                    if (strlen($record['check_out']) === 5) { // "HH:mm" is 5 chars
                        $input['attendance'][$key]['check_out'] = $record['check_out'] . ':00';
                    }
                }
            }
        }

        $validated = \Illuminate\Support\Facades\Validator::make($input, [
            'site_id' => 'required|exists:sites,id',
            'date' => 'required|date|before_or_equal:today',
            'attendance' => 'required|array',
            'attendance.*.worker_id' => 'required|exists:users,id',
            'attendance.*.is_present' => 'required|boolean',
            'attendance.*.hours' => 'nullable|numeric|min:0|max:24',
            'attendance.*.check_in' => 'nullable|date_format:H:i:s',
            'attendance.*.check_out' => 'nullable|date_format:H:i:s',
            'attendance.*.reason' => 'nullable|string|max:500',
        ])->validate();

        $user = auth()->user();
        $this->assertForemanSite($user->id, (int) $validated['site_id']);

        // Foremen cannot mark attendance for past dates
        if ($validated['date'] < now()->toDateString()) {
            return back()->withErrors(['date' => 'Cannot mark attendance for past dates. Contact site owner if correction is needed.']);
        }

        $count = 0;
        foreach ($validated['attendance'] as $record) {
            Attendance::updateOrCreate(
                [
                    'site_id' => $validated['site_id'],
                    'worker_id' => $record['worker_id'],
                    'date' => $validated['date'],
                ],
                [
                    'is_present' => (bool) $record['is_present'],
                    'hours' => $record['hours'] ?? null,
                    'check_in' => $record['check_in'] ?? null,
                    'check_out' => $record['check_out'] ?? null,
                    'source' => 'foreman_web',
                ]
            );

            // Update payouts for this worker
            $this->updatePayoutsForAttendance(
                (int) $validated['site_id'],
                (int) $record['worker_id'],
                $validated['date'],
                $record['hours'] ?? 0
            );

            $count++;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'foreman.attendance.bulk-mark',
            'entity_type' => 'Attendance',
            'entity_id' => 0,
            'meta' => [
                'site_id' => $validated['site_id'],
                'date' => $validated['date'],
                'count' => $count,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Attendance marked for {$count} workers and payroll updated automatically.");
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
            $feeBreakdown = app(MpesaFeeService::class)->resolveB2CFee($grossAmount);
            $mpesaFee = $feeBreakdown['fee'];
            $netAmount = $grossAmount;

            $payout->update([
                'gross_amount' => $grossAmount,
                'platform_fee' => 0,
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
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'role' => 'nullable|string|max:100',
            'is_foreman' => 'boolean',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'required|numeric|min:0',
            'started_at' => 'required|date',
        ]);

        $user = auth()->user();
        $this->assertForemanSite($user->id, (int) $validated['site_id']);

        $existingByEmail = null;
        if (!empty($validated['email'])) {
            $existingByEmail = User::where('email', $validated['email'])->first();
        }

        // Check if worker exists, otherwise create
        $worker = User::where('phone', $validated['phone'])->first();

        if ($existingByEmail && (!$worker || $existingByEmail->id !== $worker->id)) {
            return back()->withErrors([
                'email' => 'That email is already linked to another account. Please use a different email or leave it blank.',
            ])->withInput();
        }

        if (!$worker) {
            try {
                $worker = User::create([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'],
                    'password' => bcrypt('default123'),
                    'role' => 'worker',
                    'kyc_status' => 'pending',
                ]);
            } catch (QueryException $e) {
                if ((int) $e->getCode() === 23000) {
                    return back()->withErrors([
                        'email' => 'That email is already linked to another account. Please use a different email or leave it blank.',
                    ])->withInput();
                }

                throw $e;
            }
        }

        // Check if already assigned to site
        $existing = SiteWorker::where('site_id', $validated['site_id'])
            ->where('user_id', $worker->id)
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            return back()->withErrors(['phone' => 'Worker already assigned to this site.'])->withInput();
        }

        SiteWorker::create([
            'site_id' => $validated['site_id'],
            'user_id' => $worker->id,
            'role' => $validated['role'] ?? 'worker',
            'is_foreman' => $validated['is_foreman'] ?? false,
            'daily_rate' => $validated['daily_rate'],
            'weekly_rate' => $validated['weekly_rate'],
            'started_at' => $validated['started_at'],
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'foreman.worker.add',
            'entity_type' => 'SiteWorker',
            'entity_id' => $worker->id,
            'meta' => [
                'site_id' => $validated['site_id'],
                'worker_name' => $validated['name'],
                'phone' => $validated['phone'],
                'daily_rate' => $validated['daily_rate'],
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
