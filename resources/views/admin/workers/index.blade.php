@extends('admin.layouts.app')

@section('page-title', 'Workers Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Workers Management</h1>
        <p class="text-muted mb-0">Manage worker assignments across all sites</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.workers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Worker
        </a>
    </div>
</div>

<form method="GET" action="{{ route('admin.workers.index') }}" class="card card-body mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Worker or site name">
        </div>
        <div class="col-md-3">
            <label class="form-label">Site</label>
            <select name="site_id" class="form-select">
                <option value="">All sites</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}" @selected($siteId == $site->id)>{{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                <option value="all" @selected($status === 'all')>All</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Foreman</label>
            <select name="is_foreman" class="form-select">
                <option value="">All</option>
                <option value="yes" @selected($isForeman === 'yes')>Yes</option>
                <option value="no" @selected($isForeman === 'no')>No</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
    </div>
</form>

<!-- Bulk Actions Bar -->
<div class="card card-body mb-3" id="bulkActionsBar" style="display: none;">
    <form method="POST" action="{{ route('admin.workers.bulk') }}" id="bulkActionsForm" onsubmit="return handleBulkSubmit()">
        @csrf
        <div class="d-flex align-items-center gap-3">
            <span class="fw-semibold">
                <span id="selectedCount">0</span> selected
            </span>
            <select name="action" class="form-select" style="width: auto;" required id="bulkAction">
                <option value="">Choose action...</option>
                <option value="deactivate">Deactivate</option>
                <option value="reactivate">Reactivate</option>
                <option value="update_rates">Update Rates</option>
            </select>
            
            <!-- Rate fields (hidden by default) -->
            <div id="rateFields" style="display: none;" class="d-flex gap-2">
                <input type="number" name="daily_rate" class="form-control" placeholder="Daily Rate" style="width: 120px;" min="0" step="0.01">
                <input type="number" name="weekly_rate" class="form-control" placeholder="Weekly Rate" style="width: 120px;" min="0" step="0.01">
            </div>
            
            <button type="submit" class="btn btn-primary">Apply</button>
            <button type="button" class="btn btn-secondary" onclick="clearSelection()">Clear</button>
        </div>
    </form>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    </th>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Role</th>
                    <th>Rates (KES)</th>
                    <th>Start Date</th>
                    <th>Status</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $worker)
                    <tr>
                        <td>
                            <input type="checkbox" name="worker_ids[]" value="{{ $worker->id }}" class="worker-checkbox" onchange="updateSelection()">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $worker->user->name ?? 'Unknown' }}</div>
                                    <small class="text-muted">{{ $worker->user->phone ?? $worker->user->email ?? '-' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.sites.show', $worker->site_id) }}" class="text-decoration-none">
                                {{ $worker->site->name ?? 'Unknown' }}
                            </a>
                        </td>
                        <td>
                            @if($worker->is_foreman)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill"></i> Foreman
                                </span>
                            @else
                                <span class="badge bg-secondary">Worker</span>
                            @endif
                        </td>
                        <td>
                            <small>
                                <strong>Daily:</strong> {{ number_format($worker->daily_rate) }}<br>
                                <strong>Weekly:</strong> {{ number_format($worker->weekly_rate) }}
                            </small>
                        </td>
                        <td>{{ $worker->started_at ? $worker->started_at->format('M d, Y') : '-' }}</td>
                        <td>
                            @if($worker->ended_at)
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Inactive
                                </span>
                                <small class="d-block text-muted">{{ $worker->ended_at->format('M d, Y') }}</small>
                            @else
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Active
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.workers.edit', $worker) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($worker->ended_at)
                                    <form method="POST" action="{{ route('admin.workers.reactivate', $worker) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success" title="Reactivate" onclick="return confirm('Reactivate this worker?')">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.workers.deactivate', $worker) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger" title="Deactivate" onclick="return confirm('Deactivate this worker?')">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No workers found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $workers->links() }}
</div>

<script>
let selectedWorkerIds = [];

function updateSelection() {
    selectedWorkerIds = Array.from(document.querySelectorAll('.worker-checkbox:checked')).map(cb => cb.value);
    document.getElementById('selectedCount').textContent = selectedWorkerIds.length;
    document.getElementById('bulkActionsBar').style.display = selectedWorkerIds.length > 0 ? 'block' : 'none';
}

function toggleSelectAll(checkbox) {
    document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateSelection();
}

function clearSelection() {
    document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateSelection();
}

// Show/hide rate fields based on action
document.getElementById('bulkAction')?.addEventListener('change', function() {
    const rateFields = document.getElementById('rateFields');
    if (this.value === 'update_rates') {
        rateFields.style.display = 'flex';
        rateFields.querySelectorAll('input').forEach(input => input.required = true);
    } else {
        rateFields.style.display = 'none';
        rateFields.querySelectorAll('input').forEach(input => input.required = false);
    }
});

function handleBulkSubmit() {
    // Add selected IDs to form
    const form = document.getElementById('bulkActionsForm');
    selectedWorkerIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'worker_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    return true;
}
</script>
@endsection
