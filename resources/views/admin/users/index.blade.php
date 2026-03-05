@extends('admin.layouts.app')

@section('page-title', 'Users')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Users</h1>
        <p class="text-muted mb-0">Manage platform users and roles</p>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-success">
            <i class="bi bi-download"></i> <span class="d-none d-sm-inline">Export CSV</span><span class="d-sm-none">Export</span>
        </a>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Create User</span><span class="d-sm-none">Add</span>
        </a>
    </div>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="card card-body mb-4">
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name, email, or phone">
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="">All roles</option>
                <option value="platform_admin" @selected($role === 'platform_admin')>Admin</option>
                <option value="site_owner" @selected($role === 'site_owner')>Site Owner</option>
                <option value="foreman" @selected($role === 'foreman')>Foreman</option>
                <option value="worker" @selected($role === 'worker')>Worker</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label">KYC Status</label>
            <select name="kyc_status" class="form-select">
                <option value="">All</option>
                <option value="pending" @selected($kycStatus === 'pending')>Pending</option>
                <option value="approved" @selected($kycStatus === 'approved')>Approved</option>
                <option value="rejected" @selected($kycStatus === 'rejected')>Rejected</option>
            </select>
        </div>
        <div class="col-12 col-lg-2 d-flex align-items-end">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
    </div>
</form>

<!-- Bulk Actions Bar -->
<div class="card card-body mb-3" id="bulkActionsBar" style="display: none;">
    <form method="POST" action="{{ route('admin.users.bulk') }}" id="bulkActionsForm">
        @csrf
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 gap-sm-3">
            <span class="fw-semibold text-nowrap">
                <span id="selectedCount">0</span> selected
            </span>
            <select name="action" class="form-select flex-grow-1" style="min-width: 200px;" required>
                <option value="">Choose action...</option>
                <option value="approve_kyc">Approve KYC</option>
                <option value="reject_kyc">Reject KYC</option>
                <option value="suspend">Suspend Accounts</option>
                <option value="reactivate">Reactivate Accounts</option>
                <option value="delete">Delete</option>
            </select>
            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                <button type="submit" class="btn btn-primary">Apply</button>
                <button type="button" class="btn btn-secondary" onclick="clearSelection()">Clear</button>
            </div>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" class="form-check-input" id="selectAll">
                    </th>
                    <th style="min-width: 150px;">Name</th>
                    <th style="min-width: 150px;" class="d-none d-sm-table-cell">Email</th>
                    <th style="min-width: 120px;" class="d-none d-md-table-cell">Phone</th>
                    <th style="min-width: 100px;">Role</th>
                    <th style="min-width: 90px;">Status</th>
                    <th style="min-width: 120px;" class="d-none d-lg-table-cell">Sites</th>
                    <th style="min-width: 90px;" class="d-none d-sm-table-cell">KYC</th>
                    <th style="min-width: 90px;" class="d-none d-md-table-cell">Joined</th>
                    <th style="width: 70px; position: sticky; right: 0; background-color: #f8f9fa; z-index: 10;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $activeSites = $user->siteWorkers->whereNull('ended_at');
                        $siteCount = $activeSites->count();
                        $firstTwo = $activeSites->take(2);
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input user-checkbox" name="user_ids[]" value="{{ $user->id }}">
                        </td>
                        <td class="fw-semibold">
                            <span class="text-truncate d-inline-block" style="max-width: 140px;" title="{{ $user->name }}">{{ $user->name }}</span>
                        </td>
                        <td class="d-none d-sm-table-cell">
                            <span class="text-truncate d-inline-block" style="max-width: 140px;" title="{{ $user->email }}">{{ $user->email ?? '-' }}</span>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $user->phone ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $user->effective_role === 'platform_admin' ? 'danger' : ($user->effective_role === 'site_owner' ? 'primary' : ($user->effective_role === 'foreman' ? 'warning' : 'secondary')) }} text-nowrap">
                                {{ str_replace('_', ' ', ucwords(str_replace('_', ' ', $user->effective_role))) }}
                            </span>
                        </td>
                        <td>
                            @if($user->is_suspended)
                                <span class="badge bg-danger text-nowrap">
                                    <i class="bi bi-ban"></i> <span class="d-none d-md-inline">Suspended</span>
                                </span>
                            @else
                                <span class="badge bg-success text-nowrap">
                                    <i class="bi bi-check-circle"></i> <span class="d-none d-md-inline">Active</span>
                                </span>
                            @endif
                        </td>
                        <td class="d-none d-lg-table-cell">
                            @if($siteCount > 0)
                                <div class="d-flex flex-column gap-1">
                                    @foreach($firstTwo as $assignment)
                                        <span class="badge text-bg-light text-dark border text-truncate" title="{{ $assignment->site->name ?? 'Unknown' }}">
                                            <i class="bi bi-building"></i> <span class="d-none d-xl-inline">{{ $assignment->site->name ?? 'Unknown' }}</span>
                                        </span>
                                    @endforeach
                                    @if($siteCount > 2)
                                        <small class="text-muted">+{{ $siteCount - 2 }} more</small>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="d-none d-sm-table-cell">
                            <span class="badge bg-{{ $user->kyc_status === 'approved' ? 'success' : ($user->kyc_status === 'rejected' ? 'danger' : 'warning') }} text-nowrap">
                                {{ ucfirst($user->kyc_status ?? 'pending') }}
                            </span>
                        </td>
                        <td class="d-none d-md-table-cell">{{ $user->created_at->format('M d, Y') }}</td>
                        <td style="position: sticky; right: 0; background-color: #f8f9fa; z-index: 9;" class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.users.show', $user) }}"><i class="bi bi-eye"></i> View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}"><i class="bi bi-pencil"></i> Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this user?')"><i class="bi bi-trash"></i> Delete</button>
                                    </form></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No users found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-center">
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
                'suspend': 'suspend',
                'reactivate': 'reactivate',
                'delete': 'delete'
            }[action] || 'apply action to';
            
            if (!confirm(`Are you sure you want to ${actionText} ${count} user(s)?`)) {
                e.preventDefault();
            }
        });
    }
</script>
