@extends('admin.layouts.app')

@section('page-title', 'Add Worker')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Add Worker to Site</h1>
        <p class="text-muted mb-0">Assign a worker to a site</p>
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
                <form method="POST" action="{{ route('admin.workers.store') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Worker <span class="text-danger">*</span></label>
                        <select name="user_id" id="user_id" class="form-select" required>
                            <option value="">Select a worker...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                    {{ $user->name }} ({{ $user->phone ?? $user->email }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select from existing workers/foremen</small>
                    </div>

                    <div class="mb-3">
                        <label for="site_id" class="form-label">Site <span class="text-danger">*</span></label>
                        <select name="site_id" id="site_id" class="form-select" required>
                            <option value="">Select a site...</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>
                                    {{ $site->name }} ({{ $site->owner->name ?? 'Unknown Owner' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_foreman" id="is_foreman" class="form-check-input" value="1" @checked(old('is_foreman'))>
                            <label class="form-check-label" for="is_foreman">
                                <i class="bi bi-star text-warning"></i> Assign as Foreman
                            </label>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="daily_rate" class="form-label">Daily Rate (KES) <span class="text-danger">*</span></label>
                            <input type="number" name="daily_rate" id="daily_rate" class="form-control" 
                                   value="{{ old('daily_rate') }}" required min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label for="weekly_rate" class="form-label">Weekly Rate (KES) <span class="text-danger">*</span></label>
                            <input type="number" name="weekly_rate" id="weekly_rate" class="form-control" 
                                   value="{{ old('weekly_rate') }}" required min="0" step="0.01">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="started_at" class="form-label">Start Date</label>
                        <input type="date" name="started_at" id="started_at" class="form-control" 
                               value="{{ old('started_at', now()->format('Y-m-d')) }}">
                        <small class="text-muted">Leave blank to use today's date</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Add Worker
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
                <h5 class="card-title"><i class="bi bi-info-circle"></i> Worker Assignment</h5>
                <p class="mb-2">This form assigns an existing user to a site as a worker.</p>
                <ul class="small mb-0">
                    <li>Select a user with Worker or Foreman role</li>
                    <li>Choose the site for assignment</li>
                    <li>Set compensation rates</li>
                    <li>Optionally mark as foreman</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
