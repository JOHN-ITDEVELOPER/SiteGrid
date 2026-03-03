@extends('field.layouts.app')

@section('title', 'Inventory Control')
@section('page-title', 'Inventory & Progress Control')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Foreman Inventory Desk</h5>
    <form method="GET" class="d-flex gap-2">
        <select name="site_id" class="form-select">
            @foreach($sites as $site)
                <option value="{{ $site->id }}" {{ $selectedSiteId === $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary" type="submit">Switch</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="text-muted small">Low Stock Alerts</div><div class="h4 mb-0">{{ $lowStockCount }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="text-muted small">Requests (Recent)</div><div class="h4 mb-0">{{ $requests->count() }}</div></div></div>
    <div class="col-md-4"><div class="card kpi-card p-3"><div class="text-muted small">Movements (Recent)</div><div class="h4 mb-0">{{ $movements->count() }}</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="form-section">
            <div class="form-section-title">Create Procurement Request (Photo evidence required)</div>
            <form method="POST" action="{{ route('field.inventory.requests.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
                <div class="mb-2">
                    <label class="form-label">Purpose</label>
                    <textarea class="form-control" name="purpose" rows="2" required></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6"><input class="form-control" name="supplier_name" placeholder="Supplier (optional)"></div>
                    <div class="col-md-6"><input class="form-control" type="date" name="expected_delivery_date"></div>
                </div>

                <div id="requestItems">
                    <div class="row g-2 mb-2 req-item-row">
                        <div class="col-md-6">
                            <select class="form-select" name="items[0][item_id]" required>
                                <option value="">Select item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><input class="form-control" type="number" step="0.001" min="0.001" name="items[0][requested_quantity]" placeholder="Qty" required></div>
                        <div class="col-md-3"><input class="form-control" type="number" step="0.01" min="0" name="items[0][estimated_unit_cost]" placeholder="Est cost"></div>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="addReqItem">+ Add Item</button>
                <input class="form-control mb-2" type="file" name="evidences[]" accept="image/*" multiple required>
                <button class="btn btn-primary w-100" type="submit">Submit Request</button>
            </form>
        </div>

        <div class="form-section">
            <div class="form-section-title">Log Stock Usage (Photo evidence required)</div>
            <form method="POST" action="{{ route('field.inventory.usage.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <select class="form-select" name="item_id" required>
                            <option value="">Select item</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><input class="form-control" type="number" step="0.001" min="0.001" name="quantity" placeholder="Used quantity" required></div>
                </div>
                <textarea class="form-control mb-2" name="notes" rows="2" placeholder="Usage context" required></textarea>
                <input class="form-control mb-2" type="file" name="evidences[]" accept="image/*" multiple required>
                <button class="btn btn-primary w-100" type="submit">Record Usage</button>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-section">
            <div class="form-section-title">Daily Site Progress Log (Photo evidence required)</div>
            <form method="POST" action="{{ route('field.inventory.progress.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
                <input class="form-control mb-2" type="date" name="log_date" value="{{ now()->toDateString() }}" required>
                <input class="form-control mb-2" name="sector" placeholder="Sector e.g. Civil/Agri/Installations">
                <input class="form-control mb-2" name="title" placeholder="Progress title" required>
                <textarea class="form-control mb-2" name="description" rows="4" placeholder="Detailed progress update" required></textarea>
                <input class="form-control mb-2" type="file" name="evidences[]" accept="image/*" multiple required>
                <button class="btn btn-primary w-100" type="submit">Submit Progress Log</button>
            </form>
        </div>

        <div class="form-section">
            <div class="form-section-title">Current Stock</div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Threshold</th></tr></thead>
                    <tbody>
                    @forelse($stocks as $stock)
                        <tr>
                            <td>{{ $stock->item->name }} ({{ $stock->item->unit }})</td>
                            <td>{{ ucfirst($stock->item->category->type ?? '-') }}</td>
                            <td>{{ number_format($stock->quantity, 3) }}</td>
                            <td class="{{ $stock->low_stock_threshold > 0 && $stock->quantity <= $stock->low_stock_threshold ? 'text-danger fw-bold' : '' }}">{{ number_format($stock->low_stock_threshold, 3) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No stock records yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="form-section">
            <div class="form-section-title">Recent Procurement Requests</div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Ref</th><th>Status</th><th>Purpose</th><th>Created</th></tr></thead>
                    <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->reference }}</td>
                            <td><span class="badge text-bg-light border">{{ strtoupper($req->status) }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($req->purpose, 80) }}</td>
                            <td>{{ $req->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No requests yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const addBtn = document.getElementById('addReqItem');
        const wrap = document.getElementById('requestItems');
        let idx = 1;

        if (!addBtn || !wrap) return;

        addBtn.addEventListener('click', function () {
            const firstRow = wrap.querySelector('.req-item-row');
            if (!firstRow) return;
            const row = firstRow.cloneNode(true);
            row.querySelectorAll('select, input').forEach(function (el) {
                const name = el.getAttribute('name');
                if (name) el.setAttribute('name', name.replace(/\[(\d+)\]/, '[' + idx + ']'));
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                if (el.tagName === 'INPUT') el.value = '';
            });
            wrap.appendChild(row);
            idx++;
        });
    })();
</script>
@endsection
