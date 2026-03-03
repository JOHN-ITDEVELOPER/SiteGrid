@extends('admin.layouts.app')

@section('page-title', 'Edit User')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Edit User</h1>
    <p class="text-muted mb-0">Update user profile and role</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="platform_admin" @selected($user->role === 'platform_admin')>Admin</option>
                        <option value="site_owner" @selected($user->role === 'site_owner')>Site Owner</option>
                        <option value="foreman" @selected($user->role === 'foreman')>Foreman</option>
                        <option value="worker" @selected($user->role === 'worker')>Worker</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" placeholder="name@example.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+2547XXXXXXXX">
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Password (optional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep">
                </div>
                <div class="col-md-6">
                    <label class="form-label">KYC Status</label>
                    <select name="kyc_status" class="form-select">
                        <option value="pending" @selected($user->kyc_status === 'pending')>Pending</option>
                        <option value="approved" @selected($user->kyc_status === 'approved')>Approved</option>
                        <option value="rejected" @selected($user->kyc_status === 'rejected')>Rejected</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
