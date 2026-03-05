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
            <div class="text-muted small">Approved & Paid</div>
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
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Paid</option>
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
        <h6 class="mb-0">Worker Claims (Withdrawal Requests)</h6>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Requested Amount</th>
                    <th>Reason</th>
                    <th>Requested On</th>
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
                        <td>{{ $claim->site->name ?? '—' }}</td>
                        <td>
                            <strong>KES {{ number_format($claim->requested_amount, 2) }}</strong>
                        </td>
                        <td>
                            <small>{{ $claim->reason ? substr($claim->reason, 0, 40) . (strlen($claim->reason) > 40 ? '...' : '') : '—' }}</small>
                        </td>
                        <td>
                            <small class="text-muted">{{ optional($claim->requested_at)->format('M d, Y H:i') ?? '—' }}</small>
                        </td>
                        <td>
                            @php
                                $statusBadge = match($claim->status) {
                                    'pending_foreman' => 'text-bg-warning',
                                    'pending_owner' => 'text-bg-info',
                                    'approved' => 'text-bg-secondary',
                                    'paid' => 'text-bg-success',
                                    'rejected' => 'text-bg-danger',
                                    default => 'text-bg-secondary'
                                };
                                
                                $statusLabel = match($claim->status) {
                                    'pending_foreman' => 'Awaiting Foreman',
                                    'pending_owner' => 'Awaiting You',
                                    'approved' => 'Ready to Pay',
                                    'paid' => 'Completed',
                                    'rejected' => 'Rejected',
                                    default => ucfirst($claim->status)
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            @if($claim->status === 'pending_owner')
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $claim->id }}">
                                        Approve
                                    </button>
                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $claim->id }}">
                                        Reject
                                    </button>
                                </div>
                            @elseif($claim->status === 'approved')
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#payModal{{ $claim->id }}" title="Disburse payment">
                                        Pay
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $claim->id }}">
                                        Reject
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
                                        <p><strong>Site:</strong> {{ $claim->site->name }}</p>
                                        <p><strong>Amount:</strong> KES {{ number_format($claim->requested_amount, 2) }}</p>
                                        <p><strong>Reason:</strong> {{ $claim->reason ?? 'No reason provided' }}</p>
                                        <div class="mb-3">
                                            <label class="form-label">Notes (optional)</label>
                                            <textarea class="form-control" name="notes" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Approve & Proceed</button>
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
                                        <p><strong>Site:</strong> {{ $claim->site->name }}</p>
                                        <p><strong>Amount:</strong> KES {{ number_format($claim->requested_amount, 2) }}</p>
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

                    <!-- Pay Modal (for approved claims) -->
                    <div class="modal fade" id="payModal{{ $claim->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('owner.claims.action', $claim) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Process Payment</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Worker:</strong> {{ $claim->worker->name }}</p>
                                        <p><strong>Phone:</strong> {{ $claim->worker->phone }}</p>
                                        <p><strong>Amount to Pay:</strong> <span class="text-success fw-bold">KES {{ number_format($claim->requested_amount, 2) }}</span></p>
                                        <div class="alert alert-info small">
                                            Payment will be sent to the worker's M-Pesa number via B2C transfer.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Confirm & Send Payment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="7" class="text-muted text-center py-4">No claims found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $claims->links() }}</div>
@endsection
