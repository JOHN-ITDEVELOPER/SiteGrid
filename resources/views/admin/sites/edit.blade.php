@extends('admin.layouts.app')

@section('page-title', 'Edit Site')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Edit Site</h1>
    <p class="text-muted mb-0">{{ $site->name }}</p>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.sites.update', $site) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-3">
                        <!-- Owner Selection -->
                        <div class="col-md-12">
                            <label class="form-label">Site Owner *</label>
                            <select name="owner_id" class="form-select" required>
                                <option value="">Select an owner</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected(old('owner_id', $site->owner_id) == $owner->id)>
                                        {{ $owner->name }} ({{ $owner->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('owner_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Site Name -->
                        <div class="col-md-6">
                            <label class="form-label">Site Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $site->name) }}" required>
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $site->location) }}">
                            @error('location')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="col-md-12">
                            <label class="form-label">Site Status</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_completed" id="isCompleted" value="1" @checked(old('is_completed', $site->is_completed))>
                                <label class="form-check-label" for="isCompleted">
                                    <strong>Mark as Completed</strong>
                                    <small class="d-block text-muted">Site has finished work</small>
                                </label>
                            </div>
                        </div>

                        <!-- Payout Method -->
                        <div class="col-md-12">
                            <label class="form-label">Payout Method *</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payout_method" id="payoutPlatform" value="platform_managed" @checked(old('payout_method', $site->payout_method) === 'platform_managed') onchange="togglePayoutFields()">
                                    <label class="form-check-label" for="payoutPlatform">
                                        <strong>Platform Managed</strong>
                                        <small class="d-block text-muted">Mjengo handles all payouts</small>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payout_method" id="payoutOwner" value="owner_managed" @checked(old('payout_method', $site->payout_method) === 'owner_managed') onchange="togglePayoutFields()">
                                    <label class="form-check-label" for="payoutOwner">
                                        <strong>Owner Managed</strong>
                                        <small class="d-block text-muted">Owner payout own staff</small>
                                    </label>
                                </div>
                            </div>
                            @error('payout_method')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Owner M-Pesa Account (conditional) -->
                        <div class="col-md-6" id="mpesaField" style="display: none;">
                            <label class="form-label">Owner M-Pesa Account</label>
                            <input type="text" name="owner_mpesa_account" class="form-control" value="{{ old('owner_mpesa_account', $site->owner_mpesa_account) }}" placeholder="0712345678">
                            @error('owner_mpesa_account')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Payout Windows -->
                        <div class="col-md-6">
                            <label class="form-label">Payout Window Start *</label>
                            <select name="payout_window_start" class="form-select" required>
                                <option value="">Select day</option>
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <option value="{{ $day }}" @selected(old('payout_window_start', $site->payout_window_start) === $day)>{{ $day }}</option>
                                @endforeach
                            </select>
                            @error('payout_window_start')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Payout Window End *</label>
                            <select name="payout_window_end" class="form-select" required>
                                <option value="">Select day</option>
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <option value="{{ $day }}" @selected(old('payout_window_end', $site->payout_window_end) === $day)>{{ $day }}</option>
                                @endforeach
                            </select>
                            @error('payout_window_end')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Invoice Due Days -->
                        <div class="col-md-6">
                            <label class="form-label">Invoice Due Days *</label>
                            <input type="number" name="invoice_due_days" class="form-control" value="{{ old('invoice_due_days', $site->invoice_due_days) }}" min="1" max="365" required>
                            <small class="text-muted">Number of days before invoice payment is due</small>
                            @error('invoice_due_days')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light mb-3">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bi bi-info-circle"></i> Site Info
                </h5>
                <p class="mb-2"><strong>Created:</strong> {{ $site->created_at->format('M d, Y') }}</p>
                <p class="mb-0"><strong>Active Workers:</strong> {{ $site->workers()->whereNull('ended_at')->count() }}</p>
            </div>
        </div>

        <!-- Delete Section -->
        <div class="card border-danger">
            <div class="card-body">
                <h5 class="card-title text-danger mb-3">
                    <i class="bi bi-exclamation-triangle"></i> Danger Zone
                </h5>
                <form method="POST" action="{{ route('admin.sites.delete', $site) }}" class="mb-0" onsubmit="return confirm('Are you sure you want to delete this site? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> Delete Site
                    </button>
                </form>
                <small class="text-muted d-block mt-2">You can only delete sites with no active workers or financial history.</small>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    function togglePayoutFields() {
        const method = document.querySelector('input[name="payout_method"]:checked').value;
        const mpesaField = document.getElementById('mpesaField');
        const mpesaInput = document.querySelector('input[name="owner_mpesa_account"]');
        
        if (method === 'owner_managed') {
            mpesaField.style.display = 'block';
            mpesaInput.required = true;
        } else {
            mpesaField.style.display = 'none';
            mpesaInput.required = false;
        }
    }

    // Initialize on page load
    togglePayoutFields();
</script>
@endsection
