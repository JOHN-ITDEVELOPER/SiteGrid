@extends('admin.layouts.app')

@section('page-title', 'User Activity Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Activity Log</h1>
        <p class="text-muted mb-0">{{ $user->name }} - Recent actions and events</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-primary">
            <i class="bi bi-person"></i> View User Profile
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle fs-1 text-primary"></i>
                </div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
                
                @if($user->is_suspended)
                    <span class="badge bg-danger mb-2">
                        <i class="bi bi-ban"></i> Suspended
                    </span>
                @else
                    <span class="badge bg-success mb-2">
                        <i class="bi bi-check-circle"></i> Active
                    </span>
                @endif

                <hr>
                <p class="mb-1"><small><i class="bi bi-envelope"></i> {{ $user->email ?? '-' }}</small></p>
                <p class="mb-1"><small><i class="bi bi-phone"></i> {{ $user->phone ?? '-' }}</small></p>
                <p class="mb-0"><small><i class="bi bi-calendar"></i> Joined {{ $user->created_at->format('M d, Y') }}</small></p>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="bi bi-clock-history me-2"></i>
                    Activity History ({{ $logs->total() }} total)
                </h5>
            </div>
            <div class="card-body p-0">
                @if($logs->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($logs as $log)
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            @php
                                                $actionIcon = match(true) {
                                                    str_contains($log->action, 'login') => 'box-arrow-in-right',
                                                    str_contains($log->action, 'logout') => 'box-arrow-right',
                                                    str_contains($log->action, 'create') => 'plus-circle',
                                                    str_contains($log->action, 'update') => 'pencil-square',
                                                    str_contains($log->action, 'delete') => 'trash',
                                                    str_contains($log->action, 'suspend') => 'ban',
                                                    str_contains($log->action, 'approve') => 'check-circle',
                                                    str_contains($log->action, 'reject') => 'x-circle',
                                                    default => 'circle'
                                                };
                                                $actionColor = match(true) {
                                                    str_contains($log->action, 'delete') || str_contains($log->action, 'suspend') => 'danger',
                                                    str_contains($log->action, 'create') || str_contains($log->action, 'approve') => 'success',
                                                    str_contains($log->action, 'update') => 'primary',
                                                    str_contains($log->action, 'reject') => 'warning',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <i class="bi bi-{{ $actionIcon }} text-{{ $actionColor }}"></i>
                                            <strong>{{ $log->action }}</strong>
                                        </div>
                                        
                                        @if($log->entity_type && $log->entity_id)
                                            <p class="mb-1 text-muted small">
                                                <strong>{{ $log->entity_type }}</strong> #{{ $log->entity_id }}
                                            </p>
                                        @endif
                                        
                                        @if($log->meta && count($log->meta) > 0)
                                            <div class="badge bg-light text-dark border mb-1">
                                                @foreach($log->meta as $key => $value)
                                                    @if(!is_array($value) && !is_object($value))
                                                        <span class="me-2"><strong>{{ $key }}:</strong> {{ $value }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        
                                        <small class="text-muted d-block">
                                            <i class="bi bi-geo-alt"></i> {{ $log->ip_address ?? 'Unknown IP' }}
                                            @if($log->user_agent)
                                                | {{ \Illuminate\Support\Str::limit($log->user_agent, 50) }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="text-end" style="min-width: 150px;">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> {{ $log->created_at->format('M d, Y') }}<br>
                                            {{ $log->created_at->format('h:i A') }}<br>
                                            <span class="badge bg-secondary">{{ $log->created_at->diffForHumans() }}</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No Activity Yet</h5>
                        <p class="text-muted">No recorded activity for this user.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
