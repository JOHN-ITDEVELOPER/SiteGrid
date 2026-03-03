@extends('owner.layouts.app')

@section('title', 'Payroll')
@section('page-title', 'Payroll & Payouts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <div class="d-flex gap-2">
        <a href="{{ route('owner.paycycles.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-calendar-plus"></i> Create Pay-Cycle
        </a>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-download"></i> Export
        </button>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Pending</div><div class="h5 mb-0">KES {{ number_format($summary['pending'], 2) }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Processing</div><div class="h5 mb-0">KES {{ number_format($summary['processing'], 2) }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Completed</div><div class="h5 mb-0 text-success">KES {{ number_format($summary['completed'], 2) }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3"><div class="small text-muted">Failed</div><div class="h5 mb-0 text-danger">KES {{ number_format($summary['failed'], 2) }}</div></div></div>
</div>

<div class="card kpi-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('owner.payroll') }}">
            <div class="col-md-5">
                <select class="form-select" name="site_id">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ (string)$siteId === (string)$site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    @foreach(['pending','processing','completed','failed'] as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0"><h6 class="mb-0">Pay Cycles</h6></div>
            <div class="card-body">
                @forelse($payCycles as $cycle)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold">{{ $cycle->site->name ?? 'Unknown Site' }}</div>
                        <div class="small text-muted">{{ $cycle->start_date?->format('d M Y') }} - {{ $cycle->end_date?->format('d M Y') }}</div>
                        <div class="small">Workers: {{ $cycle->worker_count }} · Total: KES {{ number_format($cycle->total_amount, 2) }}</div>
                        <span class="badge text-bg-light border mt-1">{{ ucfirst($cycle->status) }}</span>
                        @if(in_array($cycle->status, ['computed', 'open']))
                            <form method="POST" action="{{ route('owner.paycycles.approve', $cycle) }}" class="mt-2 border-top pt-2">
                                @csrf
                                <div class="mb-2">
                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Approval reason" required>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="confirm_2fa" value="1" id="approve2fa{{ $cycle->id }}" required>
                                    <label class="form-check-label small" for="approve2fa{{ $cycle->id }}">I confirm 2FA challenge is complete</label>
                                </div>
                                <button class="btn btn-sm btn-success">Approve Paycycle</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-muted">No pay cycles available.</div>
                @endforelse
                <div class="mt-2">{{ $payCycles->links() }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0"><h6 class="mb-0">Recent Payouts</h6></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Site</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $payout)
                            <tr>
                                <td>{{ $payout->worker->name ?? 'Unknown' }}</td>
                                <td>{{ $payout->payCycle->site->name ?? '—' }}</td>
                                <td>KES {{ number_format($payout->net_amount, 2) }}</td>
                                <td><span class="badge text-bg-light border">{{ ucfirst($payout->status) }}</span></td>
                                <td>{{ $payout->transaction_ref ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No payouts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ route('owner.exports.payroll') }}">
                <div class="modal-header">
                    <h5 class="modal-title">Export Payroll Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Site (optional)</label>
                        <select class="form-select" name="site_id">
                            <option value="">All Sites</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                    </div>
                    <small class="text-muted">Leave dates empty to export all records</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Download CSV</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
