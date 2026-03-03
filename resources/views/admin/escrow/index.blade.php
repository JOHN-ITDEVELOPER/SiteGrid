@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Escrow Management</h1>
    </div>

    <!-- System Stats (Always Visible) -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Liquidity</h6>
                    <h3 class="mb-0">KES {{ number_format($systemStats['total_liquidity'], 2) }}</h3>
                    <small>Across all owners</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Active Owners</h6>
                    <h3 class="mb-0">{{ $systemStats['total_owners'] }}</h3>
                    <small>With wallets</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title">Held in Escrow</h6>
                    <h3 class="mb-0">KES {{ number_format($systemStats['total_held_payouts'], 2) }}</h3>
                    <small>{{ $stats['count_held'] }} payouts</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Disputed</h6>
                    <h3 class="mb-0">KES {{ number_format($systemStats['total_disputed_payouts'], 2) }}</h3>
                    <small>{{ $stats['count_disputed'] }} payouts</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'overview' ? 'active' : '' }}" href="?tab=overview" role="tab">
                <i class="bi bi-graph-up"></i> Overview
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'owners' ? 'active' : '' }}" href="?tab=owners" role="tab">
                <i class="bi bi-people"></i> Owners
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'transactions' ? 'active' : '' }}" href="?tab=transactions" role="tab">
                <i class="bi bi-receipt"></i> Transactions
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'held' ? 'active' : '' }}" href="?tab=held" role="tab">
                <i class="bi bi-exclamation-circle"></i> Held/Disputed
            </a>
        </li>
    </ul>

    <!-- Overview Tab -->
    @if($tab === 'overview')
    <div class="card mb-4">
        <div class="card-header bg-white border-0">
            <h6 class="mb-0">System Overview</h6>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <div class="small text-muted">Total Escrow Liquidity</div>
                        <div class="h4 mb-1">KES {{ number_format($systemStats['total_liquidity'], 2) }}</div>
                        <div class="small">Available in owner wallets</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <div class="small text-muted">Total Locked in Escrow</div>
                        <div class="h4 mb-1">KES {{ number_format($systemStats['total_held_payouts'] + $systemStats['total_disputed_payouts'], 2) }}</div>
                        <div class="small">{{ $stats['count_held'] + $stats['count_disputed'] }} payouts</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="small text-muted">Active Owners</div>
                        <div class="h4 mb-0">{{ $systemStats['total_owners'] }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="small text-muted">Total Transactions</div>
                        <div class="h4 mb-0">{{ $systemStats['total_transactions'] }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="small text-muted">System Health</div>
                        <div class="h4 mb-0 text-success">Balanced</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Owners Tab -->
    @if($tab === 'owners')
    <div class="card">
        <div class="card-header bg-white border-0">
            <h6 class="mb-0">Owner Escrow Accounts</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th>Available Balance</th>
                            <th>Held in Disputes</th>
                            <th>Total in System</th>
                            <th>Transactions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($owners as $owner)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $owner['owner_name'] }}</div>
                                    <small class="text-muted">{{ $owner['owner_email'] }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold">KES {{ number_format($owner['balance'], 2) }}</div>
                                </td>
                                <td>
                                    @if($owner['held_amount'] > 0 || $owner['disputed_amount'] > 0)
                                        <div class="fw-semibold text-warning">
                                            KES {{ number_format($owner['held_amount'] + $owner['disputed_amount'], 2) }}
                                        </div>
                                        <small class="text-muted">
                                            Held: {{ number_format($owner['held_amount'], 2) }} | Disputed: {{ number_format($owner['disputed_amount'], 2) }}
                                        </small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">KES {{ number_format($owner['total_in_system'], 2) }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $owner['transaction_count'] }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.show', $owner['owner_id']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No owners with wallets yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Transactions Tab -->
    @if($tab === 'transactions')
    <div class="card">
        <div class="card-header bg-white border-0">
            <h6 class="mb-0">Wallet Transactions (Last 100)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance Before</th>
                            <th>Balance After</th>
                            <th>Reference</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $txn['owner_name'] }}</div>
                                    <small class="text-muted">{{ $txn['owner_email'] }}</small>
                                </td>
                                <td>
                                    @if($txn['type'] === 'credit')
                                        <span class="badge bg-success">
                                            <i class="bi bi-arrow-up"></i> {{ ucfirst($txn['type']) }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning">
                                            <i class="bi bi-arrow-down"></i> {{ ucfirst($txn['type']) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold">KES {{ number_format($txn['amount'], 2) }}</span>
                                </td>
                                <td>KES {{ number_format($txn['balance_before'], 2) }}</td>
                                <td>KES {{ number_format($txn['balance_after'], 2) }}</td>
                                <td>
                                    @if($txn['description'])
                                        <small class="text-muted">{{ $txn['description'] }}</small>
                                    @elseif($txn['reference_type'])
                                        <small class="text-muted">{{ $txn['reference_type'] }} #{{ $txn['reference_id'] }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $txn['created_at']->format('M d, Y') }}</small><br>
                                    <small class="text-muted">{{ $txn['created_at']->diffForHumans() }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Held/Disputed Tab -->
    @if($tab === 'held')
    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title">Held in Escrow</h6>
                    <h3 class="mb-0">KES {{ number_format($stats['total_held'], 2) }}</h3>
                    <small>{{ $stats['count_held'] }} payouts</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Disputed</h6>
                    <h3 class="mb-0">KES {{ number_format($stats['total_disputed'], 2) }}</h3>
                    <small>{{ $stats['count_disputed'] }} payouts</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Locked</h6>
                    <h3 class="mb-0">KES {{ number_format($stats['total_held'] + $stats['total_disputed'], 2) }}</h3>
                    <small>{{ $stats['count_held'] + $stats['count_disputed'] }} payouts</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.escrow.index') }}" class="row g-3">
                <input type="hidden" name="tab" value="held">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search reference or worker..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="escrow_status" class="form-select">
                        <option value="">All Escrow Status</option>
                        <option value="held" {{ request('escrow_status') === 'held' ? 'selected' : '' }}>Held</option>
                        <option value="disputed" {{ request('escrow_status') === 'disputed' ? 'selected' : '' }}>Disputed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.escrow.index', ['tab' => 'held']) }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Escrow Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Worker</th>
                            <th>Site</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Held At</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($escrows as $payout)
                            <tr>
                                <td>{{ $payout->reference }}</td>
                                <td>
                                    {{ $payout->worker->name }}<br>
                                    <small class="text-muted">{{ $payout->worker->phone }}</small>
                                </td>
                                <td>{{ $payout->payCycle->site->name ?? 'N/A' }}</td>
                                <td>KES {{ number_format($payout->net_amount, 2) }}</td>
                                <td>
                                    @if($payout->escrow_status === 'held')
                                        <span class="badge bg-warning">Held</span>
                                    @elseif($payout->escrow_status === 'disputed')
                                        <span class="badge bg-danger">Disputed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($payout->escrow_status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payout->escrow_held_at)
                                        {{ $payout->escrow_held_at->format('M d, Y') }}<br>
                                        <small class="text-muted">{{ $payout->escrow_held_at->diffForHumans() }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($payout->escrow_reason)
                                        <button class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#reasonModal{{ $payout->id }}">
                                            View
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        @if(in_array($payout->escrow_status, ['held', 'disputed']))
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#releaseModal{{ $payout->id }}">
                                                <i class="bi bi-check-circle"></i> Release
                                            </button>
                                        @endif
                                        @if($payout->escrow_status === 'held')
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#disputeModal{{ $payout->id }}">
                                                <i class="bi bi-exclamation-triangle"></i> Dispute
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Reason Modal -->
                            <div class="modal fade" id="reasonModal{{ $payout->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Escrow Reason</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            {{ $payout->escrow_reason }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Release Modal -->
                            <div class="modal fade" id="releaseModal{{ $payout->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.payouts.release', $payout) }}">
                                            @csrf
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">Release from Escrow</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to release this payout from escrow?</p>
                                                <div class="alert alert-info">
                                                    <strong>Worker:</strong> {{ $payout->worker->name }}<br>
                                                    <strong>Amount:</strong> KES {{ number_format($payout->net_amount, 2) }}<br>
                                                    <strong>Reference:</strong> {{ $payout->reference }}
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Release Payout</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Dispute Modal -->
                            <div class="modal fade" id="disputeModal{{ $payout->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.payouts.dispute', $payout) }}">
                                            @csrf
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Mark as Disputed</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Dispute Reason *</label>
                                                    <textarea name="reason" class="form-control" rows="4" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Mark as Disputed</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No escrow payouts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $escrows->links() }}
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
