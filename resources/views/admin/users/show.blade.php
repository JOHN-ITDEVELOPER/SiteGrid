@extends('admin.layouts.app')

@section('page-title', 'User Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $user->name }}</h1>
        <p class="text-muted mb-0">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
        @if($user->is_suspended)
            <span class="badge bg-danger mt-1">
                <i class="bi bi-ban"></i> Account Suspended
            </span>
        @endif
        @if($user->password_reset_required)
            <span class="badge bg-warning mt-1">
                <i class="bi bi-key"></i> Password Reset Required
            </span>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.activity', $user) }}" class="btn btn-outline-info">
            <i class="bi bi-clock-history"></i> Activity Log
        </a>
        @if($user->role !== 'platform_admin')
            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning" onclick="return confirm('Impersonate {{ $user->name }}?')">
                    <i class="bi bi-person-check"></i> Impersonate
                </button>
            </form>
        @endif
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Profile</h5>
                <p class="mb-2"><strong>Email:</strong> {{ $user->email ?? '-' }}</p>
                <p class="mb-2"><strong>Phone:</strong> {{ $user->phone ?? '-' }}</p>
                <p class="mb-2"><strong>KYC:</strong> 
                    <span class="badge bg-{{ $user->kyc_status === 'approved' ? 'success' : ($user->kyc_status === 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($user->kyc_status ?? 'pending') }}
                    </span>
                </p>
                <p class="mb-0"><strong>Joined:</strong> {{ $user->created_at->format('M d, Y') }}</p>
            </div>
        </div>

        @if($user->id !== auth()->id())
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-shield-exclamation"></i> Account Controls</h6>
                </div>
                <div class="card-body">
                    @if($user->is_suspended)
                        <div class="alert alert-danger py-2 px-3 mb-3">
                            <strong><i class="bi bi-ban"></i> Suspended</strong>
                            <p class="mb-1 small">{{ $user->suspension_reason }}</p>
                            <small class="text-muted">{{ $user->suspended_at->format('M d, Y H:i') }}</small>
                        </div>
                        <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Reactivate this account?')">
                                <i class="bi bi-check-circle"></i> Reactivate Account
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#suspendModal">
                            <i class="bi bi-ban"></i> Suspend Account
                        </button>
                    @endif

                    <form method="POST" action="{{ route('admin.users.force-password-reset', $user) }}">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100 mb-2" onclick="return confirm('Force password reset for this user?')">
                            <i class="bi bi-key"></i> Force Password Reset
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Permanently delete this user? This action cannot be undone.')">
                            <i class="bi bi-trash"></i> Delete Account
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if(in_array($user->role, ['worker', 'foreman']) || $user->siteWorkers->isNotEmpty())
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Site Assignments</h5>
                    @php
                        $activeSites = $user->siteWorkers->whereNull('ended_at');
                        $endedSites = $user->siteWorkers->whereNotNull('ended_at');
                    @endphp
                    
                    @if ($activeSites->isNotEmpty())
                        <h6 class="text-success mb-2">
                            <i class="bi bi-check-circle-fill"></i> Active ({{ $activeSites->count() }})
                        </h6>
                        <div class="mb-3">
                            @foreach ($activeSites as $assignment)
                                <div class="border rounded p-2 mb-2 bg-light">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong class="text-primary">{{ $assignment->site->name ?? 'Unknown' }}</strong>
                                        <span class="badge bg-{{ $assignment->is_foreman ? 'warning' : 'secondary' }}">
                                            {{ $assignment->is_foreman ? 'Foreman' : 'Worker' }}
                                        </span>
                                    </div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar-check"></i> Since {{ \Carbon\Carbon::parse($assignment->started_at)->format('M d, Y') }}
                                    </small>
                                    @if($assignment->role)
                                        <small class="text-muted d-block">
                                            <i class="bi bi-briefcase"></i> {{ $assignment->role }}
                                        </small>
                                    @endif
                                    <small class="text-muted d-block">
                                        <i class="bi bi-cash"></i> Daily: KES {{ number_format($assignment->daily_rate) }} | Weekly: KES {{ number_format($assignment->weekly_rate) }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    @if ($endedSites->isNotEmpty())
                        <h6 class="text-muted mb-2">
                            <i class="bi bi-x-circle"></i> Ended ({{ $endedSites->count() }})
                        </h6>
                        <div>
                            @foreach ($endedSites as $assignment)
                                <div class="border rounded p-2 mb-2 opacity-75">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong class="text-secondary">{{ $assignment->site->name ?? 'Unknown' }}</strong>
                                        <span class="badge bg-secondary">
                                            {{ $assignment->is_foreman ? 'Foreman' : 'Worker' }}
                                        </span>
                                    </div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar-range"></i> 
                                        {{ \Carbon\Carbon::parse($assignment->started_at)->format('M d, Y') }} - 
                                        {{ \Carbon\Carbon::parse($assignment->ended_at)->format('M d, Y') }}
                                    </small>
                                    @if($assignment->role)
                                        <small class="text-muted d-block">
                                            <i class="bi bi-briefcase"></i> {{ $assignment->role }}
                                        </small>
                                    @endif
                                    @if($assignment->ended_reason)
                                        <small class="text-danger d-block">
                                            <i class="bi bi-info-circle"></i> {{ $assignment->ended_reason }}
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if($user->siteWorkers->isNotEmpty())
                <div class="mt-3">
                    <a href="{{ route('admin.workers.history', $user) }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-list-ul"></i> View Full Worker History
                    </a>
                </div>
            @endif
        @endif
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

<!-- Suspend Account Modal -->
@if($user->id !== auth()->id() && !$user->is_suspended)
    <div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="suspendModalLabel">
                            <i class="bi bi-ban"></i> Suspend Account: {{ $user->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This will immediately suspend the user's account and prevent them from accessing the system.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Suspension Reason <span class="text-danger">*</span></label>
                            <textarea name="suspension_reason" class="form-control" rows="3" required placeholder="Enter the reason for suspending this account..."></textarea>
                            <small class="text-muted">This reason will be visible to administrators.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-ban"></i> Suspend Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
