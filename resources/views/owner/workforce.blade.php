@extends('owner.layouts.app')

@section('title', 'Workforce')
@section('page-title', 'Workforce')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <a href="{{ route('owner.workers.add') }}" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Add Worker
    </a>
</div>

<div class="card kpi-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('owner.workforce') }}">
            <div class="col-md-3">
                <select class="form-select" name="site_id">
                    <option value="">All Sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ (string)$siteId === (string)$site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Workers</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Workers</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Deactivated Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="filter">
                    <option value="">All Workers</option>
                    <option value="anomaly" {{ $filter === 'anomaly' ? 'selected' : '' }}>Attendance anomaly</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="card kpi-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th>Site</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>KYC</th>
                    <th>30d Attendance</th>
                    <th>30d Hours</th>
                    <th>Compliance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $assignment)
                    @php
                        $stats = $attendanceByWorker[$assignment->user_id] ?? null;
                        $totalDays = $stats->total_days ?? 0;
                        $presentDays = $stats->present_days ?? 0;
                        $hours = $stats->total_hours ?? 0;
                        $compliance = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;
                        $isAnomaly = $filter === 'anomaly' ? ($compliance < 70) : false;
                        $isDeactivated = !is_null($assignment->ended_at);
                    @endphp
                    @if($filter !== 'anomaly' || $isAnomaly)
                    <tr class="{{ $isDeactivated ? 'table-secondary opacity-75' : '' }}">
                        <td>
                            <div>{{ $assignment->user->name ?? 'Unknown' }}</div>
                            @if($isDeactivated)
                                <small class="text-muted">Ended: {{ $assignment->ended_at->format('M d, Y') }}</small>
                            @endif
                        </td>
                        <td>{{ $assignment->site->name ?? '—' }}</td>
                        <td>{{ $assignment->is_foreman ? 'Foreman' : 'Worker' }}</td>
                        <td>
                            @if($isDeactivated)
                                <span class="badge text-bg-secondary">
                                    <i class="bi bi-x-circle"></i> Deactivated
                                </span>
                            @else
                                <span class="badge text-bg-success">
                                    <i class="bi bi-check-circle"></i> Active
                                </span>
                            @endif
                        </td>
                        <td><span class="badge text-bg-light border">{{ strtoupper($assignment->user->kyc_status ?? 'pending') }}</span></td>
                        <td>{{ $presentDays }} / {{ $totalDays }}</td>
                        <td>{{ number_format($hours, 1) }}</td>
                        <td>
                            @if($totalDays > 0)
                                <span class="badge {{ $compliance >= 85 ? 'text-bg-success' : ($compliance >= 70 ? 'text-bg-warning' : 'text-bg-danger') }}">
                                    {{ $compliance }}%
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($isDeactivated)
                                <form method="POST" action="{{ route('owner.workers.reactivate', $assignment) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Reactivate Worker">
                                        <i class="bi bi-arrow-clockwise"></i> Reactivate
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('owner.workers.edit', $assignment) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endif
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">No workers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $workers->links() }}</div>
@endsection
