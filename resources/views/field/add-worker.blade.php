@extends('field.layouts.app')

@section('title', 'Add Worker to Site')
@section('page-title', 'Add Worker')

@section('content')
<div class="page-title">
    <i class="bi bi-person-plus"></i>
    Add Worker to Site
</div>
<p class="page-subtitle">Assign a new worker to one of your managed sites</p>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-person-plus"></i>
        Add New Worker
    </div>
    <form method="POST" action="{{ route('field.add-worker.store') }}" class="row g-3">
        @csrf
        
        <div class="col-md-6">
            <label class="form-label">Select Site *</label>
            <select class="form-select @error('site_id') is-invalid @enderror" name="site_id" required>
                <option value="">-- Choose Site --</option>
                @foreach($foremanSiteIds as $siteId)
                    @php $site = \App\Models\Site::find($siteId); @endphp
                    <option value="{{ $siteId }}" {{ old('site_id') == $siteId ? 'selected' : '' }}>
                        {{ $site?->name }}
                    </option>
                @endforeach
            </select>
            @error('site_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Worker Name *</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Phone Number *</label>
            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="+254712345678" required>
            @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
            <small class="text-muted">If worker exists, they'll be assigned to this site</small>
        </div>

        <div class="col-md-6">
            <label class="form-label">Email (optional)</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Role</label>
            <input type="text" class="form-control @error('role') is-invalid @enderror" name="role" value="{{ old('role', 'General Worker') }}">
            @error('role')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">Daily Rate (KES) *</label>
            <input type="number" step="0.01" class="form-control @error('daily_rate') is-invalid @enderror" name="daily_rate" value="{{ old('daily_rate') }}" required>
            @error('daily_rate')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">Weekly Rate (KES) *</label>
            <input type="number" step="0.01" class="form-control @error('weekly_rate') is-invalid @enderror" name="weekly_rate" value="{{ old('weekly_rate') }}" required>
            @error('weekly_rate')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="col-md-2">
            <label class="form-label">Start Date *</label>
            <input type="date" class="form-control @error('started_at') is-invalid @enderror" name="started_at" value="{{ old('started_at', date('Y-m-d')) }}" required>
            @error('started_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

       <!-- <div class="col-md-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_foreman" value="1" id="isForeman" {{ old('is_foreman') ? 'checked' : '' }}>
                <label class="form-check-label" for="isForeman">
                    This worker is a Foreman/Supervisor
                </label>
            </div>
        </div> -->

        <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-lg"></i> Add Worker to Site
            </button>
        </div>
    </form>
</div>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-info-circle"></i>
        How It Works
    </div>
    <div class="alert alert-info mb-0">
        <ul class="mb-0">
            <li>Select the site where you want to add the worker</li>
            <li>Choose the worker from the list of available workers</li>
            <li>Click "Add Worker" to assign them to your site</li>
            <li>The worker will immediately be able to clock in and submit claims for this site</li>
        </ul>
    </div>
</div>
@endsection
