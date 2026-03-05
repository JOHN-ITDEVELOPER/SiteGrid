@extends('admin.layouts.app')

@section('page-title', 'Revenue Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Revenue Report</h1>
        <p class="text-muted mb-0">Worker payouts vs Owner costs breakdown by site</p>
    </div>
    <a href="{{ route('admin.financial.export', ['type' => 'revenue', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-success">
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
        <div class="col-md-4">
            <label class="form-label">Site</label>
            <select name="site_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Sites</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ $siteId == $site->id ? 'selected' : '' }}>
                        {{ $site->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<!-- Summary Cards - CORRECTED for proper billing model -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Total Paid to Workers</small>
                <h4 class="mb-0">KES {{ number_format($summary['total_paid_to_workers'], 2) }}</h4>
                <small class="text-muted">{{ $summary['payout_count'] }} payouts</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Platform Fees Charged</small>
                <h4 class="mb-0 text-success">KES {{ number_format($summary['total_platform_fees'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">MPesa Fees (Owner Cost)</small>
                <h4 class="mb-0 text-warning">KES {{ number_format($summary['total_mpesa_fees'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Total Owner Cost</small>
                <h4 class="mb-0 text-danger">KES {{ number_format($summary['total_owner_cost'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- By Site Breakdown -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Site Cost Breakdown</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th class="text-end">Cycles</th>
                            <th class="text-end">Paid to Workers</th>
                            <th class="text-end">Platform Fee</th>
                            <th class="text-end">MPesa Fee</th>
                            <th class="text-end">Owner Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bySite as $siteName => $data)
                            <tr>
                                <td class="fw-semibold">{{ $siteName }}</td>
                                <td class="text-end">{{ $data['cycles'] }}</td>
                                <td class="text-end">KES {{ number_format($data['paid_to_workers'], 2) }}</td>
                                <td class="text-end text-success">KES {{ number_format($data['platform_fee'], 2) }}</td>
                                <td class="text-end text-warning">KES {{ number_format($data['mpesa_fee'], 2) }}</td>
                                <td class="text-end text-danger fw-semibold">KES {{ number_format($data['owner_cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center py-3">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- By Pay Cycle Detail -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Pay Cycle Worker Breakdown</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Period</th>
                    <th class="text-end">Workers</th>
                    <th class="text-end">Paid to Workers</th>
                    <th class="text-end">Platform Fee</th>
                    <th class="text-end">MPesa Fee</th>
                    <th class="text-end">Owner Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byPayCycle as $cycle)
                    <tr>
                        <td class="fw-semibold">{{ $cycle['site_name'] }}</td>
                        <td>{{ $cycle['period'] }}</td>
                        <td class="text-end">{{ $cycle['worker_count'] }}</td>
                        <td class="text-end">KES {{ number_format($cycle['paid_to_workers'], 2) }}</td>
                        <td class="text-end text-success">KES {{ number_format($cycle['platform_fee'], 2) }}</td>
                        <td class="text-end text-warning">KES {{ number_format($cycle['mpesa_fee'], 2) }}</td>
                        <td class="text-end text-danger fw-semibold">KES {{ number_format($cycle['owner_cost'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted text-center py-3">No data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Navigation -->
<div class="mt-4 d-flex gap-2 justify-content-center">
    <a href="{{ route('admin.financial.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
    <a href="{{ route('admin.financial.platform-revenue') }}" class="btn btn-outline-success">View Platform Revenue</a>
    <a href="{{ route('admin.financial.fee-analysis') }}" class="btn btn-outline-primary">View Fee Analysis</a>
</div>
@endsection
