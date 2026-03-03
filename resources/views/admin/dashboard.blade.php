@extends('admin.layouts.app')

@section('page-title', 'Admin Dashboard')

@section('content')
<div class="mb-5">
    <h1 class="h2 text-dark">Admin Dashboard</h1>
        <p class="text-muted">Platform overview and management</p>
    </div>

    <!-- Metrics Cards -->
    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
        <!-- Total Sites -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Sites</p>
                            <h3 class="h2 text-dark mb-0">{{ $metrics['total_sites'] }}</h3>
                        </div>
                        <span class="badge bg-primary"><i class="bi bi-bar-chart"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Sites -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Active Sites</p>
                            <h3 class="h2 text-dark mb-0">{{ $metrics['active_sites'] }}</h3>
                        </div>
                        <span class="badge bg-success"><i class="bi bi-check-circle"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Workers -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Workers</p>
                            <h3 class="h2 text-dark mb-0">{{ $metrics['total_workers'] }}</h3>
                        </div>
                        <span class="badge bg-info"><i class="bi bi-people"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Users</p>
                            <h3 class="h2 text-dark mb-0">{{ $metrics['total_users'] }}</h3>
                        </div>
                        <span class="badge bg-warning"><i class="bi bi-person"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Revenue</p>
                            <h3 class="h3 text-dark mb-0">KES {{ number_format($metrics['total_revenue']) }}</h3>
                        </div>
                        <span class="badge bg-success"><i class="bi bi-cash-stack"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Payouts -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Payouts</p>
                            <h3 class="h3 text-dark mb-0">KES {{ number_format($metrics['total_payouts']) }}</h3>
                        </div>
                        <span class="badge bg-info"><i class="bi bi-credit-card-2-front"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payouts -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Pending Payouts</p>
                            <h3 class="h2 text-warning mb-0">{{ $metrics['pending_payouts'] }}</h3>
                        </div>
                        <span class="badge bg-warning"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Payouts -->
        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Failed Payouts</p>
                            <h3 class="h2 text-danger mb-0">{{ $metrics['failed_payouts'] }}</h3>
                        </div>
                        <span class="badge bg-danger"><i class="bi bi-x-circle"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Low Stock Alerts</p>
                            <h3 class="h2 text-danger mb-0">{{ $metrics['inventory_low_stock'] }}</h3>
                        </div>
                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Pending Procurement</p>
                            <h3 class="h2 text-warning mb-0">{{ $metrics['inventory_pending_requests'] }}</h3>
                        </div>
                        <span class="badge bg-warning"><i class="bi bi-box-seam"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Progress Logs Today</p>
                            <h3 class="h2 text-dark mb-0">{{ $metrics['progress_logs_today'] }}</h3>
                        </div>
                        <span class="badge bg-info"><i class="bi bi-image"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Weekly Payouts</h5>
                </div>
                <div class="card-body">
                    <canvas id="weeklyPayoutsChart" height="140"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Active Sites (Weekly)</h5>
                </div>
                <div class="card-body">
                    <canvas id="activeSitesChart" height="140"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Attendance Rate (30 days)</p>
                    <h3 class="h2 text-dark mb-0">{{ $analytics['attendance_rate'] }}%</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Late Payouts</p>
                    <h3 class="h2 text-dark mb-0">{{ $analytics['late_payouts'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Active Sites Trend</p>
                    <h3 class="h2 text-dark mb-0">{{ $analytics['active_sites_last'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Recent Sites</h5>
                </div>
                <div class="card-body">
                    @forelse($recentSites as $site)
                        <div class="d-flex justify-content-between align-items-center pb-3 border-bottom">
                            <div>
                                <p class="fw-semibold mb-1">{{ $site->name }}</p>
                                <p class="small text-muted mb-0">{{ $site->owner->name }}</p>
                            </div>
                            <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                    @empty
                        <p class="text-muted small">No sites yet</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Recent Payouts</h5>
                </div>
                <div class="card-body">
                    @forelse($recentPayouts as $payout)
                        <div class="d-flex justify-content-between align-items-center pb-3 border-bottom">
                            <div>
                                <p class="fw-semibold mb-1">{{ $payout->worker->name }}</p>
                                <span class="badge badge-sm bg-{{ $payout->status === 'paid' ? 'success' : ($payout->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($payout->status) }}</span>
                            </div>
                            <p class="mb-0">KES {{ number_format($payout->net_amount) }}</p>
                        </div>
                    @empty
                        <p class="text-muted small">No payouts yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Links -->
    <div class="row g-3">
        <div class="col-md-3">
            <a href="{{ route('admin.sites.index') }}" class="card card-hover text-decoration-none text-dark">
                <div class="card-body text-center">
                    <p class="h3 mb-2"><i class="bi bi-geo-alt"></i></p>
                    <p class="fw-semibold">Manage Sites</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.payouts.index') }}" class="card card-hover text-decoration-none text-dark">
                <div class="card-body text-center">
                    <p class="h3 mb-2"><i class="bi bi-credit-card-2-front"></i></p>
                    <p class="fw-semibold">Payouts</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.invoices.index') }}" class="card card-hover text-decoration-none text-dark">
                <div class="card-body text-center">
                    <p class="h3 mb-2"><i class="bi bi-receipt"></i></p>
                    <p class="fw-semibold">Invoices</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.kyc.pending') }}" class="card card-hover text-decoration-none text-dark">
                <div class="card-body text-center">
                    <p class="h3 mb-2"><i class="bi bi-shield-check"></i></p>
                    <p class="fw-semibold">KYC Verification</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.inventory.index') }}" class="card card-hover text-decoration-none text-dark">
                <div class="card-body text-center">
                    <p class="h3 mb-2"><i class="bi bi-box-seam"></i></p>
                    <p class="fw-semibold">Inventory Control</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .card-hover {
        transition: all 0.3s ease;
        border: 0 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }
</style>

@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const weeklyLabels = @json($analytics['weekly_labels']);
        const weeklyPayouts = @json($analytics['weekly_payouts']);
        const activeSites = @json($analytics['active_sites']);

        const payoutsCtx = document.getElementById('weeklyPayoutsChart');
        const sitesCtx = document.getElementById('activeSitesChart');

        if (payoutsCtx) {
            new Chart(payoutsCtx, {
                type: 'line',
                data: {
                    labels: weeklyLabels,
                    datasets: [{
                        label: 'Payouts',
                        data: weeklyPayouts,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.15)',
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        if (sitesCtx) {
            new Chart(sitesCtx, {
                type: 'bar',
                data: {
                    labels: weeklyLabels,
                    datasets: [{
                        label: 'Active Sites',
                        data: activeSites,
                        backgroundColor: 'rgba(30, 27, 75, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    </script>
@endsection
