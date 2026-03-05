@extends('owner.layouts.app')

@section('title', 'Edit Worker')
@section('page-title', 'Edit Worker Details')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card kpi-card">
            <div class="card-body">
                <div class="mb-3">
                    <strong>Worker:</strong> {{ $worker->user->name ?? 'Unknown' }}<br>
                    <strong>Phone:</strong> {{ $worker->user->phone ?? '—' }}<br>
                    <strong>Site:</strong> {{ $worker->site->name ?? '—' }}
                </div>

                <form method="POST" action="{{ route('owner.workers.update', $worker) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Select Site *</label>
                            <select class="form-select @error('site_id') is-invalid @enderror" name="site_id" required>
                                <option value="">-- Choose Site --</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id', $worker->site_id) == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Worker Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $worker->user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $worker->user->phone) }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control @error('role') is-invalid @enderror" name="role" value="{{ old('role', $worker->role) }}">
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_foreman" value="1" id="isForeman" {{ old('is_foreman', $worker->is_foreman) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isForeman">
                                    Foreman/Supervisor
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Daily Rate (KES) *</label>
                            <input type="number" step="0.01" class="form-control @error('daily_rate') is-invalid @enderror" name="daily_rate" value="{{ old('daily_rate', $worker->daily_rate) }}" required>
                            @error('daily_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Weekly Rate (KES) *</label>
                            <input type="number" step="0.01" class="form-control @error('weekly_rate') is-invalid @enderror" name="weekly_rate" value="{{ old('weekly_rate', $worker->weekly_rate) }}" required>
                            @error('weekly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Worker</button>
                        <a href="{{ route('owner.workforce') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

                <hr class="my-4">

                <form method="POST" action="{{ route('owner.workers.deactivate', $worker) }}" onsubmit="return confirm('Are you sure you want to deactivate this worker?');">
                    @csrf
                    <h6 class="text-danger">Deactivate Worker</h6>
                    <p class="small text-muted">This will end the worker's assignment to this site.</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for deactivation *</label>
                        <textarea class="form-control" name="reason" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">Deactivate Worker</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
