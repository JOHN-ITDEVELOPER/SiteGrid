@extends('admin.layouts.app')

@section('page-title', 'Create User')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Create User</h1>
    <p class="text-muted mb-0">Add a new platform user</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-select" id="roleSelect" required>
                        <option value="">Select role</option>
                        <option value="platform_admin" @selected(old('role') === 'platform_admin')>Admin</option>
                        <option value="site_owner" @selected(old('role') === 'site_owner')>Site Owner</option>
                        <option value="foreman" @selected(old('role') === 'foreman')>Foreman</option>
                        <option value="worker" @selected(old('role') === 'worker')>Worker</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="name@example.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+2547XXXXXXXX">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">KYC Status</label>
                    <select name="kyc_status" class="form-select">
                        <option value="pending" @selected(old('kyc_status') === 'pending' || !old('kyc_status'))>Pending</option>
                        <option value="approved" @selected(old('kyc_status') === 'approved')>Approved</option>
                        <option value="rejected" @selected(old('kyc_status') === 'rejected')>Rejected</option>
                    </select>
                </div>

                <!-- Site Assignment Fields - Only show for Foreman/Worker -->
                <div id="siteAssignmentFields" style="display: none;" class="col-12">
                    <hr class="my-3">
                    <h6 class="mb-3"><i class="bi bi-building"></i> Site Assignment</h6>
                </div>

                <div id="siteField" class="col-md-6" style="display: none;">
                    <label class="form-label">Site *</label>
                    <select name="site_id" class="form-select">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('site_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div id="isForemanField" class="col-md-6" style="display: none;">
                    <label class="form-label">Position</label>
                    <div class="form-check">
                        <input type="checkbox" name="is_foreman" value="1" class="form-check-input" id="isForemanCheckbox" @checked(old('is_foreman'))>
                        <label class="form-check-label" for="isForemanCheckbox">
                            Assign as Foreman (supervisor role)
                        </label>
                    </div>
                </div>

                <div id="dailyRateField" class="col-md-6" style="display: none;">
                    <label class="form-label">Daily Rate (KES) *</label>
                    <input type="number" name="daily_rate" class="form-control" value="{{ old('daily_rate') }}" step="0.01" min="0">
                    @error('daily_rate')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div id="weeklyRateField" class="col-md-6" style="display: none;">
                    <label class="form-label">Weekly Rate (KES) *</label>
                    <input type="number" name="weekly_rate" class="form-control" value="{{ old('weekly_rate') }}" step="0.01" min="0">
                    @error('weekly_rate')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div id="startedAtField" class="col-md-6" style="display: none;">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="started_at" class="form-control" value="{{ old('started_at') }}">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script>
    const roleSelect = document.getElementById('roleSelect');
    const siteAssignmentFields = document.getElementById('siteAssignmentFields');
    const siteField = document.getElementById('siteField');
    const isForemanField = document.getElementById('isForemanField');
    const dailyRateField = document.getElementById('dailyRateField');
    const weeklyRateField = document.getElementById('weeklyRateField');
    const startedAtField = document.getElementById('startedAtField');
    const siteSelect = document.querySelector('select[name="site_id"]');
    const dailyRateInput = document.querySelector('input[name="daily_rate"]');
    const weeklyRateInput = document.querySelector('input[name="weekly_rate"]');

    function updateFieldVisibility() {
        const role = roleSelect.value;
        const isWorkerOrForeman = role === 'foreman' || role === 'worker';

        siteAssignmentFields.style.display = isWorkerOrForeman ? 'block' : 'none';
        siteField.style.display = isWorkerOrForeman ? 'block' : 'none';
        isForemanField.style.display = isWorkerOrForeman ? 'block' : 'none';
        dailyRateField.style.display = isWorkerOrForeman ? 'block' : 'none';
        weeklyRateField.style.display = isWorkerOrForeman ? 'block' : 'none';
        startedAtField.style.display = isWorkerOrForeman ? 'block' : 'none';

        // Update validation requirements
        if (isWorkerOrForeman) {
            siteSelect.required = true;
            dailyRateInput.required = true;
            weeklyRateInput.required = true;
        } else {
            siteSelect.required = false;
            dailyRateInput.required = false;
            weeklyRateInput.required = false;
        }
    }

    roleSelect.addEventListener('change', updateFieldVisibility);

    // Initialize on page load
    updateFieldVisibility();
</script>
@endsection
