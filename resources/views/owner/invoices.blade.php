@extends('owner.layouts.app')

@section('title', 'Invoices')
@section('page-title', 'Invoices & Billing')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="small text-muted">Unpaid</div><div class="h5 mb-0">KES {{ number_format($summary['unpaid'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="small text-muted">Overdue</div><div class="h5 mb-0 text-danger">KES {{ number_format($summary['overdue'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="small text-muted">Paid</div><div class="h5 mb-0 text-success">KES {{ number_format($summary['paid'], 2) }}</div></div></div>
</div>

<div class="card kpi-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('owner.invoices') }}">
            <div class="col-md-5">
                <select class="form-select" name="site_id">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ (string)$siteId === (string)$site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    @foreach(['unpaid','overdue','paid'] as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<div class="card kpi-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Period</th>
                    <th>Workers</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->site->name ?? 'Unknown Site' }}</td>
                        <td>{{ $invoice->period_start?->format('d M') }} - {{ $invoice->period_end?->format('d M Y') }}</td>
                        <td>{{ $invoice->worker_count }}</td>
                        <td>KES {{ number_format($invoice->amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $invoice->status === 'paid' ? 'text-bg-success' : ($invoice->status === 'overdue' ? 'text-bg-danger' : 'text-bg-warning') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td>{{ $invoice->created_at->format('d M Y') }}</td>
                        <td>
                            @if($invoice->status !== 'paid')
                                <form method="POST" action="{{ route('owner.invoices.upload-proof', $invoice) }}" enctype="multipart/form-data" class="d-grid gap-1">
                                    @csrf
                                    <input type="text" name="proof_reference" class="form-control form-control-sm" placeholder="Reference / Txn code">
                                    <input type="file" name="proof_file" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="confirm_2fa" value="1" id="invoice2fa{{ $invoice->id }}" required>
                                        <label class="form-check-label small" for="invoice2fa{{ $invoice->id }}">2FA confirmed</label>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary">Upload proof</button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">No invoices found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $invoices->links() }}</div>
@endsection
