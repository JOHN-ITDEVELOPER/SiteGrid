@extends('owner.layouts.app')

@section('title', $site->name)
@section('page-title', $site->name)

@section('content')
<div class="mb-3">
    <a href="{{ route('owner.sites') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Sites
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card kpi-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">{{ $site->name }}</h5>
                        <p class="text-muted mb-0">
                            <i class="bi bi-geo-alt"></i> {{ $site->location }}
                        </p>
                    </div>
                    <span class="badge {{ $site->is_completed ? 'text-bg-secondary' : 'text-bg-success' }} fs-6">
                        {{ $site->is_completed ? 'Completed' : 'Active' }}
                    </span>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <small class="text-muted">Active Workers</small>
                            <div class="h5 mb-0">{{ $activeWorkers }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <small class="text-muted">Total Workers</small>
                            <div class="h5 mb-0">{{ $totalWorkers }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <small class="text-muted">7-Day Attendance</small>
                            <div class="h5 mb-0">{{ $lastWeekAttendance }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <small class="text-muted">Payout Method</small>
                            <div class="small fw-semibold">{{ ucwords(str_replace('_', ' ', $site->payout_method ?? 'N/A')) }}</div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <h6>Payout Window</h6>
                <p class="text-muted mb-2">
                    <strong>Days:</strong> {{ $site->payout_window_start ?? 'Not set' }} - {{ $site->payout_window_end ?? 'Not set' }}<br>
                    <strong>Times:</strong> {{ $site->payout_opens_at ?? 'Not set' }} - {{ $site->payout_closes_at ?? 'Not set' }}
                </p>

                @if($currentPayCycle)
                    <div class="alert alert-info border">
                        <strong>Current Pay-Cycle:</strong> 
                        {{ $currentPayCycle->start_date?->format('d M') }} - {{ $currentPayCycle->end_date?->format('d M Y') }}
                        ({{ ucfirst($currentPayCycle->status) }})
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('owner.workforce', ['site_id' => $site->id]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-people"></i> View Workforce
                    </a>
                    <a href="{{ route('owner.workers.add') }}?site_id={{ $site->id }}" class="btn btn-outline-success">
                        <i class="bi bi-person-plus"></i> Add Worker
                    </a>
                    <a href="{{ route('owner.attendance', ['site_id' => $site->id]) }}" class="btn btn-outline-info">
                        <i class="bi bi-check2-square"></i> Mark Attendance
                    </a>
                    <a href="{{ route('owner.payroll', ['site_id' => $site->id]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-cash-coin"></i> View Payroll
                    </a>
                    <a href="{{ route('owner.sites.settings', $site) }}" class="btn btn-outline-dark">
                        <i class="bi bi-sliders"></i> Settings
                    </a>
                    @if(!$site->is_completed)
                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#completeModal">
                            <i class="bi bi-check-circle"></i> Mark Completed
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0">Recent Pay-Cycles</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Workers</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayCycles as $cycle)
                            <tr>
                                <td>{{ $cycle->start_date?->format('d M') }} - {{ $cycle->end_date?->format('d M') }}</td>
                                <td>{{ $cycle->worker_count }}</td>
                                <td>KES {{ number_format($cycle->total_amount, 2) }}</td>
                                <td><span class="badge text-bg-light border">{{ ucfirst($cycle->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No pay-cycles yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card kpi-card h-100">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0">Recent Invoices</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>{{ $invoice->period_start?->format('d M') }} - {{ $invoice->period_end?->format('d M Y') }}</td>
                                <td>KES {{ number_format($invoice->amount, 2) }}</td>
                                <td><span class="badge text-bg-light border">{{ ucfirst($invoice->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No invoices yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Complete Site Modal -->
@if(!$site->is_completed)
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('owner.sites.mark-completed', $site) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Mark Site as Completed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to mark <strong>{{ $site->name }}</strong> as completed?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason *</label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="E.g., Construction completed, final inspections passed"></textarea>
                    </div>
                    <div class="alert alert-warning small">
                        <strong>Note:</strong> This will prevent new workers from being assigned and no new pay-cycles can be created.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Mark Completed</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
