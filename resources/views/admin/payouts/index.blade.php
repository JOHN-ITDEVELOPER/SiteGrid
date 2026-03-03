@extends('admin.layouts.app')

@section('content')
<div class="container-lg py-5">
    <div class="mb-5">
        <h1 class="h2 text-dark mb-3">Payouts</h1>
        
        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="queued" {{ request('status') == 'queued' ? 'selected' : '' }}>Queued</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Site</label>
                        <select name="site_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Sites</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">From</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">To</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payouts Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Worker</th>
                        <th>Site</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Transaction Ref</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr>
                            <td>
                                <small class="fw-semibold">{{ $payout->worker->name }}</small><br>
                                <small class="text-muted">{{ $payout->worker->phone }}</small>
                            </td>
                            <td><small>{{ $payout->payCycle->site->name }}</small></td>
                            <td>
                                <small class="text-muted">
                                    {{ $payout->payCycle->start_date->format('M d') }} - {{ $payout->payCycle->end_date->format('M d, Y') }}
                                </small>
                            </td>
                            <td>
                                <small class="fw-semibold">KES {{ number_format($payout->net_amount) }}</small><br>
                                <small class="text-muted">Gross: KES {{ number_format($payout->gross_amount) }}</small>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'approved' => 'info',
                                        'queued' => 'secondary',
                                        'processing' => 'primary',
                                        'paid' => 'success',
                                        'failed' => 'danger',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$payout->status] ?? 'secondary' }}">
                                    {{ ucfirst($payout->status) }}
                                </span>
                            </td>
                            <td>
                                <small class="font-monospace" title="{{ $payout->transaction_ref }}">
                                    {{ $payout->transaction_ref ? substr($payout->transaction_ref, 0, 10) . '...' : '-' }}
                                </small>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $payout->id }}">
                                    View
                                </a>

                                <!-- Detail Modal -->
                                <div class="modal fade" id="detailModal{{ $payout->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Payout Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Worker:</strong> {{ $payout->worker->name }}</p>
                                                <p><strong>Site:</strong> {{ $payout->payCycle->site->name }}</p>
                                                <p><strong>Period:</strong> {{ $payout->payCycle->start_date->format('M d, Y') }} - {{ $payout->payCycle->end_date->format('M d, Y') }}</p>
                                                <p><strong>Gross Amount:</strong> KES {{ number_format($payout->gross_amount) }}</p>
                                                <p><strong>Fees:</strong> KES {{ number_format($payout->fees) }}</p>
                                                <p><strong>Net Amount:</strong> KES {{ number_format($payout->net_amount) }}</p>
                                                <p><strong>Status:</strong> <span class="badge bg-{{ $statusColors[$payout->status] ?? 'secondary' }}">{{ ucfirst($payout->status) }}</span></p>
                                                <p><strong>Transaction Ref:</strong> <code>{{ $payout->transaction_ref ?? 'N/A' }}</code></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No payouts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $payouts->links() }}
    </div>
</div>
@endsection
