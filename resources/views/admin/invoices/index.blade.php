@extends('admin.layouts.app')

@section('content')
<div class="container-lg py-5">
    <div class="mb-5">
        <h1 class="h2 text-dark mb-3">Invoices</h1>
        
        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
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

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Outstanding</p>
                    <h3 class="h3 text-warning mb-0">KES {{ number_format($unpaidTotal) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Paid This Month</p>
                    <h3 class="h3 text-success mb-0">KES {{ number_format($paidThisMonth) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Overdue</p>
                    <h3 class="h3 text-danger mb-0">KES {{ number_format($overdueTotal) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Revenue</p>
                    <h3 class="h3 text-primary mb-0">KES {{ number_format($totalRevenue) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Site</th>
                        <th>Period</th>
                        <th>Workers</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td><small class="font-monospace fw-semibold">INV-{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</small></td>
                            <td><small>{{ $invoice->site->name }}</small></td>
                            <td>
                                <small class="text-muted">
                                    {{ $invoice->period_start->format('M d') }} - {{ $invoice->period_end->format('M d, Y') }}
                                </small>
                            </td>
                            <td><small>{{ $invoice->worker_count }}</small></td>
                            <td><small class="fw-semibold">KES {{ number_format($invoice->amount) }}</small></td>
                            <td>
                                @php
                                    $statusColors = [
                                        'unpaid' => 'warning',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#invoiceModal{{ $invoice->id }}">
                                    View
                                </a>

                                <!-- Invoice Modal -->
                                <div class="modal fade" id="invoiceModal{{ $invoice->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Invoice INV-{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Site:</strong> {{ $invoice->site->name }}</p>
                                                        <p><strong>Owner:</strong> {{ $invoice->site->owner->name }}</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Period:</strong> {{ $invoice->period_start->format('M d, Y') }} - {{ $invoice->period_end->format('M d, Y') }}</p>
                                                        <p><strong>Due Date:</strong> {{ $invoice->due_date?->format('M d, Y') ?? 'Not set' }}</p>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <td>Workers</td>
                                                            <td class="text-end">{{ $invoice->worker_count }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Rate per Worker</td>
                                                            <td class="text-end">KES 50</td>
                                                        </tr>
                                                        <tr class="fw-semibold">
                                                            <td>Total</td>
                                                            <td class="text-end">KES {{ number_format($invoice->amount) }}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <p><strong>Status:</strong> <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }}">{{ ucfirst($invoice->status) }}</span></p>
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
                            <td colspan="8" class="text-center text-muted py-4">No invoices found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
