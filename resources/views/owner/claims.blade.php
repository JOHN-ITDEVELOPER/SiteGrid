@extends('owner.layouts.app')

@section('title', 'Claims Center')
@section('page-title', 'Worker Claims & Requests')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Pending Claims</div>
            <div class="h4 mb-0">{{ $summary['pending'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Approved</div>
            <div class="h4 mb-0 text-success">{{ $summary['approved'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card kpi-card p-3">
            <div class="text-muted small">Rejected</div>
            <div class="h4 mb-0 text-danger">{{ $summary['rejected'] }}</div>
        </div>
    </div>
</div>

<div class="card kpi-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('owner.claims') }}">
            <div class="col-md-6">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Approved</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-6 d-grid">
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card kpi-card">
    <div class="card-header bg-white border-0">
        <h6 class="mb-0">Worker Claims (Payout Requests)</h6>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Pay Period</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $claim)
                    <tr>
                        <td>
                            <strong>{{ $claim->worker->name ?? 'Unknown' }}</strong><br>
                            <small class="text-muted">{{ $claim->worker->phone ?? '—' }}</small>
                        </td>
                        <td>{{ $claim->payCycle->site->name ?? '—' }}</td>
                        <td>
                            <small>
                                {{ $claim->payCycle->start_date->format('d M') }} - 
                                {{ $claim->payCycle->end_date->format('d M Y') }}
                            </small>
                        </td>
                        <td>
                            <strong>KES {{ number_format($claim->net_amount, 2) }}</strong><br>
                            <small class="text-muted">Gross: {{ number_format($claim->gross_amount, 2) }}</small>
                        </td>
                        <td>
                            @php
                                $statusBadge = match($claim->status) {
                                    'pending' => 'text-bg-warning',
                                    'completed' => 'text-bg-success',
                                    'failed' => 'text-bg-danger',
                                    default => 'text-bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ ucfirst($claim->status) }}</span>
                        </td>
                        <td>
                            @if($claim->status === 'pending')
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $claim->id }}">
                                        Approve
                                    </button>
                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $claim->id }}">
                                        Reject
                                    </button>
                                    <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#overrideModal{{ $claim->id }}" title="Bypass withdrawal window for emergency approval">
                                        Override
                                    </button>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Approve Modal -->
                    <div class="modal fade" id="approveModal{{ $claim->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('owner.claims.action', $claim) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Approve Claim</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Worker:</strong> {{ $claim->worker->name }}</p>
                                        <p><strong>Amount:</strong> KES {{ number_format($claim->net_amount, 2) }}</p>
                                        <div class="mb-3">
                                            <label class="form-label">Notes (optional)</label>
                                            <textarea class="form-control" name="notes" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Approve Claim</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal{{ $claim->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('owner.claims.action', $claim) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Reject Claim</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Worker:</strong> {{ $claim->worker->name }}</p>
                                        <p><strong>Amount:</strong> KES {{ number_format($claim->net_amount, 2) }}</p>
                                        <div class="mb-3">
                                            <label class="form-label">Reason for rejection *</label>
                                            <textarea class="form-control" name="notes" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Reject Claim</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Override Withdrawal Window Modal -->
                    <div class="modal fade" id="overrideModal{{ $claim->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('owner.claims.override-window', $claim) }}">
                                    @csrf
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title">
                                            <i class="bi bi-exclamation-circle"></i> Override Withdrawal Window
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-warning small mb-3">
                                            <strong>⚠️ Emergency Approval:</strong> This bypasses the standard withdrawal window and will deduct from your owner wallet.
                                        </div>
                                        <p><strong>Worker:</strong> {{ $claim->worker->name }}</p>
                                        <p><strong>Amount:</strong> KES {{ number_format($claim->requested_amount, 2) }}</p>
                                        <p class="small text-muted">
                                            <strong>Total Cost:</strong> KES {{ number_format($claim->requested_amount * 1.05 + 25, 2) }}<br>
                                            (includes 5% platform fee + KES 25 M-Pesa fee)
                                        </p>
                                        <div class="mb-3">
                                            <label class="form-label">Reason for emergency override *</label>
                                            <textarea class="form-control" name="override_reason" rows="3" placeholder="e.g., Worker medical emergency, urgent family matter..." required minlength="5" maxlength="500"></textarea>
                                            <small class="form-text text-muted">Required for audit trail. Worker will be notified via SMS.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-info">Approve & Bypass Window</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="6" class="text-muted">No claims found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $claims->links() }}</div>
@endsection
