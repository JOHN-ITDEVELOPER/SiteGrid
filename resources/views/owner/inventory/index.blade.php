@extends('owner.layouts.app')

@section('title', 'Inventory & Procurement')
@section('page-title', 'Inventory & Procurement Control')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Owner Inventory Console</h5>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('owner.inventory.categories.index', ['site_id' => $siteId]) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-gear"></i> Manage Categories
        </a>
        <form method="GET" class="d-flex gap-2">
            <select name="site_id" class="form-select form-select-sm">
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ $siteId === $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-secondary btn-sm" type="submit">Switch</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="text-muted small">Low Stock Alerts</div><div class="h4 mb-0">{{ $lowStockCount }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="text-muted small">Pending Procurement Actions</div><div class="h4 mb-0">{{ $pendingRequests->count() }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="text-muted small">Recent Stock Movements</div><div class="h4 mb-0">{{ $recentMovements->count() }}</div></div></div>
</div>

<div class="card kpi-card mb-4">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Current Site Stock</h6></div>
    <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Threshold</th><th>Avg Cost</th></tr></thead>
            <tbody>
            @forelse($stocks as $stock)
                <tr>
                    <td>{{ $stock->item->name }} ({{ $stock->item->unit }})</td>
                    <td>{{ ucfirst($stock->item->category->type ?? '-') }}</td>
                    <td>{{ number_format($stock->quantity, 3) }}</td>
                    <td class="{{ $stock->low_stock_threshold > 0 && $stock->quantity <= $stock->low_stock_threshold ? 'text-danger fw-bold' : '' }}">{{ number_format($stock->low_stock_threshold, 3) }}</td>
                    <td>{{ number_format($stock->avg_unit_cost, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No stock records yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card kpi-card mb-4">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Procurement Requests (Approve / Reject / Receive)</h6></div>
    <div class="card-body">
        @forelse($pendingRequests as $req)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="fw-semibold">{{ $req->reference }} · <span class="badge text-bg-light border">{{ strtoupper($req->status) }}</span></div>
                        <div class="small text-muted">Requested by {{ $req->requester->name ?? 'N/A' }} · {{ $req->created_at->format('M d, Y H:i') }}</div>
                        <div class="small">{{ $req->purpose }}</div>
                    </div>
                </div>

                <div class="table-responsive mb-2">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Item</th><th>Req Qty</th><th>Approved</th><th>Delivered</th></tr></thead>
                        <tbody>
                        @foreach($req->items as $line)
                            <tr>
                                <td>{{ $line->item->name }} ({{ $line->item->unit }})</td>
                                <td>{{ number_format($line->requested_quantity, 3) }}</td>
                                <td>{{ number_format($line->approved_quantity ?? 0, 3) }}</td>
                                <td>{{ number_format($line->delivered_quantity ?? 0, 3) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if($req->status === 'requested')
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <form method="POST" action="{{ route('owner.inventory.requests.approve', $req) }}" enctype="multipart/form-data" class="border rounded p-2">
                                @csrf
                                <div class="small fw-semibold mb-1">Approve</div>
                                <input class="form-control form-control-sm mb-1" name="po_number" placeholder="PO Number" required>
                                <textarea class="form-control form-control-sm mb-1" name="decision_notes" rows="2" placeholder="Approval notes"></textarea>
                                <input class="form-control form-control-sm mb-1" type="file" name="evidences[]" accept="image/*" multiple required>
                                <button class="btn btn-sm btn-success w-100" type="submit">Approve Request</button>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <form method="POST" action="{{ route('owner.inventory.requests.reject', $req) }}" enctype="multipart/form-data" class="border rounded p-2">
                                @csrf
                                <div class="small fw-semibold mb-1">Reject</div>
                                <textarea class="form-control form-control-sm mb-1" name="rejection_reason" rows="3" placeholder="Reason" required></textarea>
                                <input class="form-control form-control-sm mb-1" type="file" name="evidences[]" accept="image/*" multiple required>
                                <button class="btn btn-sm btn-danger w-100" type="submit">Reject Request</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if(in_array($req->status, ['approved', 'po_issued']))
                    <form method="POST" action="{{ route('owner.inventory.requests.receive', $req) }}" enctype="multipart/form-data" class="border rounded p-2 mt-2">
                        @csrf
                        <div class="small fw-semibold mb-1">Receive Delivery (adds stock to immutable ledger)</div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6"><input class="form-control form-control-sm" name="delivery_reference" placeholder="Delivery Ref" required></div>
                            <div class="col-md-6"><input class="form-control form-control-sm" name="notes" placeholder="Delivery notes"></div>
                        </div>

                        @foreach($req->items as $index => $line)
                            <div class="row g-2 mb-1">
                                <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $line->item_id }}">
                                <div class="col-md-5"><input class="form-control form-control-sm" value="{{ $line->item->name }} ({{ $line->item->unit }})" readonly></div>
                                <div class="col-md-3"><input class="form-control form-control-sm" type="number" step="0.001" min="0.001" name="items[{{ $index }}][delivered_quantity]" placeholder="Delivered qty" required></div>
                                <div class="col-md-4"><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="items[{{ $index }}][unit_cost]" placeholder="Unit cost"></div>
                            </div>
                        @endforeach
                        <input class="form-control form-control-sm mt-2 mb-2" type="file" name="evidences[]" accept="image/*" multiple required>
                        <button class="btn btn-sm btn-primary w-100" type="submit">Confirm Receipt & Stock In</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-muted">No pending procurement requests.</div>
        @endforelse
    </div>
</div>

<div class="card kpi-card">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Recent Stock Movements</h6></div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>When</th><th>Type</th><th>Item</th><th>Qty</th><th>Balance</th><th>By</th></tr></thead>
            <tbody>
            @forelse($recentMovements as $move)
                <tr>
                    <td>{{ $move->created_at->format('M d, H:i') }}</td>
                    <td>{{ $move->movement_type }}</td>
                    <td>{{ $move->item->name }}</td>
                    <td>{{ number_format($move->quantity, 3) }}</td>
                    <td>{{ number_format($move->running_balance_after, 3) }}</td>
                    <td>{{ $move->performedBy->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No movements yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
