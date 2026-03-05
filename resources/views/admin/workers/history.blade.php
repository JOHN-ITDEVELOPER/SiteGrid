@extends('admin.layouts.app')

@section('page-title', 'Worker Assignment History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Worker Assignment History</h1>
        <p class="text-muted mb-0">{{ $user->name }} - Complete assignment record</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-primary">
            <i class="bi bi-person"></i> View User Profile
        </a>
        <a href="{{ route('admin.workers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Workers
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle fs-1 text-primary"></i>
                </div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ $user->role }}</p>
                <p class="mb-1"><small><i class="bi bi-envelope"></i> {{ $user->email ?? '-' }}</small></p>
                <p class="mb-0"><small><i class="bi bi-phone"></i> {{ $user->phone ?? '-' }}</small></p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Summary</h6>
                <p class="mb-2">
                    <strong>Total Assignments:</strong> 
                    <span class="badge bg-primary">{{ $user->siteWorkers->count() }}</span>
                </p>
                <p class="mb-2">
                    <strong>Currently Active:</strong> 
                    <span class="badge bg-success">{{ $user->siteWorkers->whereNull('ended_at')->count() }}</span>
                </p>
                <p class="mb-0">
                    <strong>Ended:</strong> 
                    <span class="badge bg-secondary">{{ $user->siteWorkers->whereNotNull('ended_at')->count() }}</span>
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        @php
            $activeAssignments = $user->siteWorkers->whereNull('ended_at');
            $endedAssignments = $user->siteWorkers->whereNotNull('ended_at');
        @endphp

        @if($activeAssignments->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Active Assignments ({{ $activeAssignments->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Site</th>
                                    <th>Role</th>
                                    <th>Rates (KES/day)</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeAssignments as $assignment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.sites.show', $assignment->site_id) }}" class="text-decoration-none fw-semibold">
                                                {{ $assignment->site->name ?? 'Unknown' }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $assignment->site->location ?? '-' }}</small>
                                        </td>
                                        <td>
                                            @if($assignment->is_foreman)
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-star-fill"></i> Foreman
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Worker</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ number_format($assignment->daily_rate) }}</strong><br>
                                            <small class="text-muted">Weekly: {{ number_format($assignment->weekly_rate) }}</small>
                                        </td>
                                        <td>{{ $assignment->started_at ? $assignment->started_at->format('M d, Y') : '-' }}</td>
                                        <td>
                                            @if($assignment->started_at)
                                                {{ \Carbon\Carbon::parse($assignment->started_at)->diffForHumans(null, true) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.workers.edit', $assignment) }}" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="{{ route('admin.workers.deactivate', $assignment) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger" title="Deactivate" onclick="return confirm('Deactivate this assignment?')">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if($endedAssignments->isNotEmpty())
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-clock-history me-2"></i>
                        Past Assignments ({{ $endedAssignments->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Site</th>
                                    <th>Role</th>
                                    <th>Rates (KES/day)</th>
                                    <th>Period</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($endedAssignments as $assignment)
                                    <tr class="opacity-75">
                                        <td>
                                            <a href="{{ route('admin.sites.show', $assignment->site_id) }}" class="text-decoration-none">
                                                {{ $assignment->site->name ?? 'Unknown' }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $assignment->site->location ?? '-' }}</small>
                                        </td>
                                        <td>
                                            @if($assignment->is_foreman)
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-star"></i> Foreman
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Worker</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ number_format($assignment->daily_rate) }}</strong><br>
                                            <small class="text-muted">Weekly: {{ number_format($assignment->weekly_rate) }}</small>
                                        </td>
                                        <td>
                                            <small>
                                                {{ $assignment->started_at ? $assignment->started_at->format('M d, Y') : 'Unknown' }}<br>
                                                to {{ $assignment->ended_at ? $assignment->ended_at->format('M d, Y') : '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($assignment->started_at && $assignment->ended_at)
                                                {{ \Carbon\Carbon::parse($assignment->started_at)->diffForHumans($assignment->ended_at, true) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.workers.reactivate', $assignment) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm" title="Reactivate" onclick="return confirm('Reactivate this assignment?')">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Reactivate
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if($user->siteWorkers->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">No Assignment History</h5>
                    <p class="text-muted mb-3">This worker has not been assigned to any sites yet.</p>
                    <a href="{{ route('admin.workers.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create Assignment
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
