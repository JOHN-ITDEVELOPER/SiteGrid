@extends('admin.layouts.app')

@section('page-title', 'User Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $user->name }}</h1>
        <p class="text-muted mb-0">{{ $user->role }}</p>
    </div>
    <div class="d-flex gap-2">
        @if($user->role !== 'platform_admin')
            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning" onclick="return confirm('Impersonate {{ $user->name }}?')">
                    <i class="bi bi-person-check"></i> Impersonate
                </button>
            </form>
        @endif
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Profile</h5>
                <p class="mb-2"><strong>Email:</strong> {{ $user->email ?? '-' }}</p>
                <p class="mb-2"><strong>Phone:</strong> {{ $user->phone ?? '-' }}</p>
                <p class="mb-2"><strong>KYC:</strong> {{ ucfirst($user->kyc_status ?? 'pending') }}</p>
                <p class="mb-0"><strong>Joined:</strong> {{ $user->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Owned Sites</h5>
                    @if($user->role === 'site_owner')
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSiteModal">
                            <i class="bi bi-plus-lg"></i> Add Site
                        </button>
                    @endif
                </div>
                @if ($user->ownedSites->isEmpty())
                    <p class="text-muted mb-0">No owned sites.</p>
                @else
                    <ul class="list-group">
                        @foreach ($user->ownedSites as $site)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $site->name }}
                                <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Recent Payouts</h5>
                @if ($user->payouts->isEmpty())
                    <p class="text-muted mb-0">No payouts found.</p>
                @else
                    <ul class="list-group">
                        @foreach ($user->payouts->take(5) as $payout)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $payout->created_at->format('M d, Y') }}</span>
                                <span>KES {{ number_format($payout->net_amount) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

@if($user->role === 'site_owner')
    <div class="modal fade" id="addSiteModal" tabindex="-1" aria-labelledby="addSiteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.users.sites.store', $user) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSiteModalLabel">Add Site for {{ $user->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Site Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g., Ruaka, Kiambu">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payout Method *</label>
                            <select name="payout_method" class="form-select" required>
                                <option value="platform_managed">Platform Managed</option>
                                <option value="owner_managed">Owner Managed</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Owner M-Pesa Account</label>
                            <input type="text" name="owner_mpesa_account" class="form-control" placeholder="Required when payout method is Owner Managed">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Site</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
