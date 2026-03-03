@extends('admin.layouts.app')

@section('content')
<div class="container-lg py-5">
    <div class="mb-5">
        <h1 class="h2 text-dark mb-3">KYC Verification Queue</h1>
        <p class="text-muted">Review and approve user identity verification</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Tabs for Status -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ request('status', 'pending') == 'pending' ? 'active' : '' }}" 
                    onclick="window.location='?status=pending'" type="button">
                <i class="bi bi-clipboard-check"></i> Pending ({{ $pendingCount }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ request('status') == 'approved' ? 'active' : '' }}" 
                    onclick="window.location='?status=approved'" type="button">
                <i class="bi bi-shield-check"></i> Approved ({{ $approvedCount }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ request('status') == 'rejected' ? 'active' : '' }}" 
                    onclick="window.location='?status=rejected'" type="button">
                <i class="bi bi-x-circle"></i> Rejected ({{ $rejectedCount }})
            </button>
        </li>
    </ul>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <small class="fw-semibold">{{ $user->name ?? 'N/A' }}</small><br>
                                <small class="text-muted font-monospace">{{ $user->id }}</small>
                            </td>
                            <td>
                                <small class="font-monospace">{{ $user->phone }}</small>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$user->kyc_status] ?? 'secondary' }}">
                                    {{ ucfirst($user->kyc_status) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                @if($user->kyc_status === 'pending')
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#reviewModal{{ $user->id }}">
                                        Review
                                    </button>
                                @elseif($user->kyc_status === 'approved')
                                    <span class="text-success small"><i class="bi bi-check-circle"></i> Approved</span>
                                @else
                                    <span class="text-danger small"><i class="bi bi-x-circle"></i> Rejected</span>
                                @endif

                                <!-- Review Modal -->
                                <div class="modal fade" id="reviewModal{{ $user->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Review KYC - {{ $user->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Name:</strong> {{ $user->name ?? 'N/A' }}</p>
                                                <p><strong>Phone:</strong> {{ $user->phone }}</p>
                                                <p><strong>Role:</strong> {{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
                                                <p><strong>User ID:</strong> <code>{{ $user->id }}</code></p>
                                                <p><strong>Joined:</strong> {{ $user->created_at->format('M d, Y H:i A') }}</p>
                                                <hr>
                                                <p class="text-muted small">KYC verification is pending approval. Please review the details above and verify or reject.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" action="{{ route('admin.kyc.reject', $user) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger">Reject</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.kyc.approve', $user) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success">Verify</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                @if(request('status') === 'pending')
                                    No pending verifications
                                @elseif(request('status') === 'approved')
                                    No approved users
                                @else
                                    No rejected users
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
