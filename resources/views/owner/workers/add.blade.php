@extends('owner.layouts.app')

@section('title', 'Add Worker')
@section('page-title', 'Add New Worker')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card kpi-card">
            <div class="card-body">
                <form method="POST" action="{{ route('owner.workers.store') }}">
                    @csrf
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Select Site *</label>
                            <select class="form-select @error('site_id') is-invalid @enderror" name="site_id" required>
                                <option value="">-- Choose Site --</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Worker Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="+254712345678" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">If worker exists, they'll be assigned to this site</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control @error('role') is-invalid @enderror" name="role" value="{{ old('role', 'General Worker') }}">
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Daily Rate (KES) *</label>
                            <input type="number" step="0.01" class="form-control @error('daily_rate') is-invalid @enderror" name="daily_rate" value="{{ old('daily_rate') }}" required>
                            @error('daily_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Weekly Rate (KES) *</label>
                            <input type="number" step="0.01" class="form-control @error('weekly_rate') is-invalid @enderror" name="weekly_rate" value="{{ old('weekly_rate') }}" required>
                            @error('weekly_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Start Date *</label>
                            <input type="date" class="form-control @error('started_at') is-invalid @enderror" name="started_at" value="{{ old('started_at', date('Y-m-d')) }}" required>
                            @error('started_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_foreman" value="1" id="isForeman" {{ old('is_foreman') ? 'checked' : '' }}>
                                <label class="form-check-label" for="isForeman">
                                    This worker is a Foreman/Supervisor
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Add Worker</button>
                        <a href="{{ route('owner.workforce') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
