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
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Sites with Inventory</div><div class="h4 mb-0">{{ $metrics['sites_enabled'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Categories</div><div class="h4 mb-0">{{ $metrics['categories_count'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Active Items</div><div class="h4 mb-0">{{ $metrics['active_items_count'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Pending Requests</div><div class="h4 mb-0">{{ $metrics['pending_requests'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Low Stock Alerts</div><div class="h4 mb-0 text-danger">{{ $metrics['low_stock_alerts'] }}</div></div></div>
    <div class="col-md-2"><div class="card p-3"><div class="small text-muted">Movements Today</div><div class="h4 mb-0">{{ $metrics['movements_today'] }}</div></div></div>
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
            <div class="card-body">
                @forelse($requests as $req)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">{{ $req->reference }}</div>
                                <div class="small text-muted">
                                    <i class="bi bi-building"></i> {{ $req->site->name ?? '-' }}
                                    · <i class="bi bi-person"></i> {{ $req->requester->name ?? '-' }}
                                </div>
                            </div>
                            <span class="badge text-bg-light border">{{ strtoupper($req->status) }}</span>
                        </div>

                        <div class="small mb-2">
                            <strong>Purpose:</strong> {{ $req->purpose ?? 'N/A' }}<br/>
                            <strong>Items:</strong> {{ $req->items->count() }} line(s)<br/>
                            <strong>Created:</strong> {{ $req->created_at->format('M d, Y H:i') }}
                        </div>

                        @if($req->status === 'requested')
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-sm btn-success w-100" data-bs-toggle="modal" data-bs-target="#approveModal{{ $req->id }}">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-sm btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if(in_array($req->status, ['requested', 'rejected']))
                            <form method="POST" action="{{ route('admin.inventory.requests.destroy', $req) }}" class="d-inline" onsubmit="return confirm('Delete this procurement request?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100 mt-2">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Approve Modal -->
                    <div class="modal fade" id="approveModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Approve: {{ $req->reference }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.inventory.requests.approve', $req) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">PO Number *</label>
                                            <input type="text" name="po_number" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Approval Notes</label>
                                            <textarea name="approval_notes" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Approve Request</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject: {{ $req->reference }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.inventory.requests.reject', $req) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Rejection Reason *</label>
                                            <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Why is this request being rejected?" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Reject Request</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No procurement requests yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Site Progress Logs</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Site</th><th>Title</th><th>Sector</th><th>Status</th><th>By</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse($progressLogs as $log)
                        <tr>
                            <td>{{ $log->log_date }}</td>
                            <td>{{ $log->site->name ?? '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($log->title, 40) }}</td>
                            <td>{{ $log->sector ?? '-' }}</td>
                            <td><span class="badge text-bg-{{ $log->status === 'submitted' ? 'warning' : 'success' }}">{{ strtoupper($log->status) }}</span></td>
                            <td>{{ $log->creator->name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.inventory.progress.show', $log) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No progress logs yet.</td></tr>
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
                <table class="table table-sm align-middle">
                    <thead><tr><th>When</th><th>Site</th><th>Type</th><th>Item</th><th>Qty</th><th>Balance</th><th>By</th><th>Photos</th></tr></thead>
                    <tbody>
                    @forelse($movements as $move)
                        <tr>
                            <td>{{ $move->created_at->format('M d, H:i') }}</td>
                            <td>{{ $move->site->name ?? '-' }}</td>
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
                                <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                            </td>
                            <td>{{ $move->item->name ?? '-' }}</td>
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
                                                Evidence Photos · {{ $move->item->name ?? 'N/A' }}
                                                <span class="badge text-bg-light">{{ $move->movement_type }}</span>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <div class="small text-muted">
                                                    <strong>Site:</strong> {{ $move->site->name ?? 'N/A' }}<br/>
                                                    <strong>Date:</strong> {{ $move->created_at->format('M d, Y H:i') }}<br/>
                                                    <strong>Performed By:</strong> {{ $move->performedBy->name ?? 'N/A' }}<br/>
                                                    @if($move->notes)
                                                        <strong>Notes:</strong> {{ $move->notes }}<br/>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                @foreach($move->evidences as $evidence)
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <img src="{{ asset('storage/' . $evidence->file_path) }}" class="card-img-top" style="max-height: 250px; object-fit: cover;">
                                                            <div class="card-body">
                                                                @if($evidence->caption)
                                                                    <p class="card-text small"><strong>{{ $evidence->caption }}</strong></p>
                                                                @endif
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
                        <tr><td colspan="8" class="text-muted text-center py-3">No movement logs yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
