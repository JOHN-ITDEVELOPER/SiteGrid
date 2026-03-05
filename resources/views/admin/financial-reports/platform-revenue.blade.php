@extends('admin.layouts.app')

@section('page-title', 'Platform Revenue Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Platform Revenue Report</h1>
        <p class="text-muted mb-0">Invoice payments received from owners to platform</p>
    </div>
    <a href="{{ route('admin.financial.export', ['type' => 'platform-revenue', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-outline-success">
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
            <label class="form-label">Payment Account</label>
            <select name="account_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Accounts</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ $accountId == $account->id ? 'selected' : '' }}>
                        {{ $account->name }} ({{ $account->shortcode }})
                    </option>
                @endforeach
            </select>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Total Invoices Paid</small>
                <h4 class="mb-0">{{ $summary['total_invoices_paid'] }}</h4>
                <small class="text-muted">Unique invoices</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Total Revenue Received</small>
                <h4 class="mb-0 text-success">KES {{ number_format($summary['total_revenue_received'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Received / Reconciled</small>
                <h4 class="mb-0">{{ $summary['received_count'] }} / {{ $summary['reconciled_count'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Disputed</small>
                <h4 class="mb-0 text-danger">{{ $summary['disputed_count'] }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- By Account Breakdown -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Revenue by Payment Account</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Account Name</th>
                            <th>Shortcode</th>
                            <th class="text-end">Transaction Count</th>
                            <th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byAccount as $accountData)
                            <tr>
                                <td class="fw-semibold">{{ $accountData['account_name'] }}</td>
                                <td>{{ $accountData['shortcode'] ?? 'N/A' }}</td>
                                <td class="text-end">{{ $accountData['count'] }}</td>
                                <td class="text-end fw-semibold">KES {{ number_format($accountData['total_amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center py-3">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Recent Invoice Payments -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">Recent Invoice Payments</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Owner</th>
                    <th>Site</th>
                    <th class="text-end">Amount</th>
                    <th>Receipt</th>
                    <th>Account</th>
                    <th>Status</th>
                    <th>Received At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $payment)
                    <tr>
                        <td class="fw-semibold">#{{ $payment->invoice_id }}</td>
                        <td>{{ $payment->invoice->site->owner->name ?? 'N/A' }}</td>
                        <td>{{ $payment->invoice->site->name ?? 'N/A' }}</td>
                        <td class="text-end">KES {{ number_format($payment->amount, 2) }}</td>
                        <td><code class="text-muted small">{{ $payment->mpesa_receipt_number }}</code></td>
                        <td>{{ $payment->platformAccount->name ?? 'Unknown' }}</td>
                        <td>
                            @if($payment->status === 'received')
                                <span class="badge bg-success">Received</span>
                            @elseif($payment->status === 'reconciled')
                                <span class="badge bg-primary">Reconciled</span>
                            @elseif($payment->status === 'disputed')
                                <span class="badge bg-danger">Disputed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $payment->received_at->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-muted text-center py-3">No invoice payments found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Navigation -->
<div class="mt-4 d-flex gap-2 justify-content-center">
    <a href="{{ route('admin.financial.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
    <a href="{{ route('admin.financial.revenue') }}" class="btn btn-outline-primary">View Payout Revenue</a>
</div>
@endsection
