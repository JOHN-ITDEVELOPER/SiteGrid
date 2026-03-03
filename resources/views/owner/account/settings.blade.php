@extends('owner.layouts.app')

@section('title', 'Account Settings')
@section('page-title', 'Account Settings')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <ul class="nav nav-tabs mb-4" id="accountSettingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab" aria-controls="profile-pane" aria-selected="true">Profile</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-pane" type="button" role="tab" aria-controls="security-pane" aria-selected="false">Security</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences-pane" type="button" role="tab" aria-controls="preferences-pane" aria-selected="false">Preferences</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing-pane" type="button" role="tab" aria-controls="billing-pane" aria-selected="false">Billing Per Site</button>
            </li>
        </ul>

        <div class="tab-content" id="accountSettingsTabContent">
            <div class="tab-pane fade show active" id="profile-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                <form method="POST" action="{{ route('owner.account.settings.profile.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $owner->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $owner->phone) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $owner->email) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Avatar URL</label>
                        <input type="url" class="form-control" name="avatar_url" value="{{ old('avatar_url', $owner->avatar_url) }}" placeholder="https://...">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="security-pane" role="tabpanel" aria-labelledby="security-tab" tabindex="0">
                <div class="alert alert-info">Password updates use Laravel's built-in current password validation and secure hashing.</div>
                <form method="POST" action="{{ route('owner.account.settings.security.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" minlength="8" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="password_confirmation" minlength="8" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="preferences-pane" role="tabpanel" aria-labelledby="preferences-tab" tabindex="0">
                @php
                    $notificationPrefs = old('notification_preferences', $owner->notification_preferences ?? []);
                @endphp
                <form method="POST" action="{{ route('owner.account.settings.preferences.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label">Timezone</label>
                        <select class="form-select" name="timezone" required>
                            @foreach(timezone_identifiers_list() as $timezone)
                                <option value="{{ $timezone }}" {{ old('timezone', $owner->timezone ?? 'Africa/Nairobi') === $timezone ? 'selected' : '' }}>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Language</label>
                        <select class="form-select" name="locale" required>
                            <option value="en" {{ old('locale', $owner->locale ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="sw" {{ old('locale', $owner->locale ?? 'en') === 'sw' ? 'selected' : '' }}>Swahili</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Notification Channels</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="notification_preferences[]" id="pref_sms" value="sms" {{ in_array('sms', $notificationPrefs, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pref_sms">SMS</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="notification_preferences[]" id="pref_email" value="email" {{ in_array('email', $notificationPrefs, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pref_email">Email</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="notification_preferences[]" id="pref_push" value="push" {{ in_array('push', $notificationPrefs, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pref_push">Push</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Preferences</button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="billing-pane" role="tabpanel" aria-labelledby="billing-tab" tabindex="0">
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Billing rates and terms are set by platform administrators. Contact support if you need to change your subscription plan.
                </div>

                <!-- Platform Billing Configuration -->
                <div class="mb-4">
                    <h5 class="mb-3">Platform Billing Rates</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold" width="250">Fee per Worker</td>
                                    <td>{{ $platformSettings->default_currency ?? 'KES' }} {{ number_format($platformSettings->platform_fee_per_worker ?? 50, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Billing Cadence</td>
                                    <td>{{ ucfirst($platformSettings->billing_cadence ?? 'Weekly') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Currency</td>
                                    <td>{{ $platformSettings->default_currency ?? 'KES' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Fee Model</td>
                                    <td>
                                        {{ ucfirst($platformSettings->fee_model ?? 'Flat') }}
                                        @if(($platformSettings->fee_model ?? 'flat') === 'percentage' || ($platformSettings->fee_model ?? 'flat') === 'hybrid')
                                            ({{ $platformSettings->fee_percentage ?? 0 }}%)
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Late Fee</td>
                                    <td>{{ $platformSettings->default_currency ?? 'KES' }} {{ number_format($platformSettings->late_fee_amount ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Free Trial</td>
                                    <td>
                                        @if(($platformSettings->free_trial_workers ?? 0) > 0)
                                            {{ $platformSettings->free_trial_workers }} workers for {{ $platformSettings->free_trial_weeks ?? 0 }} weeks
                                        @else
                                            No trial period
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">Invoice Reminders</td>
                                    <td>Sent {{ $platformSettings->invoice_reminder_days ?? '3,7,14' }} days before due date</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Site-Specific Billing Status -->
                <div>
                    <h5 class="mb-3">Your Sites Billing Status</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Site</th>
                                    <th>Active Workers</th>
                                    <th>Status</th>
                                    <th>Billing Cycle</th>
                                    <th>Currency</th>
                                    <th>Amount per Cycle</th>
                                    <th>Next Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sites as $site)
                                    @php
                                        $plan = $site->billing_plan ?? [];
                                        $workerCount = $site->workers_count ?? 0;
                                        $feePerWorker = $platformSettings->platform_fee_per_worker ?? 50;
                                        $currency = $platformSettings->default_currency ?? 'KES';
                                        $cadence = $platformSettings->billing_cadence ?? 'weekly';
                                        $freeTrialWorkers = $platformSettings->free_trial_workers ?? 0;
                                        $freeTrialWeeks = $platformSettings->free_trial_weeks ?? 0;
                                        
                                        // Check if site is in trial period
                                        $siteAge = $site->created_at->diffInWeeks(now());
                                        $isInTrial = ($siteAge < $freeTrialWeeks) && ($workerCount <= $freeTrialWorkers);
                                        
                                        // Calculate base amount
                                        $billableWorkers = max(0, $workerCount - ($isInTrial ? $freeTrialWorkers : 0));
                                        
                                        // Calculate based on fee model
                                        $feeModel = $platformSettings->fee_model ?? 'flat';
                                        if ($feeModel === 'flat') {
                                            $baseAmount = $billableWorkers * $feePerWorker;
                                        } elseif ($feeModel === 'percentage') {
                                            // For percentage model, assume base payroll amount per worker
                                            $avgPayrollPerWorker = 5000; // This should come from actual payroll data
                                            $feePercentage = ($platformSettings->fee_percentage ?? 5) / 100;
                                            $baseAmount = $billableWorkers * $avgPayrollPerWorker * $feePercentage;
                                        } else { // hybrid
                                            $avgPayrollPerWorker = 5000;
                                            $feePercentage = ($platformSettings->fee_percentage ?? 2) / 100;
                                            $baseAmount = ($billableWorkers * $feePerWorker) + ($billableWorkers * $avgPayrollPerWorker * $feePercentage);
                                        }
                                        
                                        // Adjust for billing cadence (weekly base)
                                        $amountPerCycle = $cadence === 'monthly' ? $baseAmount * 4 : $baseAmount;
                                        
                                        // Determine status
                                        $storedStatus = $plan['status'] ?? null;
                                        if ($isInTrial) {
                                            $status = 'trial';
                                        } elseif ($storedStatus) {
                                            $status = $storedStatus;
                                        } else {
                                            $status = 'active';
                                        }
                                        
                                        $statusBadgeClass = match($status) {
                                            'active' => 'bg-success',
                                            'trial' => 'bg-info',
                                            'past_due' => 'bg-warning text-dark',
                                            'canceled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        
                                        // Calculate next due date
                                        if (!empty($plan['next_due_date'])) {
                                            $nextDue = $plan['next_due_date'];
                                        } else {
                                            // Calculate based on cadence
                                            $nextDue = $cadence === 'monthly' 
                                                ? now()->addMonth()->format('Y-m-d')
                                                : now()->addWeek()->format('Y-m-d');
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $site->name }}</div>
                                            <small class="text-muted">ID: {{ $site->id }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $workerCount }}</span>
                                            @if($isInTrial && $workerCount <= $freeTrialWorkers)
                                                <small class="text-success d-block">(Free trial)</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }}">{{ ucfirst($status) }}</span>
                                        </td>
                                        <td>{{ ucfirst($cadence) }}</td>
                                        <td>{{ $currency }}</td>
                                        <td class="fw-semibold">
                                            {{ number_format($amountPerCycle, 2) }}
                                            @if($isInTrial && $workerCount > $freeTrialWorkers)
                                                <small class="text-muted d-block">
                                                    ({{ $workerCount - $freeTrialWorkers }} billable)
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($nextDue)->format('M d, Y') }}
                                            @if($isInTrial)
                                                <small class="text-muted d-block">
                                                    Trial ends: {{ $site->created_at->addWeeks($freeTrialWeeks)->format('M d, Y') }}
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No owned sites available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($sites->isNotEmpty())
                        <div class="text-muted small mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Amounts calculated based on active workers × platform rates. Trial period applies to first {{ $platformSettings->free_trial_weeks ?? 0 }} weeks for up to {{ $platformSettings->free_trial_workers ?? 0 }} workers.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
