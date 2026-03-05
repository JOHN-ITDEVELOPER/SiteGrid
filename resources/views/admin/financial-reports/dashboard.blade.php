@extends('admin.layouts.app')

@section('page-title', 'Financial Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Financial Dashboard</h1>
        <p class="text-muted mb-0">Platform financial metrics and insights</p>
    </div>
    <div class="d-flex gap-2">
        <select id="dateRange" class="form-select" style="width: auto;" onchange="window.location.href='?days='+this.value">
            <option value="7" {{ $dateRange == 7 ? 'selected' : '' }}>Last 7 days</option>
            <option value="30" {{ $dateRange == 30 ? 'selected' : '' }}>Last 30 days</option>
            <option value="90" {{ $dateRange == 90 ? 'selected' : '' }}>Last 90 days</option>
            <option value="365" {{ $dateRange == 365 ? 'selected' : '' }}>Last year</option>
        </select>
        <a href="{{ route('admin.financial.export', ['type' => 'payouts']) }}" class="btn btn-outline-primary">
            <i class="bi bi-download"></i> Export
        </a>
    </div>
</div>

<!-- Key Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Total Paid to Workers</small>
                <h4 class="mb-0">KES {{ number_format($metrics['total_paid_to_workers'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Platform Fees Earned</small>
                <h4 class="mb-0 text-success">KES {{ number_format($metrics['platform_fee_earned'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">MPesa Fees (Owner Covers)</small>
                <h4 class="mb-0 text-warning">KES {{ number_format($metrics['mpesa_fees_owner_covers'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Total Owner Costs</small>
                <h4 class="mb-0 text-danger">KES {{ number_format($metrics['total_owner_costs'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Alerts for Issues -->
<div class="row g-3 mb-4">
    @if($metrics['pending_payouts_count'] > 0)
        <div class="col-md-6">
            <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
                <div>
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>{{ $metrics['pending_payouts_count'] }} Pending Payouts</strong><br>
                    <small>KES {{ number_format($metrics['pending_payouts_amount'], 2) }} awaiting approval</small>
                </div>
                <a href="{{ route('admin.payouts.index', ['status' => 'pending']) }}" class="btn btn-sm btn-warning">Review</a>
            </div>
        </div>
    @endif
    
    @if($metrics['failed_payouts_count'] > 0)
        <div class="col-md-6">
            <div class="alert alert-danger d-flex justify-content-between align-items-center" role="alert">
                <div>
                    <i class="bi bi-x-circle"></i>
                    <strong>{{ $metrics['failed_payouts_count'] }} Failed Payouts</strong><br>
                    <small>KES {{ number_format($metrics['failed_payouts_amount'], 2) }} requiring attention</small>
                </div>
                <a href="{{ route('admin.payouts.index', ['status' => 'failed']) }}" class="btn btn-sm btn-danger">Review</a>
            </div>
        </div>
    @endif
</div>

<!-- Payout Status Breakdown -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Payout Status Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    @foreach($payoutsByStatus as $status => $data)
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted">{{ ucfirst($status) }}</small>
                                <h6 class="mb-1">{{ $data['count'] }} payouts</h6>
                                <small>KES {{ number_format($data['amount'], 2) }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Top Sites -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Top Paying Sites</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSites as $site)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.sites.show', $site->site->id) }}" class="text-decoration-none">
                                        {{ $site->site->name }}
                                    </a>
                                </td>
                                <td class="text-end fw-semibold">KES {{ number_format($site->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted text-center py-3">No payout data</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Payout Trend -->
<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payout Trend</h5>
        <a href="{{ route('admin.financial.export', ['type' => 'payouts']) }}" class="btn btn-sm btn-outline-secondary">Export</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-end">Count</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payoutTrend as $day)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                        <td class="text-end">{{ $day->count }}</td>
                        <td class="text-end fw-semibold">KES {{ number_format($day->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted text-center py-3">No data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Owner Wallet Health -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Owner Wallet Status (Top 15)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Owner</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ownerWallets as $wallet)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $wallet->owner->id) }}" class="text-decoration-none">
                                {{ $wallet->owner->name }}
                            </a>
                        </td>
                        <td class="text-end">KES {{ number_format($wallet->balance, 2) }}</td>
                        <td class="text-end">
                            @if($wallet->balance > 0)
                                <span class="badge bg-success">Funded</span>
                            @else
                                <span class="badge bg-danger">No Balance</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted text-center py-3">No wallets found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Links -->
<div class="row g-3 mt-4">
    <div class="col-md-6">
        <a href="{{ route('admin.financial.revenue') }}" class="card card-hover text-decoration-none text-dark">
            <div class="card-body">
                <i class="bi bi-graph-up text-success"></i>
                <h6 class="mt-2">Payout Revenue Report</h6>
                <small class="text-muted">Worker payouts and owner costs breakdown</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('admin.financial.platform-revenue') }}" class="card card-hover text-decoration-none text-dark">
            <div class="card-body">
                <i class="bi bi-wallet2 text-success"></i>
                <h6 class="mt-2">Platform Revenue Report</h6>
                <small class="text-muted">Invoice payments received from owners</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('admin.financial.fee-analysis') }}" class="card card-hover text-decoration-none text-dark">
            <div class="card-body">
                <i class="bi bi-percent text-info"></i>
                <h6 class="mt-2">Fee Analysis</h6>
                <small class="text-muted">Detailed fee breakdown and trends</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('admin.financial.reconciliation') }}" class="card card-hover text-decoration-none text-dark">
            <div class="card-body">
                <i class="bi bi-check2-circle text-warning"></i>
                <h6 class="mt-2">Reconciliation</h6>
                <small class="text-muted">Verify payout status and details</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('admin.payouts.index') }}" class="card card-hover text-decoration-none text-dark">
            <div class="card-body">
                <i class="bi bi-cash-coin text-primary"></i>
                <h6 class="mt-2">All Payouts</h6>
                <small class="text-muted">View and manage all payouts</small>
            </div>
        </a>
    </div>
</div>
@endsection
