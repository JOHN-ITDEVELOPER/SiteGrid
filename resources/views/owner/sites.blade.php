@extends('owner.layouts.app')

@section('title', 'Sites')
@section('page-title', 'Sites')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Manage construction sites</h6>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSiteModal">
        <i class="bi bi-plus-lg"></i> Add New Site
    </button>
</div>

<div class="card kpi-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('owner.sites') }}">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Search by name or location" value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse($sites as $site)
        <div class="col-md-6 col-xl-4">
            <div class="card kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0">{{ $site->name }}</h6>
                        <span class="badge {{ $site->is_completed ? 'text-bg-secondary' : 'text-bg-success' }}">{{ $site->is_completed ? 'Completed' : 'Active' }}</span>
                    </div>
                    <div class="text-muted small mb-2">{{ $site->location }}</div>
                    <div class="small mb-2">Active workers: <strong>{{ $site->workers_count }}</strong></div>
                    <div class="small mb-3">Payout method: <strong>{{ strtoupper($site->payout_method ?? 'n/a') }}</strong></div>
                    <a href="{{ route('owner.sites.detail', $site) }}" class="btn btn-sm btn-primary">View Details</a>
                    <a href="{{ route('owner.workforce', ['site_id' => $site->id]) }}" class="btn btn-sm btn-outline-primary">View Workforce</a>
                    <a href="{{ route('owner.payroll', ['site_id' => $site->id]) }}" class="btn btn-sm btn-outline-secondary">View Payroll</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">No sites found.</div></div>
    @endforelse
</div>

<div class="mt-3">{{ $sites->links() }}</div>

<div class="modal fade" id="createSiteModal" tabindex="-1" aria-labelledby="createSiteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('owner.sites.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createSiteModalLabel">Add New Site</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Site Name *</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" value="{{ old('location') }}" placeholder="e.g., Ruaka, Kiambu">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payout Method *</label>
                        <select class="form-select" name="payout_method" id="payoutMethod" required>
                            <option value="platform_managed" {{ old('payout_method', 'platform_managed') === 'platform_managed' ? 'selected' : '' }}>Platform Managed</option>
                            <option value="owner_managed" {{ old('payout_method') === 'owner_managed' ? 'selected' : '' }}>Owner Managed</option>
                        </select>
                    </div>
                    <div class="mb-0" id="mpesaField" style="display: {{ old('payout_method') === 'owner_managed' ? 'block' : 'none' }};">
                        <label class="form-label">Owner M-Pesa Account *</label>
                        <input type="text" class="form-control" name="owner_mpesa_account" value="{{ old('owner_mpesa_account') }}" placeholder="e.g., 2547XXXXXXXX">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Site</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const createSiteModal = document.getElementById('createSiteModal');
            if (createSiteModal) {
                new bootstrap.Modal(createSiteModal).show();
            }
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const payoutMethod = document.getElementById('payoutMethod');
        const mpesaField = document.getElementById('mpesaField');

        if (payoutMethod && mpesaField) {
            payoutMethod.addEventListener('change', function () {
                mpesaField.style.display = payoutMethod.value === 'owner_managed' ? 'block' : 'none';
            });
        }
    });
</script>
@endsection
