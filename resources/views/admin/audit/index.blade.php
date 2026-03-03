@extends('admin.layouts.app')

@section('page-title', 'Audit Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Audit Logs</h1>
        <p class="text-muted mb-0">Track admin actions across the platform</p>
    </div>
</div>

<form method="GET" action="{{ route('admin.audit.index') }}" class="card card-body mb-4">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Action or entity">
        </div>
        <div class="col-md-4">
            <label class="form-label">Action</label>
            <select name="action" class="form-select">
                <option value="">All actions</option>
                @foreach ($actions as $actionOption)
                    <option value="{{ $actionOption }}" @selected($action === $actionOption)>{{ $actionOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                        <td>{{ $log->user->name ?? 'System' }}</td>
                        <td class="fw-semibold">{{ $log->action }}</td>
                        <td>{{ $log->entity_type ?? '-' }} {{ $log->entity_id ? "#{$log->entity_id}" : '' }}</td>
                        <td class="text-muted small">
                            @if (!empty($log->meta))
                                <span>{{ json_encode($log->meta) }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No audit logs yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>
@endsection
