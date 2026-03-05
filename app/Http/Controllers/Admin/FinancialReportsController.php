<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Invoice;
use App\Models\PayCycle;
use App\Models\Site;
use App\Models\OwnerWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportsController extends Controller
{
    /**
     * Dashboard with key financial metrics
     */
    public function dashboard()
    {
        $dateRange = request('days', 30);
        $fromDate = now()->subDays($dateRange);

        // Summary metrics - CORRECTED FOR PROPER BILLING MODEL
        // Workers receive: net_amount (no deductions)
        // Owners pay: platform_fee + mpesa_fee (from invoice + payout fees)
        $metrics = [
            'total_paid_to_workers' => Payout::where('created_at', '>=', $fromDate)->sum('net_amount'),
            'platform_fee_earned' => Payout::where('created_at', '>=', $fromDate)->sum('platform_fee'),
            'mpesa_fees_owner_covers' => Payout::where('created_at', '>=', $fromDate)->sum('mpesa_fee'),
            'total_owner_costs' => Payout::where('created_at', '>=', $fromDate)->sum(DB::raw('platform_fee + mpesa_fee')),
            'pending_payouts_amount' => Payout::where('status', 'pending')->sum('net_amount'),
            'pending_payouts_count' => Payout::where('status', 'pending')->count(),
            'failed_payouts_amount' => Payout::where('status', 'failed')->sum('net_amount'),
            'failed_payouts_count' => Payout::where('status', 'failed')->count(),
            'paid_payouts_count' => Payout::where('status', 'paid')->count(),
            'platform_invoiced_revenue' => Invoice::where('status', 'paid')->where('created_at', '>=', $fromDate)->sum('amount'),
        ];

        // Payout breakdown by status
        $payoutsByStatus = Payout::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(net_amount) as amount'))
            ->where('created_at', '>=', $fromDate)
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($row) => [$row->status => [
                'count' => $row->count,
                'amount' => $row->amount,
            ]]);

        // Payout trend (last 30 days, by day)
        $payoutTrend = Payout::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(net_amount) as amount')
        )
            ->where('created_at', '>=', $fromDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Top sites by payout volume
        $topSites = PayCycle::with('site')
            ->select('site_id', DB::raw('SUM(total_amount) as total_amount'))
            ->where('created_at', '>=', $fromDate)
            ->groupBy('site_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        // Owner wallet health
        $ownerWallets = OwnerWallet::with('owner')
            ->orderByDesc('balance')
            ->limit(15)
            ->get();

        return view('admin.financial-reports.dashboard', compact(
            'metrics',
            'payoutsByStatus',
            'payoutTrend',
            'topSites',
            'ownerWallets',
            'dateRange'
        ));
    }

    /**
     * Revenue report by site and period
     */
    public function revenue(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $siteId = $request->input('site_id');

        $query = PayCycle::with('site')
            ->whereBetween('created_at', [$fromDate, $toDate]);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $payCycles = $query->get();

        // CORRECTED: Show what workers receive and what owners pay
        $summary = [
            'total_paid_to_workers' => 0,  // What workers actually receive
            'total_platform_fees' => 0,   // What platform charges (owner pays)
            'total_mpesa_fees' => 0,      // What MPesa charges (owner covers)
            'total_owner_cost' => 0,      // Total owner must pay (platform + mpesa)
            'payout_count' => 0,
        ];

        $byPayCycle = [];
        $bySite = [];

        foreach ($payCycles as $cycle) {
            $paidToWorkers = $cycle->payouts->sum('net_amount');
            $platformFee = $cycle->payouts->sum('platform_fee');
            $mpesaFee = $cycle->payouts->sum('mpesa_fee');
            $ownerCost = $platformFee + $mpesaFee;

            $summary['total_paid_to_workers'] += $paidToWorkers;
            $summary['total_platform_fees'] += $platformFee;
            $summary['total_mpesa_fees'] += $mpesaFee;
            $summary['total_owner_cost'] += $ownerCost;
            $summary['payout_count'] += $cycle->payouts->count();

            $byPayCycle[] = [
                'site_name' => $cycle->site->name,
                'period' => "{$cycle->start_date->format('M d')} - {$cycle->end_date->format('M d, Y')}",
                'paid_to_workers' => $paidToWorkers,
                'platform_fee' => $platformFee,
                'mpesa_fee' => $mpesaFee,
                'owner_cost' => $ownerCost,
                'worker_count' => $cycle->payouts->count(),
            ];

            $siteName = $cycle->site->name;
            if (!isset($bySite[$siteName])) {
                $bySite[$siteName] = [
                    'paid_to_workers' => 0,
                    'platform_fee' => 0,
                    'mpesa_fee' => 0,
                    'owner_cost' => 0,
                    'cycles' => 0,
                ];
            }
            $bySite[$siteName]['paid_to_workers'] += $paidToWorkers;
            $bySite[$siteName]['platform_fee'] += $platformFee;
            $bySite[$siteName]['mpesa_fee'] += $mpesaFee;
            $bySite[$siteName]['owner_cost'] += $ownerCost;
            $bySite[$siteName]['cycles']++;
        }

        $sites = Site::select('id', 'name')->orderBy('name')->get();

        return view('admin.financial-reports.revenue', compact(
            'summary',
            'byPayCycle',
            'bySite',
            'sites',
            'fromDate',
            'toDate',
            'siteId'
        ));
    }

    /**
     * Fee analysis report - CORRECTED for actual billing model
     * Workers receive: net_amount
     * Owners pay: platform_fee + mpesa_fee (costs on top of worker payment)
     */
    public function feeAnalysis(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $payouts = Payout::whereBetween('created_at', [$fromDate, $toDate])
            ->where('status', 'paid')
            ->get();

        $summary = [
            'total_platform_fees' => $payouts->sum('platform_fee'),
            'total_mpesa_fees' => $payouts->sum('mpesa_fee'),
            'total_owner_costs' => $payouts->sum(DB::raw('platform_fee + mpesa_fee')),
            'total_paid_to_workers' => $payouts->sum('net_amount'),
            'payout_count' => $payouts->count(),
            'avg_platform_fee_pct' => 0,
            'avg_mpesa_fee_pct' => 0,
            'avg_owner_cost_pct' => 0,
        ];

        if ($summary['total_paid_to_workers'] > 0) {
            // Fees as percentage of what workers receive
            $summary['avg_platform_fee_pct'] = ($summary['total_platform_fees'] / $summary['total_paid_to_workers']) * 100;
            $summary['avg_mpesa_fee_pct'] = ($summary['total_mpesa_fees'] / $summary['total_paid_to_workers']) * 100;
            $summary['avg_owner_cost_pct'] = ($summary['total_owner_costs'] / $summary['total_paid_to_workers']) * 100;
        }

        // By site - owner costs breakdown
        $bySite = $payouts->groupBy(function ($payout) {
            return $payout->payCycle->site->name;
        })->map(function ($sitePayouts) {
            $paidToWorkers = $sitePayouts->sum('net_amount');
            $platformFees = $sitePayouts->sum('platform_fee');
            $mpesaFees = $sitePayouts->sum('mpesa_fee');
            $ownerCosts = $platformFees + $mpesaFees;
            
            return [
                'count' => $sitePayouts->count(),
                'platform_fees' => $platformFees,
                'mpesa_fees' => $mpesaFees,
                'owner_costs' => $ownerCosts,
                'paid_to_workers' => $paidToWorkers,
                'platform_fee_pct' => $paidToWorkers > 0 ? ($platformFees / $paidToWorkers) * 100 : 0,
                'mpesa_fee_pct' => $paidToWorkers > 0 ? ($mpesaFees / $paidToWorkers) * 100 : 0,
                'total_owner_cost_pct' => $paidToWorkers > 0 ? ($ownerCosts / $paidToWorkers) * 100 : 0,
            ];
        });

        // Trend by day
        $feesTrend = Payout::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(platform_fee) as platform_fees'),
            DB::raw('SUM(mpesa_fee) as mpesa_fees'),
            DB::raw('SUM(net_amount) as paid_to_workers')
        )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where('status', 'paid')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return view('admin.financial-reports.fee-analysis', compact(
            'summary',
            'bySite',
            'feesTrend',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Payout reconciliation report
     */
    public function reconciliation(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(7)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $status = $request->input('status');

        $query = Payout::with('worker', 'payCycle.site', 'approvedBy')
            ->whereBetween('created_at', [$fromDate, $toDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $payouts = $query->orderByDesc('created_at')->paginate(50);

        return view('admin.financial-reports.reconciliation', compact(
            'payouts',
            'fromDate',
            'toDate',
            'status'
        ));
    }

    /**
     * Platform invoice revenue report (money IN to platform)
     * Shows invoices paid by owners → platform via platform_revenue table
     */
    public function platformRevenue(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $accountId = $request->input('account_id'); // Filter by account

        $query = \App\Models\PlatformRevenue::with(['invoice.site.owner', 'platformAccount'])
            ->whereBetween('received_at', [$fromDate, $toDate]);

        if ($accountId) {
            $query->where('platform_account_id', $accountId);
        }

        $revenues = $query->orderByDesc('received_at')->get();

        // Summary metrics
        $summary = [
            'total_invoices_paid' => $revenues->pluck('invoice_id')->unique()->count(),
            'total_revenue_received' => $revenues->sum('amount'),
            'received_count' => $revenues->where('status', 'received')->count(),
            'reconciled_count' => $revenues->where('status', 'reconciled')->count(),
            'disputed_count' => $revenues->where('status', 'disputed')->count(),
        ];

        // By account breakdown
        $byAccount = $revenues->groupBy('platform_account_id')->map(function($items) {
            return [
                'account_name' => $items->first()->platformAccount?->name ?? 'Unknown',
                'shortcode' => $items->first()->platformAccount?->shortcode,
                'count' => $items->count(),
                'total_amount' => $items->sum('amount'),
            ];
        });

        // Revenue trend by day
        $revenueTrend = $revenues->groupBy(function($item) {
            return $item->received_at->format('Y-m-d');
        })->map(function($items, $date) {
            return [
                'date' => $date,
                'count' => $items->count(),
                'amount' => $items->sum('amount'),
            ];
        })->sortBy('date')->values();

        // Recent payments (paginated list)
        $recentPayments = $revenues->take(50);

        $accounts = \App\Models\PlatformAccount::whereIn('account_type', ['invoice', 'deposit'])->get();

        return view('admin.financial-reports.platform-revenue', compact(
            'summary',
            'byAccount',
            'revenueTrend',
            'recentPayments',
            'accounts',
            'fromDate',
            'toDate',
            'accountId'
        ));
    }

    /**
     * Export financial data to CSV
     */
    public function export(Request $request)
    {
        $type = $request->input('type', 'payouts');
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $filename = "{$type}_export_" . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        if ($type === 'payouts') {
            return $this->exportPayouts($fromDate, $toDate, $headers, $filename);
        } elseif ($type === 'revenue') {
            return $this->exportRevenue($fromDate, $toDate, $headers, $filename);
        } elseif ($type === 'fees') {
            return $this->exportFees($fromDate, $toDate, $headers, $filename);
        }

        return back()->with('error', 'Invalid export type.');
    }

    private function exportPayouts($fromDate, $toDate, $headers, $filename)
    {
        $callback = function () use ($fromDate, $toDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Worker', 'Site', 'Period', 'Gross', 'Platform Fee', 'MPesa Fee', 'Net', 'Status', 'Approval', 'Paid At', 'Ref']);

            Payout::with('worker', 'payCycle.site')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->chunk(100, function ($payouts) use ($file) {
                    foreach ($payouts as $payout) {
                        fputcsv($file, [
                            $payout->worker->name,
                            $payout->payCycle->site->name,
                            "{$payout->payCycle->start_date->format('M d')} - {$payout->payCycle->end_date->format('M d')}",
                            $payout->gross_amount,
                            $payout->platform_fee,
                            $payout->mpesa_fee,
                            $payout->net_amount,
                            ucfirst($payout->status),
                            ucfirst($payout->approval_status ?? 'pending'),
                            $payout->paid_at?->format('M d, Y') ?? '-',
                            $payout->transaction_ref ?? '-',
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportRevenue($fromDate, $toDate, $headers, $filename)
    {
        $callback = function () use ($fromDate, $toDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Site', 'Period', 'Gross Amount', 'Platform Fees', 'MPesa Fees', 'Net Amount', 'Workers']);

            PayCycle::with('site')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->chunk(50, function ($cycles) use ($file) {
                    foreach ($cycles as $cycle) {
                        fputcsv($file, [
                            $cycle->site->name,
                            "{$cycle->start_date->format('M d')} - {$cycle->end_date->format('M d, Y')}",
                            $cycle->payouts->sum('gross_amount'),
                            $cycle->payouts->sum('platform_fee'),
                            $cycle->payouts->sum('mpesa_fee'),
                            $cycle->payouts->sum('net_amount'),
                            $cycle->payouts->count(),
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportFees($fromDate, $toDate, $headers, $filename)
    {
        $callback = function () use ($fromDate, $toDate) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Site', 'Worker', 'Gross', 'Platform Fee', 'MPesa Fee', 'Net']);

            Payout::with('worker', 'payCycle.site')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->orderByDesc('created_at')
                ->chunk(100, function ($payouts) use ($file) {
                    foreach ($payouts as $payout) {
                        fputcsv($file, [
                            $payout->created_at->format('Y-m-d'),
                            $payout->payCycle->site->name,
                            $payout->worker->name,
                            $payout->gross_amount,
                            $payout->platform_fee,
                            $payout->mpesa_fee,
                            $payout->net_amount,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
