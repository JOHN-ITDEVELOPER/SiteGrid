@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Webhook Logs</h1>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.webhooks.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search reference or event..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="integration" class="form-select">
                        <option value="">All Integrations</option>
                        @foreach($integrations as $integration)
                            <option value="{{ $integration }}" {{ request('integration') === $integration ? 'selected' : '' }}>
                                {{ ucfirst($integration) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Integration</th>
                            <th>Event</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Response</th>
                            <th>Retries</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    <small>{{ $log->created_at->format('M d, H:i:s') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ ucfirst($log->integration) }}</span>
                                </td>
                                <td>{{ $log->event_type }}</td>
                                <td>
                                    <small>{{ $log->reference ?? '-' }}</small>
                                </td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="badge bg-success">Success</span>
                                    @elseif($log->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->response_status)
                                        <span class="badge bg-{{ $log->response_status >= 200 && $log->response_status < 300 ? 'success' : 'danger' }}">
                                            {{ $log->response_status }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $log->retry_count }}</td>
                                <td>
                                    <a href="{{ route('admin.webhooks.show', $log) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($log->status === 'failed')
                                        <form method="POST" action="{{ route('admin.webhooks.retry', $log) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No webhook logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
