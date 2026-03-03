@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Activity Feed</h1>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.activity.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search activities..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="severity" class="form-select">
                        <option value="">All Severity</option>
                        <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.activity.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="card">
        <div class="card-body">
            <div class="activity-timeline">
                @forelse($activities as $activity)
                    <div class="activity-item mb-3 pb-3 border-bottom">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                @if($activity->severity === 'critical')
                                    <div class="activity-icon bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                @elseif($activity->severity === 'warning')
                                    <div class="activity-icon bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-exclamation-circle"></i>
                                    </div>
                                @else
                                    <div class="activity-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-info-circle"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $activity->message }}</strong>
                                        <div class="text-muted small mt-1">
                                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $activity->type)) }}</span>
                                            @if($activity->user)
                                                by {{ $activity->user->name }}
                                            @endif
                                            @if($activity->entity_type)
                                                | {{ $activity->entity_type }} #{{ $activity->entity_id }}
                                            @endif
                                        </div>
                                        @if($activity->meta && count($activity->meta) > 0)
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    @foreach($activity->meta as $key => $value)
                                                        <span class="badge bg-light text-dark me-1">
                                                            {{ ucfirst($key) }}: {{ is_array($value) ? json_encode($value) : $value }}
                                                        </span>
                                                    @endforeach
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            {{ $activity->created_at->format('M d, H:i:s') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No activities found.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-3">
                {{ $activities->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
