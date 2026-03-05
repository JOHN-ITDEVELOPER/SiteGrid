<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use App\Models\Payout;
use App\Models\Invoice;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\InventoryStock;
use App\Models\ProcurementRequest;
use App\Models\SiteProgressLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        // Ensure user is admin
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        // Collect metrics
        $metrics = [
            'total_sites' => Site::count(),
            'active_sites' => Site::whereDoesntHave('workers', function ($query) {
                $query->whereNull('ended_at');
            })->count() ?? 0,
            'total_workers' => User::where('role', 'worker')->count(),
            'total_users' => User::count(),
            'total_payouts' => Payout::sum('net_amount'),
            'total_revenue' => Invoice::where('status', 'paid')->sum('amount'),
            'pending_payouts' => Payout::where('status', 'pending')->count(),
            'failed_payouts' => Payout::where('status', 'failed')->count(),
            'inventory_low_stock' => InventoryStock::whereColumn('quantity', '<=', 'low_stock_threshold')
                ->where('low_stock_threshold', '>', 0)
                ->count(),
            'inventory_pending_requests' => ProcurementRequest::where('status', 'requested')->count(),
            'progress_logs_today' => SiteProgressLog::whereDate('log_date', now()->toDateString())->count(),
        ];

        // Recent activity
        $recentPayouts = Payout::with('worker', 'payCycle.site')
            ->latest()
            ->limit(10)
            ->get();

        $recentSites = Site::with('owner')
            ->latest()
            ->limit(5)
            ->get();

        $now = Carbon::now();
        $weeklyLabels = [];
        $weeklyPayouts = [];
        $activeSitesTrend = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = $now->copy()->subWeeks($i)->startOfWeek();
            $end = $start->copy()->endOfWeek();

            $weeklyLabels[] = $start->format('M d');
            $weeklyPayouts[] = (float) Payout::whereBetween('created_at', [$start, $end])->sum('net_amount');
            $activeSitesTrend[] = Site::whereHas('attendance', function ($query) use ($start, $end) {
                $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
            })->count();
        }

        $attendanceStart = $now->copy()->subDays(30)->startOfDay();
        $attendanceEnd = $now->copy()->endOfDay();
        $attendanceTotal = Attendance::whereBetween('date', [$attendanceStart, $attendanceEnd])->count();
        $attendancePresent = Attendance::whereBetween('date', [$attendanceStart, $attendanceEnd])
            ->where('is_present', true)
            ->count();
        $attendanceRate = $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100, 1) : 0;

        $latePayouts = Payout::where('status', 'pending')
            ->where('created_at', '<', $now->copy()->subDays(7))
            ->count();

        $analytics = [
            'weekly_labels' => $weeklyLabels,
            'weekly_payouts' => $weeklyPayouts,
            'active_sites' => $activeSitesTrend,
            'active_sites_last' => count($activeSitesTrend) ? $activeSitesTrend[count($activeSitesTrend) - 1] : 0,
            'attendance_rate' => $attendanceRate,
            'late_payouts' => $latePayouts,
        ];

        return view('admin.dashboard', compact('metrics', 'recentPayouts', 'recentSites', 'analytics'));
    }

    /**
     * List all sites
     */
    public function sites(Request $request)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $search = $request->input('search');
        $status = $request->input('status');

        $sites = Site::with('owner')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        return view('admin.sites.index', compact('sites', 'search', 'status'));
    }

    /**
     * View site details
     */
    public function siteShow(Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $site->load('owner', 'workers.user', 'payCycles', 'invoices', 'policy');

        $metrics = [
            'active_workers' => $site->workers()->whereNull('ended_at')->count(),
            'total_payouts' => Payout::whereHas('payCycle', function ($query) use ($site) {
                $query->where('site_id', $site->id);
            })->sum('net_amount'),
            'pending_amount' => Payout::whereHas('payCycle', function ($query) use ($site) {
                $query->where('site_id', $site->id);
            })->where('status', 'pending')->sum('net_amount'),
        ];

        return view('admin.sites.show', compact('site', 'metrics'));
    }

    /**
     * List all payouts
     */
    public function payouts(Request $request)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $status = $request->input('status');
        $siteId = $request->input('site_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $payouts = Payout::with('worker', 'payCycle.site')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($siteId, function ($query) use ($siteId) {
                $query->whereHas('payCycle', function ($q) use ($siteId) {
                    $q->where('site_id', $siteId);
                });
            })
            ->when($fromDate, function ($query) use ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            })
            ->when($toDate, function ($query) use ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            })
            ->latest()
            ->paginate(20);

        $sites = Site::select('id', 'name')->orderBy('name')->get();

        return view('admin.payouts.index', compact('payouts', 'status', 'siteId', 'sites', 'fromDate', 'toDate'));
    }

    /**
     * List all invoices
     */
    public function invoices(Request $request)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $status = $request->input('status');
        $siteId = $request->input('site_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $invoices = Invoice::with('site.owner')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($siteId, function ($query) use ($siteId) {
                $query->where('site_id', $siteId);
            })
            ->when($fromDate, function ($query) use ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            })
            ->when($toDate, function ($query) use ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            })
            ->latest()
            ->paginate(20);

        $sites = Site::select('id', 'name')->orderBy('name')->get();

        // Calculate summary metrics
        $unpaidTotal = Invoice::where('status', 'unpaid')->sum('amount');
        $paidThisMonth = Invoice::where('status', 'paid')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');
        $overdueTotal = Invoice::where('status', 'overdue')->sum('amount');
        $totalRevenue = Invoice::where('status', 'paid')->sum('amount');

        return view('admin.invoices.index', compact(
            'invoices', 'status', 'siteId', 'sites', 'fromDate', 'toDate',
            'unpaidTotal', 'paidThisMonth', 'overdueTotal', 'totalRevenue'
        ));
    }

    /**
     * List users for KYC verification
     */
    public function kycPending(Request $request)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $status = $request->input('status', 'pending');

        $users = User::where('role', 'site_owner')
            ->where('kyc_status', $status)
            ->latest()
            ->paginate(20);

        // Count statuses
        $pendingCount = User::where('role', 'site_owner')->where('kyc_status', 'pending')->count();
        $approvedCount = User::where('role', 'site_owner')->where('kyc_status', 'approved')->count();
        $rejectedCount = User::where('role', 'site_owner')->where('kyc_status', 'rejected')->count();

        return view('admin.kyc.pending', compact('users', 'status', 'pendingCount', 'approvedCount', 'rejectedCount'));
    }

    /**
     * Approve KYC
     */
    public function approveKyc(User $user)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $user->update(['kyc_status' => 'approved']);
        $this->logAction('kyc.approve', 'User', $user->id, [
            'kyc_status' => 'approved',
        ]);

        return back()->with('success', 'User KYC verified successfully');
    }

    /**
     * Reject KYC
     */
    public function rejectKyc(User $user)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $user->update(['kyc_status' => 'rejected']);
        $this->logAction('kyc.reject', 'User', $user->id, [
            'kyc_status' => 'rejected',
        ]);

        return back()->with('success', 'User KYC rejected');
    }

    /**
     * Show site creation form
     */
    public function createSite()
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $owners = User::where('role', 'site_owner')->orderBy('name')->get();
        return view('admin.sites.create', compact('owners'));
    }

    /**
     * Store a new site
     */
    public function storeSite(Request $request)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'payout_method' => 'required|in:platform_managed,owner_managed',
            'owner_mpesa_account' => 'nullable|string|max:50|required_if:payout_method,owner_managed',
            'payout_window_start' => 'required|string',
            'payout_window_end' => 'required|string',
            'invoice_due_days' => 'required|integer|min:1|max:365',
        ]);

        $settings = null;
        
        $site = Site::create([
            'owner_id' => $validated['owner_id'],
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
            'payout_method' => $validated['payout_method'],
            'owner_mpesa_account' => $validated['owner_mpesa_account'] ?? null,
            'payout_window_start' => $validated['payout_window_start'],
            'payout_window_end' => $validated['payout_window_end'],
            'is_completed' => false,
            'invoice_payment_method' => 'auto_wallet',
            'invoice_due_days' => $validated['invoice_due_days'],
        ]);

        $this->logAction('admin.site.create', 'Site', $site->id, [
            'name' => $site->name,
            'owner_id' => $site->owner_id,
            'location' => $site->location,
            'payout_method' => $site->payout_method,
        ]);

        return redirect()->route('admin.sites.show', $site)->with('success', 'Site created successfully.');
    }

    /**
     * Show site edit form
     */
    public function editSite(Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $owners = User::where('role', 'site_owner')->orderBy('name')->get();
        return view('admin.sites.edit', compact('site', 'owners'));
    }

    /**
     * Update site details
     */
    public function updateSite(Request $request, Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'payout_method' => 'required|in:platform_managed,owner_managed',
            'owner_mpesa_account' => 'nullable|string|max:50|required_if:payout_method,owner_managed',
            'payout_window_start' => 'required|string',
            'payout_window_end' => 'required|string',
            'invoice_due_days' => 'required|integer|min:1|max:365',
            'is_completed' => 'boolean',
        ]);

        $oldValues = $site->only(['owner_id', 'name', 'location', 'payout_method', 'owner_mpesa_account', 'payout_window_start', 'payout_window_end', 'invoice_due_days', 'is_completed']);

        $site->update($validated);

        $changes = [];
        foreach ($oldValues as $key => $oldValue) {
            if ($oldValue != $validated[$key]) {
                $changes[$key] = ['from' => $oldValue, 'to' => $validated[$key]];
            }
        }

        if (!empty($changes)) {
            $this->logAction('admin.site.update', 'Site', $site->id, $changes);
        }

        return back()->with('success', 'Site updated successfully.');
    }

    /**
     * Delete a site
     */
    public function deleteSite(Request $request, Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        if ($site->workers()->whereNull('ended_at')->exists()) {
            return back()->withErrors(['error' => 'Cannot delete site with active workers. Please deactivate all workers first.']);
        }

        if ($site->payCycles()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete site with pay cycles. Site has financial history.']);
        }

        $siteName = $site->name;
        $siteId = $site->id;

        $site->delete();

        $this->logAction('admin.site.delete', 'Site', $siteId, [
            'name' => $siteName,
            'owner_id' => $site->owner_id,
        ]);

        return redirect()->route('admin.sites.index')->with('success', 'Site deleted successfully.');
    }

    public function editSitePolicy(Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $policy = $site->policy;
        if (!$policy) {
            $policy = $site->policy()->create([
                'lock_payout_method' => true,
                'lock_invoice_payment_method' => true,
                'lock_compliance_settings' => true,
            ]);
        }

        return view('admin.sites.policy', compact('site', 'policy'));
    }

    public function updateSitePolicy(Request $request, Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'lock_payout_method' => 'nullable|boolean',
            'lock_payout_window' => 'nullable|boolean',
            'lock_invoice_payment_method' => 'nullable|boolean',
            'lock_compliance_settings' => 'nullable|boolean',
            'lock_auto_payout' => 'nullable|boolean',
            'lock_approval_workflow' => 'nullable|boolean',
            'allowed_payout_methods' => 'nullable|array',
            'allowed_payout_methods.*' => 'string|in:mpesa,bank_transfer,wallet',
            'payout_window_constraints' => 'nullable|array',
            'sms_provider_whitelist' => 'nullable|array',
            'max_team_members' => 'nullable|integer|min:1|max:200',
            'max_foremen' => 'nullable|integer|min:1|max:50',
        ]);

        // Convert missing checkboxes to false (unchecked)
        $lockFields = ['lock_payout_method', 'lock_payout_window', 'lock_invoice_payment_method', 
                       'lock_compliance_settings', 'lock_auto_payout', 'lock_approval_workflow'];
        foreach ($lockFields as $field) {
            $validated[$field] = $request->has($field) ? true : false;
        }

        $policy = $site->policy()->first();
        if (!$policy) {
            $policy = $site->policy()->create([
                'lock_payout_method' => true,
                'lock_invoice_payment_method' => true,
                'lock_compliance_settings' => true,
            ]);
        }

        $changes = [];
        foreach ($validated as $key => $value) {
            if (isset($policy->$key) && $policy->$key !== $value) {
                $changes[$key] = [
                    'from' => $policy->$key,
                    'to' => $value,
                ];
            }
        }

        $policy->update(array_merge($validated, [
            'last_policy_changed_at' => now(),
            'last_policy_changed_by' => auth()->id(),
        ]));

        $this->logAction('admin.site_policy.update', 'SitePolicy', $policy->id, [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'changes' => $changes,
        ]);

        return redirect()->route('admin.sites.show', $site)->with('success', 'Site policy updated successfully.');
    }

    public function lockdownSite(Request $request, Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'lockdown_reason' => 'required|string|max:500',
            'lockdown_duration_hours' => 'required|integer|min:1|max:720', // max 30 days
        ]);

        $policy = $site->policy()->firstOrFail();
        $lockdown_until = now()->addHours($validated['lockdown_duration_hours']);

        $policy->update([
            'is_locked_down' => true,
            'lockdown_reason' => $validated['lockdown_reason'],
            'lockdown_until' => $lockdown_until,
            'last_policy_changed_at' => now(),
            'last_policy_changed_by' => auth()->id(),
        ]);

        $this->logAction('admin.site.lockdown', 'Site', $site->id, [
            'name' => $site->name,
            'reason' => $validated['lockdown_reason'],
            'until' => $lockdown_until,
        ]);

        return redirect()->route('admin.sites.show', $site)->with('success', "Site locked down until {$lockdown_until->format('M d, Y H:i')}.");
    }

    public function unlockdownSite(Site $site)
    {
        if (auth()->user()->role !== 'platform_admin') {
            abort(403, 'Unauthorized');
        }

        $policy = $site->policy()->firstOrFail();

        $policy->update([
            'is_locked_down' => false,
            'lockdown_reason' => null,
            'lockdown_until' => null,
            'last_policy_changed_at' => now(),
            'last_policy_changed_by' => auth()->id(),
        ]);

        $this->logAction('admin.site.unlockdown', 'Site', $site->id, [
            'name' => $site->name,
        ]);

        return redirect()->route('admin.sites.show', $site)->with('success', 'Site lockdown removed.');
    }

    private function logAction(string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
