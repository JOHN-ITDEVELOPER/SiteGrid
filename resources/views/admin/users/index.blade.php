@extends('admin.layouts.app')

@section('page-title', 'Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Users</h1>
        <p class="text-muted mb-0">Manage platform users and roles</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Create User
        </a>
    </div>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="card card-body mb-4">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name, email, or phone">
        </div>
        <div class="col-md-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="">All roles</option>
                <option value="platform_admin" @selected($role === 'platform_admin')>Admin</option>
                <option value="site_owner" @selected($role === 'site_owner')>Site Owner</option>
                <option value="foreman" @selected($role === 'foreman')>Foreman</option>
                <option value="worker" @selected($role === 'worker')>Worker</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">KYC Status</label>
            <select name="kyc_status" class="form-select">
                <option value="">All</option>
                <option value="pending" @selected($kycStatus === 'pending')>Pending</option>
                <option value="approved" @selected($kycStatus === 'approved')>Approved</option>
                <option value="rejected" @selected($kycStatus === 'rejected')>Rejected</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
    </div>
</form>

<!-- Bulk Actions Bar -->
<div class="card card-body mb-3" id="bulkActionsBar" style="display: none;">
    <form method="POST" action="{{ route('admin.users.bulk') }}" id="bulkActionsForm">
        @csrf
        <div class="d-flex align-items-center gap-3">
            <span class="fw-semibold">
                <span id="selectedCount">0</span> selected
            </span>
            <select name="action" class="form-select" style="width: auto;" required>
                <option value="">Choose action...</option>
                <option value="approve_kyc">Approve KYC</option>
                <option value="reject_kyc">Reject KYC</option>
                <option value="delete">Delete</option>
            </select>
            <button type="submit" class="btn btn-primary">Apply</button>
            <button type="button" class="btn btn-secondary" onclick="clearSelection()">Clear</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" class="form-check-input" id="selectAll">
                    </th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>KYC</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input user-checkbox" name="user_ids[]" value="{{ $user->id }}">
                        </td>
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email ?? '-' }}</td>
                        <td>{{ $user->phone ?? '-' }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($user->role)) }}</td>
                        <td>
                            <span class="badge bg-{{ $user->kyc_status === 'approved' ? 'success' : ($user->kyc_status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($user->kyc_status ?? 'pending') }}
                            </span>
                        </td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No users found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $users->links() }}
</div>
@endsection

@section('script')
<script>
    const selectAllCheckbox = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkActionsForm = document.getElementById('bulkActionsForm');

    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionsBar();
        });
    }

    // Individual checkbox change
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActionsBar();
            
            // Update select all checkbox
            const allChecked = Array.from(userCheckboxes).every(cb => cb.checked);
            const noneChecked = Array.from(userCheckboxes).every(cb => !cb.checked);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
            }
        });
    });

    function updateBulkActionsBar() {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            bulkActionsBar.style.display = 'block';
            selectedCountSpan.textContent = count;
            
            // Move checked checkboxes to form
            const existingInputs = bulkActionsForm.querySelectorAll('input[name="user_ids[]"]');
            existingInputs.forEach(input => input.remove());
            
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = checkbox.value;
                bulkActionsForm.appendChild(input);
            });
        } else {
            bulkActionsBar.style.display = 'none';
        }
    }

    function clearSelection() {
        userCheckboxes.forEach(checkbox => checkbox.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkActionsBar();
    }

    // Form submission confirmation
    if (bulkActionsForm) {
        bulkActionsForm.addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="action"]').value;
            const count = document.querySelectorAll('.user-checkbox:checked').length;
            
            if (!action) {
                e.preventDefault();
                alert('Please select an action');
                return;
            }
            
            const actionText = {
                'approve_kyc': 'approve KYC for',
                'reject_kyc': 'reject KYC for',
                'delete': 'delete'
            }[action];
            
            if (!confirm(`Are you sure you want to ${actionText} ${count} user(s)?`)) {
                e.preventDefault();
            }
        });
    }
</script>
@endsection
