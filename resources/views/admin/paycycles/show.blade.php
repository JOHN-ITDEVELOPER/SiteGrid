@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('admin.paycycles.index') }}" class="btn btn-link text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Pay Cycles
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Paycycle Summary -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pay Cycle Details</h5>
                    <form method="POST" action="{{ route('admin.paycycles.recalculate', $paycycle) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning" 
                                onclick="return confirm('Recalculate all totals for this pay cycle?')">
                            <i class="bi bi-arrow-clockwise"></i> Recalculate
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>Site:</strong><br>
                            {{ $paycycle->site->name }}
                        </div>
                        <div class="col-6">
                            <strong>Period:</strong><br>
                            {{ $paycycle->start_date->format('M d, Y') }} - {{ $paycycle->end_date->format('M d, Y') }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4">
                            <strong>Worker Days:</strong><br>
                            {{ $paycycle->total_worker_days }}
                        </div>
                        <div class="col-4">
                            <strong>Total Hours:</strong><br>
                            {{ $paycycle->total_hours ?? 0 }} hrs
                        </div>
                        <div class="col-4">
                            <strong>Status:</strong><br>
                            @php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'approved' => 'info',
                                    'rejected' => 'danger',
                                    'processing' => 'primary',
                                    'completed' => 'success',
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$paycycle->status] ?? 'secondary' }}">
                                {{ ucfirst($paycycle->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <strong>Total Amount:</strong><br>
                            <h4 class="text-primary mb-0">KES {{ number_format($paycycle->total_amount, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Payouts -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Worker Payouts ({{ $paycycle->payouts->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Days</th>
                                    <th>Hours</th>
                                    <th>Gross</th>
                                    <th>Net</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paycycle->payouts as $payout)
                                    <tr>
                                        <td>
                                            {{ $payout->worker->name }}<br>
                                            <small class="text-muted">{{ $payout->worker->phone }}</small>
                                        </td>
                                        <td>{{ $payout->days_worked }}</td>
                                        <td>{{ $payout->hours_worked ?? 0 }}</td>
                                        <td>KES {{ number_format($payout->gross_amount, 2) }}</td>
                                        <td>KES {{ number_format($payout->net_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $payoutColors = [
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $payoutColors[$payout->status] ?? 'secondary' }}">
                                                {{ ucfirst($payout->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($payout->status === 'failed')
                                                <form method="POST" action="{{ route('admin.payouts.retry', $payout) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" 
                                                            onclick="return confirm('Retry this payout?')">
                                                        <i class="bi bi-arrow-clockwise"></i> Retry
                                                    </button>
                                                </form>
                                            @endif
                                            @if($payout->failure_reason)
                                                <button class="btn btn-sm btn-secondary" 
                                                        data-bs-toggle="tooltip" 
                                                        title="{{ $payout->failure_reason }}">
                                                    <i class="bi bi-info-circle"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No payouts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Stats Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    @php
                        $successCount = $paycycle->payouts->where('status', 'completed')->count();
                        $failedCount = $paycycle->payouts->where('status', 'failed')->count();
                        $pendingCount = $paycycle->payouts->whereIn('status', ['pending', 'processing'])->count();
                        $totalPayouts = $paycycle->payouts->count();
                    @endphp

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Success Rate:</span>
                            <strong>{{ $totalPayouts > 0 ? round(($successCount / $totalPayouts) * 100) : 0 }}%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $totalPayouts > 0 ? ($successCount / $totalPayouts) * 100 : 0 }}%"></div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-check-circle text-success"></i> Completed:</span>
                        <strong>{{ $successCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-hourglass-split text-warning"></i> Pending:</span>
                        <strong>{{ $pendingCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-x-circle text-danger"></i> Failed:</span>
                        <strong>{{ $failedCount }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
