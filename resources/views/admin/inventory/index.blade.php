@extends('admin.layouts.app')

@section('title', 'Inventory Command Center')
@section('page-title', 'Inventory Command Center')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Central Inventory Control</h5>
    <form method="GET" class="d-flex gap-2">
        <select name="site_id" class="form-select">
            <option value="">All Sites</option>
            @foreach($sites as $site)
                <option value="{{ $site->id }}" {{ (string) $siteId === (string) $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary" type="submit">Filter</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Sites Enabled</div><div class="h4 mb-0">{{ $metrics['sites_enabled'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Pending Requests</div><div class="h4 mb-0">{{ $metrics['pending_requests'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Approved Pending Receipt</div><div class="h4 mb-0">{{ $metrics['approved_not_received'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Low Stock Alerts</div><div class="h4 mb-0 text-danger">{{ $metrics['low_stock_alerts'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Movements Today</div><div class="h4 mb-0">{{ $metrics['movements_today'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Progress Logs Today</div><div class="h4 mb-0">{{ $metrics['progress_logs_today'] }}</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong>Low Stock Alerts</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Site</th><th>Item</th><th>Qty</th><th>Threshold</th></tr></thead>
                    <tbody>
                    @forelse($lowStock as $stock)
                        <tr>
                            <td>{{ $stock->site->name ?? '-' }}</td>
                            <td>{{ $stock->item->name ?? '-' }}</td>
                            <td>{{ number_format($stock->quantity, 3) }}</td>
                            <td>{{ number_format($stock->low_stock_threshold, 3) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No low stock alerts.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong>Recent Procurement Requests</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Ref</th><th>Site</th><th>Status</th><th>Requester</th></tr></thead>
                    <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->reference }}</td>
                            <td>{{ $req->site->name ?? '-' }}</td>
                            <td><span class="badge text-bg-light border">{{ strtoupper($req->status) }}</span></td>
                            <td>{{ $req->requester->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No procurement requests yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Recent Movements Ledger (Immutable)</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>When</th><th>Site</th><th>Type</th><th>Item</th><th>Qty</th><th>Balance</th><th>By</th></tr></thead>
                    <tbody>
                    @forelse($movements as $move)
                        <tr>
                            <td>{{ $move->created_at->format('M d, H:i') }}</td>
                            <td>{{ $move->site->name ?? '-' }}</td>
                            <td>{{ $move->movement_type }}</td>
                            <td>{{ $move->item->name ?? '-' }}</td>
                            <td>{{ number_format($move->quantity, 3) }}</td>
                            <td>{{ number_format($move->running_balance_after, 3) }}</td>
                            <td>{{ $move->performedBy->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No movement logs yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
