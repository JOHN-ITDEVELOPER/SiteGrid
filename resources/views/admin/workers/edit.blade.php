@extends('admin.layouts.app')

@section('page-title', 'Edit Worker')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Edit Worker Assignment</h1>
        <p class="text-muted mb-0">{{ $worker->user->name ?? 'Unknown' }} @ {{ $worker->site->name ?? 'Unknown' }}</p>
    </div>
    <a href="{{ route('admin.workers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
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

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.workers.update', $worker) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Worker</label>
                        <input type="text" class="form-control" value="{{ $worker->user->name ?? 'Unknown' }}" disabled>
                        <small class="text-muted">Worker cannot be changed. Create a new assignment instead.</small>
                    </div>

                    <div class="mb-3">
                        <label for="site_id" class="form-label">Site <span class="text-danger">*</span></label>
                        <select name="site_id" id="site_id" class="form-select" required>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" @selected($worker->site_id == $site->id)>
                                    {{ $site->name }} ({{ $site->owner->name ?? 'Unknown Owner' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_foreman" id="is_foreman" class="form-check-input" value="1" @checked($worker->is_foreman)>
                            <label class="form-check-label" for="is_foreman">
                                <i class="bi bi-star text-warning"></i> Assign as Foreman
                            </label>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="daily_rate" class="form-label">Daily Rate (KES) <span class="text-danger">*</span></label>
                            <input type="number" name="daily_rate" id="daily_rate" class="form-control" 
                                   value="{{ old('daily_rate', $worker->daily_rate) }}" required min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label for="weekly_rate" class="form-label">Weekly Rate (KES) <span class="text-danger">*</span></label>
                            <input type="number" name="weekly_rate" id="weekly_rate" class="form-control" 
                                   value="{{ old('weekly_rate', $worker->weekly_rate) }}" required min="0" step="0.01">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="started_at" class="form-label">Start Date</label>
                        <input type="date" name="started_at" id="started_at" class="form-control" 
                               value="{{ old('started_at', $worker->started_at?->format('Y-m-d')) }}">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Update Worker
                        </button>
                        <a href="{{ route('admin.workers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Assignment Status</h5>
                <p class="mb-2">
                    <strong>Status:</strong> 
                    @if($worker->ended_at)
                        <span class="badge bg-danger">Inactive</span>
                    @else
                        <span class="badge bg-success">Active</span>
                    @endif
                </p>
                @if($worker->ended_at)
                    <p class="mb-2"><strong>Ended:</strong> {{ $worker->ended_at->format('M d, Y') }}</p>
                    <form method="POST" action="{{ route('admin.workers.reactivate', $worker) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Reactivate this worker?')">
                            <i class="bi bi-arrow-counterclockwise"></i> Reactivate Worker
                        </button>
                    </form>
                @else
                    <p class="mb-2"><strong>Started:</strong> {{ $worker->started_at?->format('M d, Y') ?? 'Unknown' }}</p>
                    <form method="POST" action="{{ route('admin.workers.deactivate', $worker) }}">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('Deactivate this worker?')">
                            <i class="bi bi-x-circle"></i> Deactivate Worker
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-clock-history"></i> View History</h5>
                <p class="small mb-2">See all assignments for this worker across all sites.</p>
                <a href="{{ route('admin.workers.history', $worker->user_id) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-list-ul"></i> View Full History
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
