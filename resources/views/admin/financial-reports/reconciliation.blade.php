@extends('admin.layouts.app')

@section('page-title', 'Payout Reconciliation')

@section('content')
<style>
    .btn-xs {
        font-size: 0.75rem;
        padding: 0.35rem 0.6rem;
        white-space: nowrap;
    }
    
    table tbody td {
        vertical-align: middle;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Payout Reconciliation</h1>
        <p class="text-muted mb-0">Verify payout status, approvals, and processing details</p>
    </div>
    <a href="{{ route('admin.financial.export', ['type' => 'payouts', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-success">
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
            <label class="form-label">Status</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="queued" {{ $status === 'queued' ? 'selected' : '' }}>Queued</option>
                <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
    </form>
</div>

<!-- Reconciliation Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Period</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Approval</th>
                    <th>Approved By</th>
                    <th>Approved At</th>
                    <th>Paid At</th>
                    <th>Ref</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payouts as $payout)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $payout->worker->id) }}" class="text-decoration-none">
                                {{ $payout->worker->name }}
                            </a>
                        </td>
                        <td>{{ $payout->payCycle->site->name }}</td>
                        <td>
                            <small>
                                {{ $payout->payCycle->start_date->format('M d') }} -<br>
                                {{ $payout->payCycle->end_date->format('M d, Y') }}
                            </small>
                        </td>
                        <td class="text-end fw-semibold">KES {{ number_format($payout->net_amount, 2) }}</td>
                        <td>
                            @php
                                $statusColor = match($payout->status) {
                                    'paid' => 'success',
                                    'failed' => 'danger',
                                    'pending' => 'warning',
                                    'approved' => 'info',
                                    'queued' => 'primary',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $statusColor }}">{{ ucfirst($payout->status) }}</span>
                        </td>
                        <td>
                            @if($payout->approval_status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($payout->approval_status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-warning">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($payout->approvedBy)
                                <small>{{ $payout->approvedBy->name }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if($payout->approved_at)
                                <small>{{ $payout->approved_at->format('M d, Y H:i') }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if($payout->paid_at)
                                <small>{{ $payout->paid_at->format('M d, Y') }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            @if($payout->transaction_ref)
                                <small class="font-monospace text-truncate d-inline-block" style="max-width: 80px;" title="{{ $payout->transaction_ref }}">
                                    {{ substr($payout->transaction_ref, 0, 8) }}...
                                </small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td class="text-center" style="white-space: nowrap;">
                            @if($payout->approval_status === 'pending' && $payout->status === 'pending')
                                <button class="btn btn-xs btn-success" data-bs-toggle="modal" data-bs-target="#approveConfirmModal{{ $payout->id }}" title="Approve this payout">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button class="btn btn-xs btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $payout->id }}" title="Reject this payout">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            @else
                                <button class="btn btn-xs btn-info" data-bs-toggle="modal" data-bs-target="#detailModal{{ $payout->id }}" title="View payout details">
                                    <i class="bi bi-eye"></i> Details
                                </button>
                            @endif
                        </td>
                    </tr>

                    <!-- Approve Confirmation Modal -->
                    @if($payout->approval_status === 'pending' && $payout->status === 'pending')
                        <div class="modal fade" id="approveConfirmModal{{ $payout->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">Confirm Payout Approval</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-3">Are you sure you want to approve this payout?</p>
                                        <div class="alert alert-info">
                                            <strong>{{ $payout->worker->name }}</strong><br>
                                            <small>{{ $payout->payCycle->site->name }}</small>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Amount:</strong><br>
                                                KES {{ number_format($payout->net_amount, 2) }}
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong>Period:</strong><br>
                                                {{ $payout->payCycle->start_date->format('M d') }} - {{ $payout->payCycle->end_date->format('M d, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <form action="{{ route('admin.payouts.approve', $payout) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Approve Payout
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Detail Modal -->
                    <div class="modal fade" id="detailModal{{ $payout->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Payout Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">Worker:</dt>
                                        <dd class="col-sm-8">{{ $payout->worker->name }}</dd>

                                        <dt class="col-sm-4">Site:</dt>
                                        <dd class="col-sm-8">{{ $payout->payCycle->site->name }}</dd>

                                        <dt class="col-sm-4">Gross Amount:</dt>
                                        <dd class="col-sm-8">KES {{ number_format($payout->gross_amount, 2) }}</dd>

                                        <dt class="col-sm-4">Platform Fee:</dt>
                                        <dd class="col-sm-8">KES {{ number_format($payout->platform_fee, 2) }}</dd>

                                        <dt class="col-sm-4">MPesa Fee:</dt>
                                        <dd class="col-sm-8">KES {{ number_format($payout->mpesa_fee, 2) }}</dd>

                                        <dt class="col-sm-4">Net Amount:</dt>
                                        <dd class="col-sm-8 fw-semibold">KES {{ number_format($payout->net_amount, 2) }}</dd>

                                        <dt class="col-sm-4">Status:</dt>
                                        <dd class="col-sm-8">{{ ucfirst($payout->status) }}</dd>

                                        <dt class="col-sm-4">Approval:</dt>
                                        <dd class="col-sm-8">{{ ucfirst($payout->approval_status ?? 'pending') }}</dd>

                                        @if($payout->approvedBy)
                                            <dt class="col-sm-4">Approved By:</dt>
                                            <dd class="col-sm-8">{{ $payout->approvedBy->name }}</dd>

                                            <dt class="col-sm-4">Approved At:</dt>
                                            <dd class="col-sm-8">{{ $payout->approved_at->format('M d, Y H:i') }}</dd>
                                        @endif

                                        @if($payout->transaction_ref)
                                            <dt class="col-sm-4">Ref:</dt>
                                            <dd class="col-sm-8"><small class="font-monospace">{{ $payout->transaction_ref }}</small></dd>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    @if($payout->approval_status === 'pending' && $payout->status === 'pending')
                        <div class="modal fade" id="rejectModal{{ $payout->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title">Reject Payout</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-3"><strong>This action cannot be reversed.</strong> The payout will be marked as failed.</p>
                                        <div class="alert alert-warning">
                                            <strong>{{ $payout->worker->name }}</strong><br>
                                            <small>{{ $payout->payCycle->site->name }}</small>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <strong>Amount:</strong><br>
                                                KES {{ number_format($payout->net_amount, 2) }}
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong>Period:</strong><br>
                                                {{ $payout->payCycle->start_date->format('M d') }} - {{ $payout->payCycle->end_date->format('M d, Y') }}
                                            </div>
                                        </div>
                                        <form id="rejectForm{{ $payout->id }}" action="{{ route('admin.payouts.reject', $payout) }}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                                                <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Enter the reason for rejecting this payout..." required></textarea>
                                                <small class="text-muted">The worker will see this reason.</small>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#rejectConfirmModal{{ $payout->id }}" data-bs-dismiss="modal" onclick="prepareRejectConfirm{{ $payout->id }}()">
                                            <i class="bi bi-exclamation-triangle"></i> Next: Confirm Rejection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Confirmation Modal -->
                        <div class="modal fade" id="rejectConfirmModal{{ $payout->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title"><i class="bi bi-exclamation-circle"></i> Confirm Rejection</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-danger">
                                            <strong>⚠️ Warning</strong><br>
                                            You are about to reject this payout. This action cannot be reversed.
                                        </div>
                                        <p class="mb-2"><strong>Worker:</strong> {{ $payout->worker->name }}</p>
                                        <p class="mb-2"><strong>Site:</strong> {{ $payout->payCycle->site->name }}</p>
                                        <p class="mb-3"><strong>Amount:</strong> KES {{ number_format($payout->net_amount, 2) }}</p>
                                        <p class="mb-0"><strong>Your Reason:</strong></p>
                                        <div class="alert alert-light border mt-2">
                                            <small id="reasonText{{ $payout->id }}"></small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                                        <button type="button" class="btn btn-danger" onclick="document.getElementById('rejectForm{{ $payout->id }}').submit()">
                                            <i class="bi bi-check-circle"></i> Confirm Rejection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            function prepareRejectConfirm{{ $payout->id }}() {
                                const reason = document.querySelector('#rejectForm{{ $payout->id }} textarea').value;
                                document.getElementById('reasonText{{ $payout->id }}').textContent = reason || '(No reason provided)';
                            }
                        </script>
                    @endif
                @empty
                    <tr>
                        <td colspan="11" class="text-muted text-center py-4">No payouts found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center mt-3">
    {{ $payouts->links() }}
</div>

<!-- Navigation -->
<div class="mt-4 d-flex gap-2 justify-content-center">
    <a href="{{ route('admin.financial.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
    <a href="{{ route('admin.payouts.index') }}" class="btn btn-outline-primary">View All Payouts</a>
</div>
@endsection
