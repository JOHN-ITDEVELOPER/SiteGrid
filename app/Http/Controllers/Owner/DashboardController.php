<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\OwnerWallet;
use App\Models\PayCycle;
use App\Models\Payout;
use App\Models\Site;
use App\Models\SiteWorker;
use App\Models\User;
use App\Models\WorkerClaim;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\SiteProgressLog;
use App\Services\MpesaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $owner = auth()->user();
        $siteIds = $owner->ownedSites()->pluck('id');

        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $activeSites = Site::whereIn('id', $siteIds)->where('is_completed', false)->count();

        $workersPresentToday = Attendance::whereIn('site_id', $siteIds)
            ->whereDate('date', $today)
            ->where('is_present', true)
            ->distinct('worker_id')
            ->count('worker_id');

        $hoursLoggedToday = Attendance::whereIn('site_id', $siteIds)
            ->whereDate('date', $today)
            ->sum('hours');

        $pendingApprovals = PayCycle::whereIn('site_id', $siteIds)
            ->where('status', 'computed')
            ->count();

        $pendingPayouts = Payout::whereHas('payCycle', function ($query) use ($siteIds) {
            $query->whereIn('site_id', $siteIds);
        })->where('status', 'pending')->count();

        $inventoryLowStock = InventoryStock::whereIn('site_id', $siteIds)
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('low_stock_threshold', '>', 0)
            ->count();

        $inventoryPendingRequests = ProcurementRequest::whereIn('site_id', $siteIds)
            ->where('status', 'requested')
            ->count();

        $progressLogsToday = SiteProgressLog::whereIn('site_id', $siteIds)
            ->whereDate('log_date', $today->toDateString())
            ->count();

        // Combined escrow: owner's wallet balance + held payouts
        $walletBalance = OwnerWallet::where('user_id', $owner->id)->value('balance') ?? 0;
        $heldPayouts = Payout::whereHas('payCycle', function ($query) use ($siteIds) {
            $query->whereIn('site_id', $siteIds);
        })->where('escrow_status', 'held')->sum('net_amount');
        $escrowHeldAmount = $walletBalance + $heldPayouts;

        $weeklyPayrollEstimate = Attendance::whereIn('site_id', $siteIds)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('hours') * 150;

        $invoicesDue = Invoice::whereIn('site_id', $siteIds)->where('status', 'unpaid')->sum('amount');
        $invoicesOverdue = Invoice::whereIn('site_id', $siteIds)->where('status', 'overdue')->sum('amount');
        $invoicesPaid = Invoice::whereIn('site_id', $siteIds)->where('status', 'paid')->sum('amount');

        $alerts = collect([
            [
                'type' => 'warning',
                'title' => 'Attendance anomalies',
                'count' => Attendance::whereIn('site_id', $siteIds)
                    ->whereDate('date', '>=', Carbon::now()->subDays(7)->toDateString())
                    ->where(function ($query) {
                        $query->where('hours', '<', 4)->orWhere('hours', '>', 14);
                    })->count(),
                'route' => route('owner.workforce', ['filter' => 'anomaly']),
            ],
            [
                'type' => 'danger',
                'title' => 'Failed payouts',
                'count' => Payout::whereHas('payCycle', function ($query) use ($siteIds) {
                    $query->whereIn('site_id', $siteIds);
                })->where('status', 'failed')->count(),
                'route' => route('owner.payroll', ['status' => 'failed']),
            ],
            [
                'type' => 'danger',
                'title' => 'Disputed/held payouts',
                'count' => Payout::whereHas('payCycle', function ($query) use ($siteIds) {
                    $query->whereIn('site_id', $siteIds);
                })->whereIn('escrow_status', ['held', 'disputed'])->count(),
                'route' => route('owner.disputes'),
            ],
            [
                'type' => 'info',
                'title' => 'Invoices overdue',
                'count' => Invoice::whereIn('site_id', $siteIds)->where('status', 'overdue')->count(),
                'route' => route('owner.invoices', ['status' => 'overdue']),
            ],
        ])->filter(fn($alert) => $alert['count'] > 0)->values();

        $recentPayouts = Payout::with('worker', 'payCycle.site')
            ->whereHas('payCycle', function ($query) use ($siteIds) {
                $query->whereIn('site_id', $siteIds);
            })
            ->latest()
            ->limit(8)
            ->get();

        $sitePerformance = Site::whereIn('id', $siteIds)
            ->withCount(['workers' => function ($query) {
                $query->whereNull('ended_at');
            }])
            ->get()
            ->map(function ($site) {
                $last7DaysHours = Attendance::where('site_id', $site->id)
                    ->whereDate('date', '>=', Carbon::now()->subDays(7)->toDateString())
                    ->sum('hours');

                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'location' => $site->location,
                    'active_workers' => $site->workers_count,
                    'hours_last_7_days' => round($last7DaysHours, 1),
                    'is_completed' => $site->is_completed,
                ];
            });

        return view('owner.dashboard', [
            'metrics' => [
                'active_sites' => $activeSites,
                'workers_present_today' => $workersPresentToday,
                'hours_logged_today' => round($hoursLoggedToday, 1),
                'pending_approvals' => $pendingApprovals,
                'pending_payouts' => $pendingPayouts,
                'escrow_held_amount' => $escrowHeldAmount,
                'inventory_low_stock' => $inventoryLowStock,
                'inventory_pending_requests' => $inventoryPendingRequests,
                'progress_logs_today' => $progressLogsToday,
            ],
            'cashflow' => [
                'weekly_payroll_estimate' => round($weeklyPayrollEstimate, 2),
                'invoices_due' => round($invoicesDue, 2),
                'invoices_overdue' => round($invoicesOverdue, 2),
                'invoices_paid' => round($invoicesPaid, 2),
            ],
            'alerts' => $alerts,
            'recentPayouts' => $recentPayouts,
            'sitePerformance' => $sitePerformance,
        ]);
    }

    public function sites(Request $request)
    {
        $ownerId = auth()->id();
        $search = $request->input('search');
        $status = $request->input('status');

        $sites = Site::where('owner_id', $ownerId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn($query) => $query->where('is_completed', false))
            ->when($status === 'completed', fn($query) => $query->where('is_completed', true))
            ->withCount(['workers' => function ($query) {
                $query->whereNull('ended_at');
            }])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('owner.sites', compact('sites', 'search', 'status'));
    }

    public function storeSite(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'payout_method' => 'required|in:platform_managed,owner_managed',
            'owner_mpesa_account' => 'nullable|string|max:50|required_if:payout_method,owner_managed',
        ]);

        $site = Site::create([
            'owner_id' => auth()->id(),
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'payout_method' => $validated['payout_method'],
            'owner_mpesa_account' => $validated['owner_mpesa_account'] ?? null,
            'is_completed' => false,
        ]);

        $this->writeAudit($request, 'owner.site.create', 'Site', $site->id, [
            'name' => $site->name,
            'location' => $site->location,
            'payout_method' => $site->payout_method,
        ]);

        return redirect()->route('owner.sites')->with('success', 'Site created successfully.');
    }

    public function siteDetail(Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $activeWorkers = $site->workers()->whereNull('ended_at')->count();
        $totalWorkers = $site->workers()->count();
        
        $currentPayCycle = $site->payCycles()->where('status', 'computed')->orWhere('status', 'open')->latest()->first();
        
        $lastWeekAttendance = Attendance::where('site_id', $site->id)
            ->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()])
            ->count();
        
        $recentInvoices = $site->invoices()->latest()->limit(5)->get();
        $recentPayCycles = $site->payCycles()->latest()->limit(5)->get();
        
        return view('owner.sites.detail', compact('site', 'activeWorkers', 'totalWorkers', 'currentPayCycle', 'lastWeekAttendance', 'recentInvoices', 'recentPayCycles'));
    }

    public function workforce(Request $request)
    {
        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');
        $siteId = $request->input('site_id');
        $filter = $request->input('filter');

        $workers = SiteWorker::with(['user', 'site'])
            ->whereIn('site_id', $ownerSiteIds)
            ->whereNull('ended_at')
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $attendanceByWorker = Attendance::whereIn('site_id', $ownerSiteIds)
            ->whereDate('date', '>=', Carbon::now()->subDays(30)->toDateString())
            ->selectRaw('worker_id, count(*) as total_days, sum(case when is_present = 1 then 1 else 0 end) as present_days, sum(hours) as total_hours')
            ->groupBy('worker_id')
            ->get()
            ->keyBy('worker_id');

        $sites = Site::whereIn('id', $ownerSiteIds)->select('id', 'name')->orderBy('name')->get();

        return view('owner.workforce', compact('workers', 'attendanceByWorker', 'sites', 'siteId', 'filter'));
    }

    public function payroll(Request $request)
    {
        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');
        $status = $request->input('status');
        $siteId = $request->input('site_id');

        $payCycles = PayCycle::with('site')
            ->whereIn('site_id', $ownerSiteIds)
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest('start_date')
            ->paginate(12)
            ->withQueryString();

        $payouts = Payout::with(['worker', 'payCycle.site'])
            ->whereHas('payCycle', function ($query) use ($ownerSiteIds, $siteId) {
                $query->whereIn('site_id', $ownerSiteIds);
                if ($siteId) {
                    $query->where('site_id', $siteId);
                }
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->limit(20)
            ->get();

        $summary = [
            'pending' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))->where('status', 'pending')->sum('net_amount'),
            'processing' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))->where('status', 'processing')->sum('net_amount'),
            'completed' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))->where('status', 'completed')->sum('net_amount'),
            'failed' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))->where('status', 'failed')->sum('net_amount'),
        ];

        $sites = Site::whereIn('id', $ownerSiteIds)->select('id', 'name')->orderBy('name')->get();

        return view('owner.payroll', compact('payCycles', 'payouts', 'summary', 'sites', 'siteId', 'status'));
    }

    public function invoices(Request $request)
    {
        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');
        $status = $request->input('status');
        $siteId = $request->input('site_id');

        $invoices = Invoice::with('site')
            ->whereIn('site_id', $ownerSiteIds)
            ->when($status, fn($query) => $query->where('status', $status))
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'unpaid' => Invoice::whereIn('site_id', $ownerSiteIds)->where('status', 'unpaid')->sum('amount'),
            'overdue' => Invoice::whereIn('site_id', $ownerSiteIds)->where('status', 'overdue')->sum('amount'),
            'paid' => Invoice::whereIn('site_id', $ownerSiteIds)->where('status', 'paid')->sum('amount'),
        ];

        $sites = Site::whereIn('id', $ownerSiteIds)->select('id', 'name')->orderBy('name')->get();

        return view('owner.invoices', compact('invoices', 'summary', 'sites', 'siteId', 'status'));
    }

    public function disputes(Request $request)
    {
        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');

        $disputed = Payout::with(['worker', 'payCycle.site'])
            ->whereHas('payCycle', function ($query) use ($ownerSiteIds) {
                $query->whereIn('site_id', $ownerSiteIds);
            })
            ->whereIn('escrow_status', ['held', 'disputed'])
            ->latest()
            ->paginate(20);

        $summary = [
            'held_amount' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))
                ->where('escrow_status', 'held')
                ->sum('net_amount'),
            'disputed_amount' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))
                ->where('escrow_status', 'disputed')
                ->sum('net_amount'),
            'held_count' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))
                ->where('escrow_status', 'held')
                ->count(),
            'disputed_count' => Payout::whereHas('payCycle', fn($query) => $query->whereIn('site_id', $ownerSiteIds))
                ->where('escrow_status', 'disputed')
                ->count(),
        ];

        return view('owner.disputes', compact('disputed', 'summary'));
    }

    public function approvePaycycle(Request $request, PayCycle $paycycle)
    {
        $this->assertOwnerHasSite($paycycle->site_id);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
            'confirm_2fa' => 'accepted',
        ]);

        $oldStatus = $paycycle->status;
        $paycycle->status = 'approved';
        $paycycle->save();

        $this->writeAudit($request, 'owner.paycycle.approve', 'PayCycle', $paycycle->id, [
            'reason' => $validated['reason'],
            '2fa_confirmed' => true,
            'status_from' => $oldStatus,
            'status_to' => $paycycle->status,
            'site_id' => $paycycle->site_id,
        ]);

        return back()->with('success', 'Pay cycle approved successfully.');
    }

    public function acknowledgeDispute(Request $request, Payout $payout)
    {
        $this->assertOwnerHasSite($payout->payCycle->site_id);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
            'confirm_2fa' => 'accepted',
        ]);

        $notes = trim(($payout->error_message ? $payout->error_message . ' | ' : '') . 'Owner acknowledged dispute: ' . $validated['reason']);
        $payout->error_message = $notes;
        $payout->save();

        $this->writeAudit($request, 'owner.dispute.acknowledge', 'Payout', $payout->id, [
            'reason' => $validated['reason'],
            '2fa_confirmed' => true,
            'escrow_status' => $payout->escrow_status,
            'site_id' => $payout->payCycle->site_id,
        ]);

        return back()->with('success', 'Dispute acknowledged and logged.');
    }

    public function uploadInvoiceProof(Request $request, Invoice $invoice)
    {
        $this->assertOwnerHasSite($invoice->site_id);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
            'proof_reference' => 'nullable|string|max:255',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'confirm_2fa' => 'accepted',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('invoice-proofs', 'public');
        }

        $existingNotes = $invoice->notes ? $invoice->notes . PHP_EOL : '';
        $invoice->notes = $existingNotes . '[OWNER PROOF] ref=' . ($validated['proof_reference'] ?? 'n/a') . '; file=' . ($proofPath ?? 'none') . '; reason=' . $validated['reason'];
        $invoice->save();

        $this->writeAudit($request, 'owner.invoice.upload_proof', 'Invoice', $invoice->id, [
            'reason' => $validated['reason'],
            '2fa_confirmed' => true,
            'proof_reference' => $validated['proof_reference'] ?? null,
            'proof_file' => $proofPath,
            'site_id' => $invoice->site_id,
            'invoice_status' => $invoice->status,
        ]);

        return back()->with('success', 'Invoice payment proof uploaded successfully.');
    }

    protected function assertOwnerHasSite(int $siteId): void
    {
        $ownsSite = auth()->user()->ownedSites()->where('id', $siteId)->exists();

        if (!$ownsSite) {
            abort(403, 'Unauthorized action for this site.');
        }
    }

    protected function writeAudit(Request $request, string $action, string $entityType, int $entityId, array $meta = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    // ============================================
    // WORKER MANAGEMENT
    // ============================================

    public function addWorker()
    {
        $sites = auth()->user()->ownedSites()->select('id', 'name')->orderBy('name')->get();
        return view('owner.workers.add', compact('sites'));
    }

    public function storeWorker(Request $request)
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

        $this->assertOwnerHasSite($validated['site_id']);

        // Check if worker exists, otherwise create
        $user = User::where('phone', $validated['phone'])->first();
        if (!$user) {
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'password' => bcrypt('default123'),
                'role' => 'worker',
                'kyc_status' => 'pending',
            ]);
        }

        // Check if already assigned to site
        $existing = SiteWorker::where('site_id', $validated['site_id'])
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        if ($existing) {
            return back()->withErrors(['phone' => 'Worker already assigned to this site.'])->withInput();
        }

        SiteWorker::create([
            'site_id' => $validated['site_id'],
            'user_id' => $user->id,
            'role' => $validated['role'] ?? 'worker',
            'is_foreman' => $validated['is_foreman'] ?? false,
            'daily_rate' => $validated['daily_rate'],
            'weekly_rate' => $validated['weekly_rate'],
            'started_at' => $validated['started_at'],
        ]);

        $this->writeAudit($request, 'owner.worker.add', 'SiteWorker', $user->id, [
            'site_id' => $validated['site_id'],
            'worker_name' => $validated['name'],
            'phone' => $validated['phone'],
            'daily_rate' => $validated['daily_rate'],
        ]);

        return redirect()->route('owner.workforce', ['site_id' => $validated['site_id']])
            ->with('success', 'Worker added successfully.');
    }

    public function editWorker(SiteWorker $worker)
    {
        $this->assertOwnerHasSite($worker->site_id);
        $sites = auth()->user()->ownedSites()->select('id', 'name')->orderBy('name')->get();
        return view('owner.workers.edit', compact('worker', 'sites'));
    }

    public function updateWorker(Request $request, SiteWorker $worker)
    {
        $this->assertOwnerHasSite($worker->site_id);

        $validated = $request->validate([
            'role' => 'nullable|string|max:100',
            'is_foreman' => 'boolean',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'required|numeric|min:0',
        ]);

        $oldRate = $worker->daily_rate;
        $worker->update($validated);

        $this->writeAudit($request, 'owner.worker.update', 'SiteWorker', $worker->id, [
            'site_id' => $worker->site_id,
            'rate_from' => $oldRate,
            'rate_to' => $validated['daily_rate'],
        ]);

        return redirect()->route('owner.workforce', ['site_id' => $worker->site_id])
            ->with('success', 'Worker updated successfully.');
    }

    public function deactivateWorker(Request $request, SiteWorker $worker)
    {
        $this->assertOwnerHasSite($worker->site_id);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $worker->update(['ended_at' => now()]);

        $this->writeAudit($request, 'owner.worker.deactivate', 'SiteWorker', $worker->id, [
            'site_id' => $worker->site_id,
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Worker deactivated successfully.');
    }

    // ============================================
    // ATTENDANCE MANAGEMENT
    // ============================================

    public function attendance(Request $request)
    {
        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');
        $siteId = $request->input('site_id');
        $date = $request->input('date', Carbon::today()->toDateString());

        $sites = Site::whereIn('id', $ownerSiteIds)->select('id', 'name')->orderBy('name')->get();

        $workers = SiteWorker::with('user')
            ->whereIn('site_id', $ownerSiteIds)
            ->whereNull('ended_at')
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->get();

        $attendanceRecords = Attendance::whereIn('site_id', $ownerSiteIds)
            ->whereDate('date', $date)
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->get()
            ->keyBy('worker_id');

        return view('owner.attendance', compact('sites', 'workers', 'attendanceRecords', 'siteId', 'date'));
    }

    public function markAttendance(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'worker_id' => 'required|exists:users,id',
            'date' => 'required|date|before_or_equal:today',
            'is_present' => 'required|boolean',
            'hours' => 'nullable|numeric|min:0|max:24',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ]);

        $this->assertOwnerHasSite($validated['site_id']);

        // Owners can only update attendance from the current week (Monday to Sunday)
        $startOfWeek = now()->startOfWeek(); // Monday
        if ($validated['date'] < $startOfWeek->toDateString()) {
            return back()->withErrors(['date' => 'Cannot update attendance from previous weeks. Current week started on ' . $startOfWeek->format('M d, Y')]);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'site_id' => $validated['site_id'],
                'worker_id' => $validated['worker_id'],
                'date' => $validated['date'],
            ],
            [
                'is_present' => $validated['is_present'],
                'hours' => $validated['hours'] ?? 0,
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'source' => 'owner_web',
            ]
        );

        // NEW: Update payouts for open pay cycles that include this date
        $this->updatePayoutsForAttendance(
            $validated['site_id'],
            $validated['worker_id'],
            $validated['date'],
            $validated['hours'] ?? 0
        );

        $this->writeAudit($request, 'owner.attendance.mark', 'Attendance', $attendance->id, [
            'site_id' => $validated['site_id'],
            'worker_id' => $validated['worker_id'],
            'date' => $validated['date'],
            'is_present' => $validated['is_present'],
            'hours' => $validated['hours'] ?? 0,
            'reason' => $validated['reason'] ?? 'Manual entry by owner',
        ]);

        return back()->with('success', 'Attendance recorded and payroll updated automatically.');
    }

    /**
     * Update payouts in real-time when attendance is recorded (Option C workflow)
     */
    private function updatePayoutsForAttendance(int $siteId, int $workerId, string $date, float $hours)
    {
        // Find open pay cycle(s) that include this date
        $cycles = PayCycle::where('site_id', $siteId)
            ->where('status', 'open')
            ->whereBetween('start_date', ['2000-01-01', $date])
            ->where('end_date', '>=', $date)
            ->get();

        foreach ($cycles as $cycle) {
            // Get worker's rate
            $worker = SiteWorker::where('user_id', $workerId)
                ->where('site_id', $siteId)
                ->first();

            if (!$worker) continue; // Worker not assigned to this site

            $hourlyRate = $worker->daily_rate / 8;

            // Find or create payout for this worker in this cycle
            $payout = Payout::firstOrCreate(
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

            // Recalculate ALL hours for this worker in this cycle (in case they added hours today)
            $totalHours = Attendance::where('site_id', $siteId)
                ->where('worker_id', $workerId)
                ->whereBetween('date', [$cycle->start_date, $cycle->end_date])
                ->sum('hours');

            // Update payout amounts
            $grossAmount = $totalHours * $hourlyRate;
            $platformFee = $grossAmount * 0.05;
            $mpesaFee = 25;
            $netAmount = $grossAmount; // Full amount to worker

            $payout->update([
                'gross_amount' => $grossAmount,
                'platform_fee' => $platformFee,
                'mpesa_fee' => $mpesaFee,
                'net_amount' => $netAmount,
            ]);

            // Recalculate cycle totals
            $this->recalculateCyclePayoutTotals($cycle);
        }
    }

    /**
     * Recalculate total_amount and worker_count for a pay cycle
     */
    private function recalculateCyclePayoutTotals(PayCycle $cycle)
    {
        $totalAmount = $cycle->payouts()->sum('net_amount');
        $workerCount = $cycle->payouts()->distinct('worker_id')->count('worker_id');

        $cycle->update([
            'total_amount' => $totalAmount,
            'worker_count' => $workerCount,
        ]);
    }

    // ============================================
    // PAY-CYCLE MANAGEMENT
    // ============================================

    public function createPaycycle()
    {
        $sites = auth()->user()->ownedSites()->select('id', 'name', 'location')->orderBy('name')->get();
        return view('owner.paycycles.create', compact('sites'));
    }

    public function storePaycycle(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'recurrence_pattern' => 'nullable|in:weekly,bi-weekly,monthly',
        ]);

        $this->assertOwnerHasSite($validated['site_id']);

        // Check for overlapping pay cycles
        $overlapping = PayCycle::where('site_id', $validated['site_id'])
            ->where('status', '!=', 'paid')
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('start_date', '<=', $validated['start_date'])
                          ->where('end_date', '>=', $validated['end_date']);
                    });
            })->exists();

        if ($overlapping) {
            return back()->withErrors(['dates' => 'Another pay cycle already exists for this date range. Complete or cancel it first.']);
        }

        // Calculate next cycle date if recurrence is enabled
        $nextCycleDate = null;
        if (!empty($validated['recurrence_pattern'])) {
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $cycleDuration = $startDate->diffInDays($endDate);

            match($validated['recurrence_pattern']) {
                'weekly' => $nextCycleDate = $endDate->copy()->addDays(3)->toDateString(), // Fri + 3 = Mon
                'bi-weekly' => $nextCycleDate = $endDate->copy()->addDays(10)->toDateString(), // +10 days
                'monthly' => $nextCycleDate = $endDate->copy()->addMonths(1)->toDateString(), // Same day next month
            };
        }

        // Create pay cycle STRUCTURE
        $payCycle = PayCycle::create([
            'site_id' => $validated['site_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
            'total_amount' => 0,
            'worker_count' => 0,
            'recurrence_pattern' => $validated['recurrence_pattern'] ?? null,
            'next_cycle_date' => $nextCycleDate,
            'is_auto_generated' => false,
        ]);

        // NEW: Include any existing attendance records from the cycle date range
        $this->computePayoutsFromExistingAttendance($payCycle);

        $this->writeAudit($request, 'owner.paycycle.create', 'PayCycle', $payCycle->id, [
            'site_id' => $validated['site_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'recurrence_pattern' => $validated['recurrence_pattern'] ?? 'none',
            'note' => 'Pay cycle created in "open" status, existing attendance included, new attendance will update payouts in real-time',
        ]);

        return redirect()->route('owner.payroll', ['site_id' => $validated['site_id']])
            ->with('success', "Pay cycle created successfully for " . \Carbon\Carbon::parse($validated['start_date'])->format('M d') . " - " . \Carbon\Carbon::parse($validated['end_date'])->format('M d') . ". Any existing attendance has been included. New attendance will update wages automatically.");
    }

    /**
     * Compute payouts from EXISTING attendance records when pay cycle is created
     * This ensures attendance recorded BEFORE the cycle was created is still included
     */
    private function computePayoutsFromExistingAttendance(PayCycle $payCycle)
    {
        // Find all attendance within this cycle's date range
        $attendance = Attendance::where('site_id', $payCycle->site_id)
            ->whereBetween('date', [$payCycle->start_date, $payCycle->end_date])
            ->get();

        if ($attendance->isEmpty()) {
            return; // No attendance yet, nothing to compute
        }

        // Group by worker and calculate totals
        $workerTotals = $attendance->groupBy('worker_id')->map(function ($records, $workerId) use ($payCycle) {
            $hours = $records->sum('hours');
            $worker = SiteWorker::where('user_id', $workerId)
                ->where('site_id', $payCycle->site_id)
                ->first();

            $hourlyRate = $worker ? $worker->daily_rate / 8 : 0;

            return [
                'worker_id' => $workerId,
                'hours' => $hours,
                'gross_amount' => $hours * $hourlyRate,
            ];
        });

        // Create payout entries for each worker
        foreach ($workerTotals as $workerData) {
            $grossAmount = $workerData['gross_amount'];
            $platformFee = $grossAmount * 0.05;
            $mpesaFee = 25;
            $netAmount = $grossAmount;

            Payout::create([
                'pay_cycle_id' => $payCycle->id,
                'worker_id' => $workerData['worker_id'],
                'gross_amount' => $grossAmount,
                'platform_fee' => $platformFee,
                'mpesa_fee' => $mpesaFee,
                'net_amount' => $netAmount,
                'status' => 'pending',
            ]);
        }

        // Update cycle totals
        $totalAmount = $workerTotals->sum('gross_amount');
        $workerCount = $workerTotals->count();

        $payCycle->update([
            'total_amount' => $totalAmount,
            'worker_count' => $workerCount,
        ]);
    }

    // ============================================
    // WALLET & TOP-UP
    // ============================================

    public function wallet()
    {
        $owner = auth()->user();
        
        // Get or create wallet for owner
        $wallet = $owner->wallet;
        if (!$wallet) {
            $wallet = OwnerWallet::create([
                'user_id' => $owner->id,
                'balance' => 0,
                'currency' => 'KES',
            ]);
        }

        // Get recent wallet transactions
        $transactions = $wallet->transactions()
            ->latest()
            ->limit(20)
            ->get();

        // Get top-ups only (credits with 'top_up' reference)
        $topups = $wallet->transactions()
            ->where('type', 'credit')
            ->where('reference_type', 'top_up')
            ->latest()
            ->limit(10)
            ->get();

        // Calculate pending payout amount
        $pendingPayoutAmount = Payout::whereHas('payCycle', function ($query) use ($owner) {
            $query->whereIn('site_id', $owner->ownedSites()->pluck('id'));
        })->where('status', 'pending')->sum('net_amount');

        // Get platform-managed sites
        $sites = $owner->ownedSites()->where('payout_method', 'platform_managed')->get();

        return view('owner.wallet', compact('wallet', 'transactions', 'topups', 'pendingPayoutAmount', 'sites'));
    }

    public function initiateTopup(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:300000',
            'phone' => 'required|string|regex:/^254[0-9]{9}$/',
        ]);

        $owner = auth()->user();
        
        // Get or create wallet
        $wallet = $owner->wallet;
        if (!$wallet) {
            $wallet = OwnerWallet::create([
                'user_id' => $owner->id,
                'balance' => 0,
                'currency' => 'KES',
            ]);
        }

        // Initiate M-Pesa STK Push
        $mpesaService = new MpesaService();
        $result = $mpesaService->stkPush(
            $validated['phone'],
            $validated['amount'],
            $wallet->id
        );

        if ($result['success']) {
            $this->writeAudit($request, 'owner.wallet.topup_initiated', 'User', auth()->id(), [
                'amount' => $validated['amount'],
                'phone' => $validated['phone'],
                'checkout_request_id' => $result['checkout_request_id'],
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'checkout_request_id' => $result['checkout_request_id'],
                ]);
            }

            return back()->with('success', $result['message'] . ' Check your phone to complete the payment.');
        }

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Check M-Pesa transaction status via AJAX
     */
    public function checkTransactionStatus($checkoutRequestId)
    {
        $wallet = auth()->user()->wallet;
        
        if (!$wallet) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Wallet not found'
            ], 404);
        }
        
        $transaction = \App\Models\MpesaTransaction::where('checkout_request_id', $checkoutRequestId)
            ->where('related_model', \App\Models\OwnerWallet::class)
            ->where('related_id', $wallet->id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Transaction not found'
            ], 404);
        }

        $response = [
            'status' => $transaction->status,
            'amount' => $transaction->amount,
        ];

        if ($transaction->status === 'completed') {
            $response['message'] = 'Payment successful! Your wallet has been credited with KES ' . number_format($transaction->amount, 2);
            $response['receipt'] = $transaction->mpesa_receipt_number;
        } elseif ($transaction->status === 'failed') {
            $response['message'] = 'Payment failed: ' . ($transaction->result_description ?? 'Payment was cancelled or declined');
        } else {
            $response['message'] = 'Processing... Check your phone for the M-Pesa prompt';
        }

        return response()->json($response);
    }

    // ============================================
    // CLAIMS CENTER
    // ============================================

    public function claims(Request $request)
    {
        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');
        $status = $request->input('status');

        // For now showing payouts as "claims" - would have separate claims table
        $claims = Payout::with(['worker', 'payCycle.site'])
            ->whereHas('payCycle', function ($query) use ($ownerSiteIds) {
                $query->whereIn('site_id', $ownerSiteIds);
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'pending' => Payout::whereHas('payCycle', function ($q) use ($ownerSiteIds) {
                $q->whereIn('site_id', $ownerSiteIds);
            })->where('status', 'pending')->count(),
            'approved' => Payout::whereHas('payCycle', function ($q) use ($ownerSiteIds) {
                $q->whereIn('site_id', $ownerSiteIds);
            })->where('status', 'completed')->count(),
            'rejected' => Payout::whereHas('payCycle', function ($q) use ($ownerSiteIds) {
                $q->whereIn('site_id', $ownerSiteIds);
            })->where('status', 'failed')->count(),
        ];

        return view('owner.claims', compact('claims', 'summary', 'status'));
    }

    public function approveClaim(Request $request, Payout $payout)
    {
        $this->assertOwnerHasSite($payout->payCycle->site_id);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'reject') {
            $oldStatus = $payout->status;
            $payout->status = 'failed';
            $payout->error_message = $validated['notes'] ?? 'Rejected by owner';
            $payout->save();

            $this->writeAudit($request, 'owner.claim.reject', 'Payout', $payout->id, [
                'site_id' => $payout->payCycle->site_id,
                'status_from' => $oldStatus,
                'status_to' => $payout->status,
                'notes' => $validated['notes'],
            ]);

            return back()->with('success', 'Claim rejected successfully.');
        }

        // Approval flow
        $site = $payout->payCycle->site;
        
        // Check if site uses platform-managed payouts
        if ($site->payout_method === 'platform_managed') {
            // Check wallet balance - NEW: Owner pays worker wage + all fees
            $owner = auth()->user();
            $wallet = $owner->wallet;
            
            if (!$wallet) {
                return back()->with('error', 'Wallet not found. Please set up your wallet first.');
            }

            // Total cost to owner = worker net amount + platform fee + M-Pesa fee
            $totalCostToOwner = $payout->net_amount + $payout->platform_fee + $payout->mpesa_fee;

            if (!$wallet->hasSufficientBalance($totalCostToOwner)) {
                return back()->with('error', "Insufficient wallet balance. Required: KES " . number_format($totalCostToOwner, 2) . " (Worker: KES " . number_format($payout->net_amount, 2) . " + Fees: KES " . number_format($payout->platform_fee + $payout->mpesa_fee, 2) . "), Available: KES " . number_format($wallet->balance, 2) . ". Please top up your wallet.");
            }

            // Deduct full amount from wallet (worker amount + all fees)
            try {
                $wallet->debit(
                    $totalCostToOwner,
                    'PayoutAndFees',
                    $payout->id,
                    "Worker payout KES {$payout->net_amount} + Platform fee KES {$payout->platform_fee} + M-Pesa fee KES {$payout->mpesa_fee} for {$payout->worker->name} - Site: {$site->name}"
                );
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to deduct from wallet: ' . $e->getMessage());
            }

            // Initiate M-Pesa B2C payment - Worker gets full gross amount (no fee deduction)
            $mpesaService = new MpesaService();
            $workerPhone = $payout->worker->phone;
            
            // Ensure phone is in correct format (254XXXXXXXXX)
            if (!str_starts_with($workerPhone, '254')) {
                // Try to format if it starts with 0 or +254
                $workerPhone = preg_replace('/^(\+?254|0)/', '254', $workerPhone);
            }

            $result = $mpesaService->b2c(
                $workerPhone,
                $payout->net_amount, // Full amount, fees already deducted from owner's wallet
                $payout->id,
                'App\\Models\\Payout'
            );

            if ($result['success']) {
                $payout->status = 'processing'; // Will be updated to 'paid' by callback
                $payout->mpesa_transaction_id = $result['transaction_id'] ?? null;
                $payout->save();

                $this->writeAudit($request, 'owner.claim.approve_auto_disbursed', 'Payout', $payout->id, [
                    'site_id' => $site->id,
                    'worker_amount' => $payout->net_amount,
                    'platform_fee' => $payout->platform_fee,
                    'mpesa_fee' => $payout->mpesa_fee,
                    'total_owner_cost' => $totalCostToOwner,
                    'mpesa_transaction_id' => $result['transaction_id'],
                    'notes' => $validated['notes'],
                ]);

                return back()->with('success', 'Claim approved and payment initiated via M-Pesa. Worker will receive KES ' . number_format($payout->net_amount, 2) . ' shortly.');
            } else {
                // Refund wallet if M-Pesa failed
                $wallet->credit(
                    $totalCostToOwner,
                    'refund',
                    $payout->id,
                    "Refund: M-Pesa payment failed - {$result['message']}"
                );

                return back()->with('error', 'M-Pesa payment failed: ' . $result['message'] . '. Amount refunded to wallet.');
            }
        }

        // Owner-managed payout method - just approve
        $oldStatus = $payout->status;
        $payout->status = 'approved';
        $payout->save();

        $this->writeAudit($request, 'owner.claim.approve', 'Payout', $payout->id, [
            'site_id' => $site->id,
            'status_from' => $oldStatus,
            'status_to' => $payout->status,
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Claim approved successfully. Please process payment manually.');
    }

    public function overrideWithdrawalWindow(Request $request, WorkerClaim $claim)
    {
        $site = $claim->site;
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'override_reason' => 'required|string|min:5|max:500',
        ]);

        // Check if claim is already processed
        if (!in_array($claim->status, ['pending_foreman', 'pending_owner'])) {
            return back()->with('error', 'This claim cannot be overridden. Only pending claims can be emergency-approved.');
        }

        // Check owner wallet balance (worker net amount + fees)
        $owner = auth()->user();
        $wallet = OwnerWallet::where('owner_id', $owner->id)->first();

        if (!$wallet) {
            return back()->with('error', 'Wallet not found. Please set up your wallet first.');
        }

        // For worker claims, estimate fees
        $platformFee = round($claim->requested_amount * 0.05, 2); // 5% platform fee
        $mpesaFee = 25; // Fixed M-Pesa fee
        $totalCost = $claim->requested_amount + $platformFee + $mpesaFee;

        if (!$wallet->hasSufficientBalance($totalCost)) {
            return back()->with('error', 
                "Insufficient wallet balance for override. Required: KES " . number_format($totalCost, 2) . 
                " (Worker: KES " . number_format($claim->requested_amount, 2) . 
                " + Fees: KES " . number_format($platformFee + $mpesaFee, 2) . "), " .
                "Available: KES " . number_format($wallet->balance, 2)
            );
        }

        try {
            // Deduct from wallet
            $wallet->debit(
                $totalCost,
                'WorkerClaim',
                $claim->id,
                "Window override withdrawal for {$claim->worker->name} - Reason: {$validated['override_reason']}"
            );

            // Initiate M-Pesa B2C payment
            $mpesaService = new MpesaService();
            $workerPhone = $claim->worker->phone;

            // Ensure phone is in correct format
            if (!str_starts_with($workerPhone, '254')) {
                $workerPhone = preg_replace('/^(\+?254|0)/', '254', $workerPhone);
            }

            $result = $mpesaService->b2c(
                $workerPhone,
                $claim->requested_amount,
                $claim->id,
                'App\\Models\\WorkerClaim'
            );

            if ($result['success']) {
                // Update claim status
                $claim->status = 'processing';
                $claim->transaction_ref = $result['transaction_id'] ?? null;
                $claim->approved_by_owner = $owner->id;
                $claim->approved_at = now();
                $claim->save();

                // Create custom field for override reason
                $claim->override_reason = $validated['override_reason'];
                $claim->overridden_at = now();
                $claim->save();

                // Send SMS notification to worker about override
                $sms = new \App\Services\SmsService();
                $sms->send(
                    $claim->worker->phone,
                    "Your withdrawal request of KES " . number_format($claim->requested_amount, 2) . " for {$site->name} was approved outside normal withdrawal window by owner override. Payment is being processed to your M-Pesa. Ref: {$claim->id}"
                );

                // Log audit
                $this->writeAudit($request, 'owner.claim.override_window', 'WorkerClaim', $claim->id, [
                    'site_id' => $site->id,
                    'worker_id' => $claim->worker_id,
                    'amount' => $claim->requested_amount,
                    'override_reason' => $validated['override_reason'],
                    'total_cost' => $totalCost,
                    'mpesa_transaction_id' => $result['transaction_id'],
                ]);

                return back()->with('success', 
                    'Withdrawal approved by owner override! Worker will receive KES ' . 
                    number_format($claim->requested_amount, 2) . ' shortly via M-Pesa.'
                );
            } else {
                // Refund wallet if M-Pesa failed
                $wallet->credit(
                    $totalCost,
                    'refund',
                    $claim->id,
                    "Refund: M-Pesa payment failed during window override - {$result['message']}"
                );

                return back()->with('error', 'M-Pesa payment failed: ' . $result['message'] . '. Amount refunded to wallet.');
            }
        } catch (\Exception $e) {
            // Ensure wallet is refunded on any error
            $wallet->credit(
                $totalCost,
                'refund',
                $claim->id,
                "Refund: Error during window override - {$e->getMessage()}"
            );

            return back()->with('error', 'Error processing override: ' . $e->getMessage());
        }
    }


    public function markSiteCompleted(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $site->update(['is_completed' => true]);

        $this->writeAudit($request, 'owner.site.mark_completed', 'Site', $site->id, [
            'reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Site marked as completed.');
    }

    // ============================================
    // EXPORTS
    // ============================================

    public function exportPayroll(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');

        $query = Payout::with(['worker', 'payCycle.site'])
            ->whereHas('payCycle', function ($q) use ($ownerSiteIds, $validated) {
                $q->whereIn('site_id', $ownerSiteIds);
                if (!empty($validated['site_id'])) {
                    $this->assertOwnerHasSite($validated['site_id']);
                    $q->where('site_id', $validated['site_id']);
                }
                if (!empty($validated['start_date'])) {
                    $q->where('start_date', '>=', $validated['start_date']);
                }
                if (!empty($validated['end_date'])) {
                    $q->where('end_date', '<=', $validated['end_date']);
                }
            });

        $payouts = $query->get();

        $filename = 'payroll_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($payouts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Worker Name', 'Phone', 'Site', 'Pay Period Start', 'Pay Period End', 'Gross Amount', 'Platform Fee', 'MPesa Fee', 'Net Amount', 'Status', 'Transaction Ref', 'Paid At']);

            foreach ($payouts as $payout) {
                fputcsv($file, [
                    $payout->worker->name ?? 'Unknown',
                    $payout->worker->phone ?? '—',
                    $payout->payCycle->site->name ?? '—',
                    $payout->payCycle->start_date?->format('Y-m-d'),
                    $payout->payCycle->end_date?->format('Y-m-d'),
                    $payout->gross_amount,
                    $payout->platform_fee,
                    $payout->mpesa_fee,
                    $payout->net_amount,
                    $payout->status,
                    $payout->transaction_ref ?? '—',
                    $payout->paid_at?->format('Y-m-d H:i:s') ?? '—',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportAttendance(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $ownerSiteIds = auth()->user()->ownedSites()->pluck('id');

        $query = Attendance::with(['worker', 'site'])
            ->whereIn('site_id', $ownerSiteIds)
            ->whereBetween('date', [$validated['start_date'], $validated['end_date']]);

        if (!empty($validated['site_id'])) {
            $this->assertOwnerHasSite($validated['site_id']);
            $query->where('site_id', $validated['site_id']);
        }

        $attendance = $query->orderBy('date')->get();

        $filename = 'attendance_export_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($attendance) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Worker Name', 'Phone', 'Site', 'Check-in', 'Check-out', 'Hours', 'Present', 'Source']);

            foreach ($attendance as $record) {
                fputcsv($file, [
                    $record->date->format('Y-m-d'),
                    $record->worker->name ?? 'Unknown',
                    $record->worker->phone ?? '—',
                    $record->site->name ?? '—',
                    $record->check_in ?? '—',
                    $record->check_out ?? '—',
                    $record->hours ?? 0,
                    $record->is_present ? 'Yes' : 'No',
                    $record->source ?? 'unknown',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
