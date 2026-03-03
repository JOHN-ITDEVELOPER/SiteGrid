@extends('admin.layouts.app')

@section('content')
<div class="container-lg py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h2 text-dark">{{ $site->name }}</h1>
            <p class="text-muted">{{ $site->location }}</p>
        </div>
        <a href="{{ route('admin.sites.index') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <div class="row g-4">
        <!-- Site Overview -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Site Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Owner</p>
                            <p class="fw-semibold">{{ $site->owner->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Status</p>
                            <span class="badge bg-{{ $site->is_completed ? 'success' : 'warning' }}">
                                {{ $site->is_completed ? 'Completed' : 'Active' }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Payout Method</p>
                            <p class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $site->payout_method)) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Payout Window</p>
                            <p class="fw-semibold">{{ $site->payout_window_start }} - {{ $site->payout_window_end }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Workers -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Workers ({{ $site->workers()->whereNull('ended_at')->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($site->workers()->whereNull('ended_at')->get() as $worker)
                                <tr>
                                    <td>{{ $worker->user->name }}</td>
                                    <td>{{ $worker->user->phone }}</td>
                                    <td>
                                        @if($worker->is_foreman)
                                            <span class="badge bg-warning">Foreman</span>
                                        @else
                                            <span class="text-muted small">Worker</span>
                                        @endif
                                    </td>
                                    <td>KES {{ number_format($worker->daily_rate) }}/day or KES {{ number_format($worker->weekly_rate) }}/week</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No workers</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Metrics Sidebar -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <p class="text-muted small mb-1">Active Workers</p>
                    <h3 class="h2 text-primary mb-0">{{ $metrics['active_workers'] }}</h3>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Payouts</p>
                    <h3 class="h3 text-dark mb-0">KES {{ number_format($metrics['total_payouts']) }}</h3>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Pending Amount</p>
                    <h3 class="h3 text-warning mb-0">KES {{ number_format($metrics['pending_amount']) }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
