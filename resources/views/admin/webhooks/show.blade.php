@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('admin.webhooks.index') }}" class="btn btn-link text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Webhooks
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <!-- Request Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Request Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Integration:</strong><br>
                        <span class="badge bg-secondary">{{ ucfirst($log->integration) }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Event Type:</strong><br>
                        {{ $log->event_type }}
                    </div>
                    <div class="mb-3">
                        <strong>Method:</strong><br>
                        <span class="badge bg-primary">{{ $log->method }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>URL:</strong><br>
                        <code>{{ $log->url }}</code>
                    </div>
                    <div class="mb-3">
                        <strong>Reference:</strong><br>
                        {{ $log->reference ?? 'N/A' }}
                    </div>
                    <div class="mb-3">
                        <strong>Timestamp:</strong><br>
                        {{ $log->created_at->format('M d, Y H:i:s') }}
                    </div>
                </div>
            </div>

            <!-- Request Body -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Request Body</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><code>{{ $log->request_body ?? 'No request body' }}</code></pre>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- Response Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Response Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Status:</strong><br>
                        @if($log->status === 'success')
                            <span class="badge bg-success">Success</span>
                        @elseif($log->status === 'failed')
                            <span class="badge bg-danger">Failed</span>
                        @else
                            <span class="badge bg-warning">Pending</span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <strong>Response Status:</strong><br>
                        @if($log->response_status)
                            <span class="badge bg-{{ $log->response_status >= 200 && $log->response_status < 300 ? 'success' : 'danger' }}">
                                {{ $log->response_status }}
                            </span>
                        @else
                            <span class="text-muted">No response</span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <strong>Retry Count:</strong><br>
                        {{ $log->retry_count }}
                    </div>
                    @if($log->last_retry_at)
                        <div class="mb-3">
                            <strong>Last Retry:</strong><br>
                            {{ $log->last_retry_at->format('M d, Y H:i:s') }}
                        </div>
                    @endif
                    @if($log->error_message)
                        <div class="mb-3">
                            <strong>Error Message:</strong><br>
                            <div class="alert alert-danger mb-0">{{ $log->error_message }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Response Body -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Response Body</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><code>{{ $log->response_body ?? 'No response body' }}</code></pre>
                </div>
            </div>

            <!-- Actions -->
            @if($log->status === 'failed')
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.webhooks.retry', $log) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-arrow-clockwise"></i> Retry Webhook
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
