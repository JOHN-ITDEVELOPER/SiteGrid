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
        Assign Worker
    </div>
    <form method="POST" action="{{ route('field.add-worker.store') }}" class="row g-3">
        @csrf
        <div class="col-md-6">
            <label class="form-label">Select Site</label>
            <select class="form-select @error('site_id') is-invalid @enderror" name="site_id" required>
                <option value="">Choose a site...</option>
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
            <label class="form-label">Select Worker</label>
            <select class="form-select @error('worker_id') is-invalid @enderror" name="worker_id" required>
                <option value="">Choose a worker...</option>
                @php
                    $availableWorkers = \App\Models\User::where('role', 'worker')
                        ->where('id', '!=' , auth()->id())
                        ->orderBy('name')
                        ->get();
                @endphp
                @foreach($availableWorkers as $worker)
                    <option value="{{ $worker->id }}" {{ old('worker_id') == $worker->id ? 'selected' : '' }}>
                        {{ $worker->name }} ({{ $worker->phone }})
                    </option>
                @endforeach
            </select>
            @error('worker_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
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
