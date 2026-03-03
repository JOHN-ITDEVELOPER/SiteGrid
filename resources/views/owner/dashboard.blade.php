@extends('owner.layouts.app')

@section('title', 'Owner Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Active Sites</div>
            <div class="h4 mb-0">{{ $metrics['active_sites'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Present Today</div>
            <div class="h4 mb-0">{{ $metrics['workers_present_today'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Hours Today</div>
            <div class="h4 mb-0">{{ number_format($metrics['hours_logged_today'], 1) }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Pending Approvals</div>
            <div class="h4 mb-0">{{ $metrics['pending_approvals'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Pending Payouts</div>
            <div class="h4 mb-0">{{ $metrics['pending_payouts'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Escrow Balance</div>
            <div class="h4 mb-0">KES {{ number_format($metrics['escrow_held_amount'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Low Stock Alerts</div>
            <div class="h4 mb-0 text-danger">{{ $metrics['inventory_low_stock'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Pending Procurement</div>
            <div class="h4 mb-0">{{ $metrics['inventory_pending_requests'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Progress Logs Today</div>
            <div class="h4 mb-0">{{ $metrics['progress_logs_today'] ?? 0 }}</div>
        </div>
    </div>
</div>

<!-- Quick Actions Ribbon -->
<div class="card kpi-card mb-4">
    <div class="card-body">
        <h6 class="mb-3">Quick Actions</h6>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('owner.paycycles.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-calendar-plus"></i> Create Pay-Cycle
            </a>
            <a href="{{ route('owner.workers.add') }}" class="btn btn-success btn-sm">
                <i class="bi bi-person-plus"></i> Add Worker
            </a>
            <a href="{{ route('owner.attendance') }}" class="btn btn-info btn-sm">
                <i class="bi bi-check2-square"></i> Mark Attendance
            </a>
            <a href="{{ route('owner.wallet') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-wallet2"></i> Top-up Wallet
            </a>
            <a href="{{ route('owner.payroll') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-cash-stack"></i> Run Payouts
            </a>
            <a href="{{ route('owner.inventory.index') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-box-seam"></i> Inventory Control
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card kpi-card">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0">Cash Snapshot</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <div class="small text-muted">Weekly Payroll Estimate</div>
                            <div class="h5 mb-0">KES {{ number_format($cashflow['weekly_payroll_estimate'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <div class="small text-muted">Invoices Due</div>
                            <div class="h5 mb-0">KES {{ number_format($cashflow['invoices_due'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <div class="small text-muted">Overdue</div>
                            <div class="h5 mb-0 text-danger">KES {{ number_format($cashflow['invoices_overdue'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <div class="small text-muted">Paid</div>
                            <div class="h5 mb-0 text-success">KES {{ number_format($cashflow['invoices_paid'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0">Needs Attention</h6>
            </div>
            <div class="card-body">
                @forelse($alerts as $alert)
                    <a href="{{ $alert['route'] }}" class="d-flex justify-content-between align-items-center text-decoration-none border rounded p-2 mb-2">
                        <span>{{ $alert['title'] }}</span>
                        <span class="badge bg-{{ $alert['type'] }}">{{ $alert['count'] }}</span>
                    </a>
                @empty
                    <div class="text-muted">No critical alerts right now.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card kpi-card">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0">Recent Activity</h6>
            </div>
            <div class="card-body">
                @php
                    $activities = \App\Models\AuditLog::with('user')
                        ->where('user_id', auth()->id())
                        ->orWhere(function($query) {
                            $siteIds = auth()->user()->ownedSites()->pluck('id');
                            $query->where('entity_type', 'Site')->whereIn('entity_id', $siteIds);
                        })
                        ->latest()
                        ->limit(8)
                        ->get();
                @endphp
                @forelse($activities as $activity)
                    <div class="d-flex align-items-start border-bottom pb-2 mb-2">
                        <div class="flex-shrink-0">
                            <i class="bi bi-circle-fill text-primary" style="font-size: 0.5rem;"></i>
                        </div>
                        <div class="ms-2 flex-grow-1">
                            <div class="small fw-semibold">{{ ucwords(str_replace('.', ' → ', $activity->action)) }}</div>
                            <div class="small text-muted">{{ $activity->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">No recent activity</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card kpi-card">
            <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Recent Payout Activity</h6>
                <a href="{{ route('owner.payroll') }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Site</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayouts as $payout)
                                <tr>
                                    <td>{{ $payout->worker->name ?? 'Unknown' }}</td>
                                    <td>{{ $payout->payCycle->site->name ?? '—' }}</td>
                                    <td>KES {{ number_format($payout->net_amount, 2) }}</td>
                                    <td><span class="badge text-bg-light border">{{ ucfirst($payout->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No payouts yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card kpi-card">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0">Site Performance (7 days)</h6>
            </div>
            <div class="card-body">
                @forelse($sitePerformance as $site)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $site['name'] }}</div>
                        <div class="small text-muted">{{ $site['location'] }}</div>
                        <div class="small mt-1">{{ $site['active_workers'] }} active workers · {{ $site['hours_last_7_days'] }} hrs</div>
                    </div>
                @empty
                    <div class="text-muted">No sites found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
