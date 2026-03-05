@extends('admin.layouts.app')

@section('page-title', 'Fee Analysis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Fee Analysis Report</h1>
        <p class="text-muted mb-0">Platform and MPesa fee breakdown and trends</p>
    </div>
    <a href="{{ route('admin.financial.export', ['type' => 'fees', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-success">
        <i class="bi bi-download"></i> Export CSV
    </a>
</div>

<!-- Filters -->
<div class="card card-body mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" onchange="this.form.submit()">
        </div>
    </form>
</div>

<!-- Summary Cards - CORRECTED for owner costs -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Total Payout Count</small>
                <h4 class="mb-0">{{ $summary['payout_count'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Paid to Workers</small>
                <h4 class="mb-0">KES {{ number_format($summary['total_paid_to_workers'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Platform Fees (Owner Pays)</small>
                <h4 class="mb-0 text-success">KES {{ number_format($summary['total_platform_fees'], 2) }}</h4>
                <small class="text-success">{{ number_format($summary['avg_platform_fee_pct'], 2) }}% of worker pay</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">MPesa Fees (Owner Pays)</small>
                <h4 class="mb-0 text-warning">KES {{ number_format($summary['total_mpesa_fees'], 2) }}</h4>
                <small class="text-warning">{{ number_format($summary['avg_mpesa_fee_pct'], 2) }}% of worker pay</small>
            </div>
        </div>
    </div>
</div>

<!-- Owner Cost Summary -->
<div class="alert alert-info">
    <strong>Total Owner Cost:</strong> 
    <span class="h5">KES {{ number_format($summary['total_owner_costs'], 2) }}</span>
    ({{ number_format($summary['avg_owner_cost_pct'], 2) }}% above worker payments)
</div>

<!-- Fee Breakdown by Site -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Owner Cost Breakdown by Site</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Site</th>
                    <th class="text-end">Payouts</th>
                    <th class="text-end">Paid to Workers</th>
                    <th class="text-end">Platform Fee</th>
                    <th class="text-end">Platform %</th>
                    <th class="text-end">MPesa Fee</th>
                    <th class="text-end">MPesa %</th>
                    <th class="text-end">Total Owner Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bySite as $siteName => $data)
                    <tr>
                        <td class="fw-semibold">{{ $siteName }}</td>
                        <td class="text-end">{{ $data['count'] }}</td>
                        <td class="text-end">KES {{ number_format($data['paid_to_workers'], 2) }}</td>
                        <td class="text-end text-success">KES {{ number_format($data['platform_fees'], 2) }}</td>
                        <td class="text-end text-success">{{ number_format($data['platform_fee_pct'], 2) }}%</td>
                        <td class="text-end text-warning">KES {{ number_format($data['mpesa_fees'], 2) }}</td>
                        <td class="text-end text-warning">{{ number_format($data['mpesa_fee_pct'], 2) }}%</td>
                        <td class="text-end text-danger fw-semibold">KES {{ number_format($data['owner_costs'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-muted text-center py-3">No data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Fee Trend by Day -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Daily Cost Trend (Owner Perspective)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-end">Paid to Workers</th>
                    <th class="text-end">Platform Fee</th>
                    <th class="text-end">MPesa Fee</th>
                    <th class="text-end">Total Owner Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feesTrend as $day)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                        <td class="text-end fw-semibold">KES {{ number_format($day->paid_to_workers, 2) }}</td>
                        <td class="text-end text-success">KES {{ number_format($day->platform_fees, 2) }}</td>
                        <td class="text-end text-warning">KES {{ number_format($day->mpesa_fees, 2) }}</td>
                        <td class="text-end text-danger fw-semibold">KES {{ number_format($day->platform_fees + $day->mpesa_fees, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-muted text-center py-3">No data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Navigation -->
<div class="mt-4 d-flex gap-2 justify-content-center">
    <a href="{{ route('admin.financial.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
    <a href="{{ route('admin.financial.revenue') }}" class="btn btn-outline-primary">View Revenue Report</a>
</div>
@endsection
