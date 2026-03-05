@extends('owner.layouts.app')

@section('title', 'Inventory & Procurement')
@section('page-title', 'Inventory & Procurement Control')

@push('styles')
<style>
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
<div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center gap-3 mb-3">
    <h5 class="mb-0">Owner Inventory Console</h5>
    <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center">
        <a href="{{ route('owner.inventory.categories.index', ['site_id' => $siteId]) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-gear"></i> Manage Categories
        </a>
        <form method="GET" class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
            <select name="site_id" class="form-select form-select-sm flex-grow-1 flex-md-grow-0">
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" {{ $siteId === $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-secondary btn-sm w-100 w-md-auto" type="submit">Switch</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3"><div class="card kpi-card p-3"><div class="text-muted small">Categories</div><div class="h4 mb-0">{{ $categoryCount }}</div></div></div>
    <div class="col-12 col-sm-6 col-md-3"><div class="card kpi-card p-3"><div class="text-muted small">Active Items</div><div class="h4 mb-0">{{ $activeItemCount }}</div></div></div>
    <div class="col-12 col-sm-6 col-md-3"><div class="card kpi-card p-3"><div class="text-muted small">Low Stock Alerts</div><div class="h4 mb-0">{{ $lowStockCount }}</div></div></div>
    <div class="col-12 col-sm-6 col-md-3"><div class="card kpi-card p-3"><div class="text-muted small">Pending Procurement Actions</div><div class="h4 mb-0">{{ $pendingRequests->count() }}</div></div></div>
</div>

<div class="card kpi-card mb-4">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Recent Site Progress Logs</h6></div>
    <div class="card-body">
        @forelse($progressLogs as $log)
            <div class="border rounded p-3 mb-3 hover-shadow" style="transition: all 0.2s;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <a href="{{ route('owner.inventory.progress.show', $log) }}" class="text-decoration-none text-dark">
                            <div class="fw-semibold">{{ $log->title }}</div>
                        </a>
                        <div class="small text-muted">
                            <span class="badge text-bg-light">{{ $log->log_date }}</span>
                            @if($log->sector)
                                · <span class="badge text-bg-info">{{ $log->sector }}</span>
                            @endif
                            · By {{ $log->creator->name ?? 'N/A' }}
                        </div>
                    </div>
                    <span class="badge text-bg-{{ $log->status === 'submitted' ? 'warning' : 'success' }}">{{ strtoupper($log->status) }}</span>
                </div>
                <div class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($log->description, 150) }}</div>
                <a href="{{ route('owner.inventory.progress.show', $log) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View Details & Photos
                </a>
            </div>
        @empty
            <div class="text-muted">No progress logs submitted yet.</div>
        @endforelse
    </div>
</div>

<!-- Tabbed Interface -->
<div class="card kpi-card mb-4">
    <ul class="nav nav-tabs" id="inventoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activityPane" type="button" role="tab">
                <i class="bi bi-clock-history"></i> Activity Log
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stockPane" type="button" role="tab">
                <i class="bi bi-box"></i> Current Stock
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requestsPane" type="button" role="tab">
                <i class="bi bi-file-earmark"></i> Requests
            </button>
        </li>
    </ul>

    <div class="tab-content" id="inventoryTabContent">
        
        <!-- ACTIVITY LOG TAB -->
        <div class="tab-pane fade show active" id="activityPane" role="tabpanel">
            <div class="card-body">
                
                <!-- Direct Stock In Form -->
                <div class="mb-4">
                    <h6 class="mb-3"><i class="bi bi-box-arrow-in-down"></i> Direct Stock In (Owner Purchase / Emergency Top-up)</h6>
                    <form method="POST" action="{{ route('owner.inventory.direct-stock-in') }}" enctype="multipart/form-data" class="border rounded p-3 bg-light">
                        @csrf
                        <input type="hidden" name="site_id" value="{{ $siteId }}">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Item</label>
                                <select name="item_id" class="form-select form-select-sm" required>
                                    <option value="">Select item</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Quantity</label>
                                <input type="number" class="form-control form-control-sm" name="quantity" step="0.001" min="0.001" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Unit Cost (opt.)</label>
                                <input type="number" class="form-control form-control-sm" name="unit_cost" step="0.01" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Reference</label>
                                <input type="text" class="form-control form-control-sm" name="reference" placeholder="Invoice/Receipt/Manual" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Reason / Notes</label>
                                <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Why this direct stock-in is being recorded" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Evidence Photos</label>
                                <input class="form-control form-control-sm" type="file" name="evidences[]" accept="image/*" multiple required>
                                <div class="small text-muted mt-1">At least one photo required.</div>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-primary mt-3" type="submit">
                            <i class="bi bi-check-circle"></i> Record Direct Stock In
                        </button>
                    </form>
                </div>

                <!-- Recent Stock Movements with Evidence -->
                <div>
                    <h6 class="mb-3"><i class="bi bi-arrow-left-right"></i> All Stock Movements (In/Out/Adjustments)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Type</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Balance</th>
                                    <th>By</th>
                                    <th>Photos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $move)
                                    <tr>
                                        <td>{{ $move->created_at->format('M d, H:i') }}</td>
                                        <td>
                                            @php
                                                $badge = [
                                                    'procurement_in' => 'info',
                                                    'adjustment_in' => 'success',
                                                    'transfer_in' => 'secondary',
                                                    'usage_out' => 'warning',
                                                    'adjustment_out' => 'danger',
                                                    'transfer_out' => 'secondary',
                                                ][$move->movement_type] ?? 'light';
                                                $label = [
                                                    'procurement_in' => 'Procurement In',
                                                    'adjustment_in' => 'Direct Stock In',
                                                    'transfer_in' => 'Transfer In',
                                                    'usage_out' => 'Usage Out',
                                                    'adjustment_out' => 'Adjustment Out',
                                                    'transfer_out' => 'Transfer Out',
                                                ][$move->movement_type] ?? $move->movement_type;
                                            @endphp
                                            <span class="badge text-bg-{{ $badge }} text-white">{{ $label }}</span>
                                        </td>
                                        <td>{{ $move->item->name }}</td>
                                        <td>{{ number_format($move->quantity, 3) }}</td>
                                        <td>{{ number_format($move->running_balance_after, 3) }}</td>
                                        <td><small>{{ $move->performedBy->name ?? '-' }}</small></td>
                                        <td>
                                            @if($move->evidences->count() > 0)
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#evidenceModal{{ $move->id }}">
                                                    <i class="bi bi-images"></i> {{ $move->evidences->count() }}
                                                </button>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- Evidence Modal -->
                                    @if($move->evidences->count() > 0)
                                        <div class="modal fade" id="evidenceModal{{ $move->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            Evidence Photos · {{ $move->item->name }}
                                                            <span class="badge text-bg-light">{{ $move->movement_type }}</span>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            @foreach($move->evidences as $evidence)
                                                                <div class="col-md-6">
                                                                    <div class="card h-100">
                                                                        <img src="{{ asset('storage/' . $evidence->file_path) }}" class="card-img-top" style="max-height: 250px; object-fit: cover;">
                                                                        <div class="card-body">
                                                                            <p class="card-text small">
                                                                                @if($evidence->caption)
                                                                                    <strong>{{ $evidence->caption }}</strong>
                                                                                @endif
                                                                            </p>
                                                                            <a href="{{ asset('storage/' . $evidence->file_path) }}" download class="btn btn-sm btn-outline-secondary w-100">
                                                                                <i class="bi bi-download"></i> Download
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <tr><td colspan="7" class="text-muted text-center py-3">No stock movements yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CURRENT STOCK TAB -->
        <div class="tab-pane fade" id="stockPane" role="tabpanel">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Current Qty</th>
                                <th>Reorder Point</th>
                                <th>Avg Cost</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stocks as $stock)
                                <tr class="{{ $stock->low_stock_threshold > 0 && $stock->quantity <= $stock->low_stock_threshold ? 'table-danger' : '' }}">
                                    <td>{{ $stock->item->name }} ({{ $stock->item->unit }})</td>
                                    <td>{{ ucfirst($stock->item->category->type ?? '-') }}</td>
                                    <td>
                                        <strong>{{ number_format($stock->quantity, 3) }}</strong>
                                        @if($stock->low_stock_threshold > 0 && $stock->quantity <= $stock->low_stock_threshold)
                                            <span class="badge text-bg-danger ms-2">LOW</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($stock->low_stock_threshold, 3) }}</td>
                                    <td>{{ number_format($stock->avg_unit_cost, 2) }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#thresholdModal{{ $stock->id }}">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>

                                <!-- Edit Threshold Modal -->
                                <div class="modal fade" id="thresholdModal{{ $stock->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Set Reorder Point: {{ $stock->item->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="{{ route('owner.inventory.update-threshold', $stock) }}">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-body">
                                                    <label class="form-label"><strong>Minimum Reorder Quantity</strong></label>
                                                    <div class="input-group">
                                                        <input type="number" class="form-control" name="low_stock_threshold" step="0.001" min="0" value="{{ $stock->low_stock_threshold }}" required>
                                                        <span class="input-group-text">{{ $stock->item->unit }}</span>
                                                    </div>
                                                    <small class="form-text text-muted mt-2">
                                                        When stock falls to or below this level, an alert will be shown. Set to 0 to disable alerts.
                                                    </small>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save Reorder Point</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-3">No stock records yet. Add stock through Direct Stock In or Procurement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- REQUESTS TAB -->
        <div class="tab-pane fade" id="requestsPane" role="tabpanel">
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

                        @if(in_array($req->status, ['requested', 'rejected']))
                            <form method="POST" action="{{ route('owner.inventory.requests.destroy', $req) }}" class="mt-2" onsubmit="return confirm('Delete this procurement request? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                    <i class="bi bi-trash"></i> Delete Request
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-muted text-center py-3">No pending procurement requests.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card kpi-card">
    <div class="card-header bg-white border-0"><h6 class="mb-0">Recently Added Items</h6></div>
    <div class="card-body table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Item</th><th>Category</th><th>Unit</th><th>Added</th></tr></thead>
            <tbody>
            @forelse($recentItems as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td><span class="badge text-bg-light">{{ $item->category->name ?? '-' }}</span></td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ $item->created_at->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No items added yet. Go to <a href="{{ route('owner.inventory.categories.index', ['site_id' => $siteId]) }}">Manage Categories</a> to add some.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
