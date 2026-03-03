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

        $site->load('owner', 'workers.user', 'payCycles', 'invoices');

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
