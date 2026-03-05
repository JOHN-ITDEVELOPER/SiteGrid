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
use App\Models\PlatformSetting;
use App\Services\MpesaFeeService;
use App\Services\MpesaService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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
            ->with('policy')
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

        $settings = PlatformSetting::firstOrCreate([]);
        
        $site = Site::create([
            'owner_id' => auth()->id(),
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'payout_method' => $validated['payout_method'],
            'owner_mpesa_account' => $validated['owner_mpesa_account'] ?? null,
            'is_completed' => false,
            'invoice_payment_method' => 'auto_wallet',
            'invoice_due_days' => $settings->default_invoice_due_days ?? 14,
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

        $site->load('policy');

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
        $status = $request->input('status', 'active'); // active, inactive, all

        $workers = SiteWorker::with(['user', 'site'])
            ->whereIn('site_id', $ownerSiteIds)
            ->when($status === 'active', fn($query) => $query->whereNull('ended_at'))
            ->when($status === 'inactive', fn($query) => $query->whereNotNull('ended_at'))
            ->when($siteId, fn($query) => $query->where('site_id', $siteId))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $attendanceByWorker = Attendance::whereIn('site_id', $ownerSiteIds)
            ->whereDate('date', '>=', Carbon::now()->subDays(30)->toDateString())
            ->selectRaw('worker_id, count(*) as total_days, sum(case when is_present = 1 then 1 else 0 end) as present_days, sum(hours) as total_hours')
            ->groupBy('worker_id')
            ->get()
            ->keyBy('worker_id');

        $sites = Site::whereIn('id', $ownerSiteIds)->select('id', 'name')->orderBy('name')->get();

        return view('owner.workforce', compact('workers', 'attendanceByWorker', 'sites', 'siteId', 'filter', 'status'));
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

    public function retryInvoicePayment(Request $request, Invoice $invoice)
    {
        $expectsJson = $request->expectsJson() || $request->ajax();

        $this->assertOwnerHasSite($invoice->site_id);

        if ($invoice->status === 'paid') {
            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice is already paid.',
                ], 422);
            }

            return back()->with('error', 'Invoice is already paid.');
        }

        if ($invoice->payment_method !== 'manual_mpesa') {
            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual retry only available for M-Pesa payment method.',
                ], 422);
            }

            return back()->with('error', 'Manual retry only available for M-Pesa payment method.');
        }

        $owner = $invoice->site->owner;
        if (!$owner || !$owner->phone) {
            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner phone number not configured. Contact support.',
                ], 422);
            }

            return back()->with('error', 'Owner phone number not configured. Contact support.');
        }

        // Normalize phone to 254XXXXXXXX format
        $phone = $owner->phone;
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (!str_starts_with($phone, '254')) {
            if (str_starts_with($phone, '0')) {
                $phone = '254' . substr($phone, 1);
            } else {
                $phone = '254' . $phone;
            }
        }

        $mpesaService = app(MpesaService::class);
        $result = $mpesaService->stkPushInvoice($phone, $invoice);

        $stkInitiated = (bool) ($result['success'] ?? false);
        $errorMsg = $result['message'] ?? 'Unknown error';
        $logMsg = $stkInitiated 
            ? "STK Retry: Initiated new STK push to {$phone}" 
            : "STK Retry: Failed - {$errorMsg}";

        $this->writeAudit($request, 'owner.invoice.retry_payment', 'Invoice', $invoice->id, [
            'stk_initiated' => $stkInitiated,
            'owner_phone' => $phone,
            'payment_method' => $invoice->payment_method,
            'site_id' => $invoice->site_id,
            'message' => $logMsg,
        ]);

        if ($stkInitiated) {
            if ($expectsJson) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment retry initiated. Check your phone for STK prompt.',
                    'checkout_request_id' => $result['checkout_request_id'] ?? null,
                ]);
            }

            return back()->with('success', 'Payment retry initiated. Check your phone for STK prompt.');
        } else {
            $fallbackMessage = 'STK prompt could not be sent. Please pay via: Paybill 522533, Account: INV-' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT);

            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg ?: $fallbackMessage,
                    'fallback_message' => $fallbackMessage,
                ], 500);
            }

            return back()->with('info', $fallbackMessage);
        }
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

        // Check if site is locked down
        $site = Site::findOrFail($validated['site_id']);
        if ($site->policy && $site->policy->isCurrentlyLockedDown()) {
            return back()->withErrors(['error' => 'Site is currently locked by admin. Cannot add workers at this time.'])->withInput();
        }

        $existingByEmail = null;
        if (!empty($validated['email'])) {
            $existingByEmail = User::where('email', $validated['email'])->first();
        }

        // Check if worker exists, otherwise create
        $user = User::where('phone', $validated['phone'])->first();

        if ($existingByEmail && (!$user || $existingByEmail->id !== $user->id)) {
            return back()->withErrors([
                'email' => 'That email is already linked to another account. Please use a different email or leave it blank.',
            ])->withInput();
        }

        if (!$user) {
            try {
                $user = User::create([
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

        // Check if site is locked down
        $site = Site::findOrFail($worker->site_id);
        if ($site->policy && $site->policy->isCurrentlyLockedDown()) {
            return back()->withErrors(['error' => 'Site is currently locked by admin. Cannot update workers at this time.']);
        }

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'role' => 'nullable|string|max:100',
            'is_foreman' => 'boolean',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'required|numeric|min:0',
        ]);

        $this->assertOwnerHasSite((int) $validated['site_id']);

        $phoneExists = User::where('phone', $validated['phone'])
            ->where('id', '!=', $worker->user_id)
            ->exists();

        if ($phoneExists) {
            return back()->withErrors([
                'phone' => 'That phone number is already linked to another account. Please use a different number.',
            ])->withInput();
        }

        $hasActiveAssignmentAtTargetSite = SiteWorker::where('site_id', $validated['site_id'])
            ->where('user_id', $worker->user_id)
            ->whereNull('ended_at')
            ->where('id', '!=', $worker->id)
            ->exists();

        if ($hasActiveAssignmentAtTargetSite) {
            return back()->withErrors([
                'site_id' => 'This worker already has an active assignment at the selected site.',
            ])->withInput();
        }

        $oldRate = $worker->daily_rate;
        $oldSiteId = $worker->site_id;
        $oldName = $worker->user->name;
        $oldPhone = $worker->user->phone;

        $worker->user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        $worker->update($validated);

        $this->writeAudit($request, 'owner.worker.update', 'SiteWorker', $worker->id, [
            'site_id_from' => $oldSiteId,
            'site_id_to' => $worker->site_id,
            'worker_name_from' => $oldName,
            'worker_name_to' => $validated['name'],
            'phone_from' => $oldPhone,
            'phone_to' => $validated['phone'],
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

    public function reactivateWorker(Request $request, SiteWorker $worker)
    {
        $this->assertOwnerHasSite($worker->site_id);

        if (is_null($worker->ended_at)) {
            return back()->withErrors(['error' => 'Worker is already active.']);
        }

        // Check if worker already has an active assignment at this site
        $hasActiveAssignment = SiteWorker::where('site_id', $worker->site_id)
            ->where('user_id', $worker->user_id)
            ->whereNull('ended_at')
            ->where('id', '!=', $worker->id)
            ->exists();

        if ($hasActiveAssignment) {
            return back()->withErrors(['error' => 'Worker already has an active assignment at this site.']);
        }

        $worker->update(['ended_at' => null]);

        $this->writeAudit($request, 'owner.worker.reactivate', 'SiteWorker', $worker->id, [
            'site_id' => $worker->site_id,
            'worker_name' => $worker->user->name,
        ]);

        return back()->with('success', 'Worker reactivated successfully.');
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

    public function bulkMarkAttendance(Request $request)
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
            'date' => 'required|date|before_or_equal:today',
            'attendance' => 'required|array',
            'attendance.*.worker_id' => 'required|exists:users,id',
            'attendance.*.site_id' => 'required|exists:sites,id',
            'attendance.*.is_present' => 'required|boolean',
            'attendance.*.hours' => 'nullable|numeric|min:0|max:24',
            'attendance.*.check_in' => 'nullable|date_format:H:i:s',
            'attendance.*.check_out' => 'nullable|date_format:H:i:s',
            'attendance.*.reason' => 'nullable|string|max:500',
        ])->validate();

        // Verify owner has access to all sites
        foreach ($validated['attendance'] as $record) {
            $this->assertOwnerHasSite($record['site_id']);
        }

        // Owners can only update attendance from the current week
        $startOfWeek = now()->startOfWeek();
        if ($validated['date'] < $startOfWeek->toDateString()) {
            return back()->withErrors(['date' => 'Cannot update attendance from previous weeks. Current week started on ' . $startOfWeek->format('M d, Y')]);
        }

        $count = 0;
        foreach ($validated['attendance'] as $record) {
            $attendance = Attendance::updateOrCreate(
                [
                    'site_id' => $record['site_id'],
                    'worker_id' => $record['worker_id'],
                    'date' => $validated['date'],
                ],
                [
                    'is_present' => $record['is_present'],
                    'hours' => $record['hours'] ?? 0,
                    'check_in' => $record['check_in'] ?? null,
                    'check_out' => $record['check_out'] ?? null,
                    'source' => 'owner_web',
                ]
            );

            // Update payouts for this worker
            $this->updatePayoutsForAttendance(
                $record['site_id'],
                $record['worker_id'],
                $validated['date'],
                $record['hours'] ?? 0
            );

            $count++;
        }

        $this->writeAudit($request, 'owner.attendance.bulk-mark', 'Attendance', 0, [
            'date' => $validated['date'],
            'count' => $count,
        ]);

        return back()->with('success', "Attendance recorded for {$count} workers and payroll updated automatically.");
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
            $feeBreakdown = app(MpesaFeeService::class)->resolveB2CFee($grossAmount);
            $mpesaFee = $feeBreakdown['fee'];
            $netAmount = $grossAmount; // Worker receives full earned amount

            $payout->update([
                'gross_amount' => $grossAmount,
                'platform_fee' => 0,
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

        // Check if site is locked down
        $site = Site::findOrFail($validated['site_id']);
        if ($site->policy && $site->policy->isCurrentlyLockedDown()) {
            return back()->withErrors(['error' => 'Site is currently locked by admin. Cannot create pay cycles at this time.'])->withInput();
        }

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
            $feeBreakdown = app(MpesaFeeService::class)->resolveB2CFee($grossAmount);
            $mpesaFee = $feeBreakdown['fee'];
            $netAmount = $grossAmount;

            Payout::create([
                'pay_cycle_id' => $payCycle->id,
                'worker_id' => $workerData['worker_id'],
                'gross_amount' => $grossAmount,
                'platform_fee' => 0,
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
     * Check M-Pesa transaction status via AJAX (handles both wallet top-ups and invoice payments)
     */
    public function checkTransactionStatus($checkoutRequestId)
    {
        $transaction = \App\Models\MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Transaction not found'
            ], 404);
        }

        // Verify owner has access to this transaction
        if ($transaction->related_model === \App\Models\OwnerWallet::class) {
            $wallet = auth()->user()->wallet;
            if (!$wallet || $transaction->related_id !== $wallet->id) {
                return response()->json([
                    'status' => 'unauthorized',
                    'message' => 'Unauthorized'
                ], 403);
            }
        } elseif ($transaction->related_model === \App\Models\Invoice::class) {
            $invoice = \App\Models\Invoice::find($transaction->related_id);
            if (!$invoice || $invoice->site->owner_id !== auth()->id()) {
                return response()->json([
                    'status' => 'unauthorized',
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        $response = [
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'type' => $transaction->related_model === \App\Models\Invoice::class ? 'invoice' : 'wallet',
        ];

        if ($transaction->status === 'completed') {
            if ($transaction->related_model === \App\Models\Invoice::class) {
                $response['message'] = 'Invoice payment successful! Payment of KES ' . number_format($transaction->amount, 2) . ' received.';
            } else {
                $response['message'] = 'Payment successful! Your wallet has been credited with KES ' . number_format($transaction->amount, 2);
            }
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
        $statusFilter = $request->input('status');

        // Query actual worker claims (withdrawal requests from workers)
        $query = WorkerClaim::with(['worker', 'site'])
            ->whereIn('site_id', $ownerSiteIds);

        // Apply status filter
        if ($statusFilter === 'pending') {
            // Pending claims (awaiting owner action)
            $query->whereIn('status', ['pending_owner', 'approved']);
        } elseif ($statusFilter === 'completed') {
            // Completed/paid claims
            $query->where('status', 'paid');
        } elseif ($statusFilter === 'failed') {
            // Rejected claims
            $query->where('status', 'rejected');
        }

        $claims = $query->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        // Calculate summary stats
        $summary = [
            'pending' => WorkerClaim::whereIn('site_id', $ownerSiteIds)
                ->whereIn('status', ['pending_owner', 'approved'])
                ->count(),
            'approved' => WorkerClaim::whereIn('site_id', $ownerSiteIds)
                ->where('status', 'paid')
                ->count(),
            'rejected' => WorkerClaim::whereIn('site_id', $ownerSiteIds)
                ->where('status', 'rejected')
                ->count(),
        ];

        $status = $statusFilter; // Pass the original filter value for view

        return view('owner.claims', compact('claims', 'summary', 'status'));
    }

    public function approveClaim(Request $request, WorkerClaim $claim)
    {
        $this->assertOwnerHasSite($claim->site_id);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'reject') {
            $oldStatus = $claim->status;
            $claim->status = 'rejected';
            $claim->rejection_reason = $validated['notes'] ?? 'Rejected by owner';
            $claim->rejected_by = auth()->id();
            $claim->save();

            $this->writeAudit($request, 'owner.claim.reject', 'WorkerClaim', $claim->id, [
                'site_id' => $claim->site_id,
                'worker_id' => $claim->worker_id,
                'status_from' => $oldStatus,
                'status_to' => $claim->status,
                'amount' => $claim->requested_amount,
                'notes' => $validated['notes'],
            ]);

            return back()->with('success', 'Claim rejected and worker notified.');
        }

        // Approval flow
        $site = $claim->site;
        
        // Check if site uses platform-managed payouts
        if ($site->payout_method === 'platform_managed') {
            // Check wallet balance - Owner pays requested amount + all fees
            $owner = auth()->user();
            $wallet = $owner->wallet;
            
            if (!$wallet) {
                return back()->with('error', 'Wallet not found. Please set up your wallet first.');
            }

            // Owner covers M-Pesa transfer cost; worker receives full requested amount.
            $feeBreakdown = app(MpesaFeeService::class)->resolveB2CFee($claim->requested_amount, $claim->worker->phone);
            $platformFee = 0;
            $mpesaFee = $feeBreakdown['fee'];
            $totalCostToOwner = $claim->requested_amount + $mpesaFee;

            if (!$wallet->hasSufficientBalance($totalCostToOwner)) {
                return back()->with('error', 
                    "Insufficient wallet balance. Required: KES " . number_format($totalCostToOwner, 2) . 
                    " (Worker: KES " . number_format($claim->requested_amount, 2) . 
                    " + M-Pesa fee: KES " . number_format($mpesaFee, 2) . "), " .
                    "Available: KES " . number_format($wallet->balance, 2) . ". Please top up your wallet."
                );
            }

            // Deduct full amount from wallet
            try {
                $wallet->debit(
                    $totalCostToOwner,
                    'WorkerClaim',
                    $claim->id,
                    "Worker withdrawal KES {$claim->requested_amount} + M-Pesa fee KES {$mpesaFee} for {$claim->worker->name} - Site: {$site->name}"
                );
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to deduct from wallet: ' . $e->getMessage());
            }

            // Initiate M-Pesa B2C payment
            $mpesaService = new MpesaService();
            $workerPhone = $claim->worker->phone;
            
            // Ensure phone is in correct format (254XXXXXXXXX)
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
                $claim->status = 'processing'; // Will be updated to 'paid' by callback
                $claim->transaction_ref = $result['transaction_id'] ?? null;
                $claim->approved_by_owner = auth()->id();
                $claim->approved_at = now();
                $claim->save();

                $this->writeAudit($request, 'owner.claim.approve_disbursed', 'WorkerClaim', $claim->id, [
                    'site_id' => $site->id,
                    'worker_id' => $claim->worker_id,
                    'worker_amount' => $claim->requested_amount,
                    'platform_fee' => $platformFee,
                    'mpesa_fee' => $mpesaFee,
                    'total_owner_cost' => $totalCostToOwner,
                    'mpesa_transaction_id' => $result['transaction_id'],
                    'notes' => $validated['notes'],
                ]);

                return back()->with('success', 'Claim approved and payment initiated via M-Pesa. Worker will receive KES ' . number_format($claim->requested_amount, 2) . ' shortly.');
            } else {
                // Refund wallet if M-Pesa failed
                $wallet->credit(
                    $totalCostToOwner,
                    'refund',
                    $claim->id,
                    "Refund: M-Pesa payment failed - {$result['message']}"
                );

                return back()->with('error', 'M-Pesa payment failed: ' . $result['message'] . '. Amount refunded to wallet.');
            }
        }

        // Owner-managed payout method - just approve for manual processing
        $oldStatus = $claim->status;
        $claim->status = 'approved';
        $claim->approved_by_owner = auth()->id();
        $claim->approved_at = now();
        $claim->save();

        $this->writeAudit($request, 'owner.claim.approve', 'WorkerClaim', $claim->id, [
            'site_id' => $site->id,
            'worker_id' => $claim->worker_id,
            'status_from' => $oldStatus,
            'status_to' => $claim->status,
            'amount' => $claim->requested_amount,
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Claim approved. Please process payment manually to the owner M-Pesa account.');
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

        // Owner covers M-Pesa transfer fee; no platform fee is charged here.
        $feeBreakdown = app(MpesaFeeService::class)->resolveB2CFee($claim->requested_amount, $claim->worker->phone);
        $platformFee = 0;
        $mpesaFee = $feeBreakdown['fee'];
        $totalCost = $claim->requested_amount + $mpesaFee;

        if (!$wallet->hasSufficientBalance($totalCost)) {
            return back()->with('error', 
                "Insufficient wallet balance for override. Required: KES " . number_format($totalCost, 2) . 
                " (Worker: KES " . number_format($claim->requested_amount, 2) . 
                " + M-Pesa fee: KES " . number_format($mpesaFee, 2) . "), " .
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
