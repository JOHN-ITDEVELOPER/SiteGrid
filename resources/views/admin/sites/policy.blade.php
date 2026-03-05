@extends('admin.layouts.app')

@section('content')
<div class="container-lg py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-sm btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left"></i> Back to Site
            </a>
            <h1 class="h2 text-dark">Site Policy</h1>
            <p class="text-muted">{{ $site->name }}</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Lock Settings Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="bi bi-shield-lock text-warning"></i> Lock Settings</h5>
                    <small class="text-muted">Prevent site owners from modifying these settings</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.sites.policy.update', $site) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @foreach([
                            ['lock_payout_method', 'Payout Method', 'Control how workers are paid (M-Pesa, Bank Transfer, Wallet)'],
                            ['lock_payout_window', 'Payout Window', 'Control payment schedule days'],
                            ['lock_invoice_payment_method', 'Invoice Payment Method', 'Control how invoices are paid'],
                            ['lock_compliance_settings', 'Compliance Settings', 'Control audit and legal compliance requirements'],
                            ['lock_auto_payout', 'Auto-Payout', 'Control automatic payment processing'],
                            ['lock_approval_workflow', 'Approval Workflow', 'Control acceptance workflow requirements']
                        ] as [$field, $label, $description])
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $label }}</h6>
                                        <small class="text-muted">{{ $description }}</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" 
                                            id="{{ $field }}" {{ $policy->$field ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <hr class="my-4">
                        <h6 class="mb-3">Constraints & Limits</h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="max_team_members" class="form-label">Maximum Team Members</label>
                                <input type="number" id="max_team_members" name="max_team_members" 
                                    min="1" max="200" class="form-control"
                                    value="{{ $policy->max_team_members ?? 10 }}">
                                <small class="text-muted d-block mt-1">Maximum workers allowed on this site</small>
                            </div>
                            <div class="col-md-6">
                                <label for="max_foremen" class="form-label">Maximum Foremen</label>
                                <input type="number" id="max_foremen" name="max_foremen" 
                                    min="1" max="50" class="form-control"
                                    value="{{ $policy->max_foremen ?? 5 }}">
                                <small class="text-muted d-block mt-1">Maximum foremen allowed on this site</small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Policy
                            </button>
                            <a href="{{ route('admin.sites.show', $site) }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Lockdown Section -->
            @if(!$policy->is_locked_down)
                <div class="card border-warning border-2 shadow-sm mb-4">
                    <div class="card-header bg-light border-warning">
                        <h6 class="mb-1"><i class="bi bi-exclamation-triangle text-danger"></i> Temporary Lockdown</h6>
                        <small class="text-muted">Complete freeze on all site setting changes</small>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.sites.lockdown', $site) }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="lockdown_reason" class="form-label">Reason for Lockdown</label>
                                <textarea id="lockdown_reason" name="lockdown_reason" rows="3"
                                    class="form-control @error('lockdown_reason') is-invalid @enderror"
                                    placeholder="e.g., Suspected fraudulent activity, Compliance investigation..."></textarea>
                                @error('lockdown_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="lockdown_duration_hours" class="form-label">Duration</label>
                                <select id="lockdown_duration_hours" name="lockdown_duration_hours"
                                    class="form-select @error('lockdown_duration_hours') is-invalid @enderror">
                                    <option value="">Select duration...</option>
                                    <option value="1">1 hour</option>
                                    <option value="4">4 hours</option>
                                    <option value="24">1 day</option>
                                    <option value="72">3 days</option>
                                    <option value="168">1 week</option>
                                    <option value="336">2 weeks</option>
                                    <option value="720">30 days</option>
                                </select>
                                @error('lockdown_duration_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-lock"></i> Lock Down Site
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card border-danger border-2 shadow-sm mb-4 bg-danger-light">
                    <div class="card-header bg-danger border-danger">
                        <h6 class="mb-0 text-white"><i class="bi bi-shield-exclamation"></i> Site is Locked Down</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Reason:</strong></p>
                        <p class="text-muted mb-3">{{ $policy->lockdown_reason }}</p>
                        <p class="mb-3"><strong>Auto-expires:</strong> {{ $policy->lockdown_until->format('M d, Y H:i') }}</p>
                        
                        <div class="alert alert-info py-2 px-3 mb-3">
                            <small><i class="bi bi-info-circle"></i> You can manually unlock this site before the expiration time.</small>
                        </div>
                        
                        <form action="{{ route('admin.sites.unlock', $site) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-unlock"></i> Manually Unlock Now
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Policy Status Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0">Policy Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Active Locks</small>
                        <h5 class="mb-0">
                            {{ collect([
                                $policy->lock_payout_method,
                                $policy->lock_payout_window,
                                $policy->lock_invoice_payment_method,
                                $policy->lock_compliance_settings,
                                $policy->lock_auto_payout,
                                $policy->lock_approval_workflow,
                            ])->filter()->count() }}/6
                        </h5>
                    </div>

                    @if($policy->last_policy_changed_at)
                        <hr>
                        <small class="text-muted">Last Modified</small>
                        <p class="mb-1">{{ $policy->last_policy_changed_at->format('M d, Y H:i') }}</p>
                        @if($policy->changedBy)
                            <small class="text-muted">by {{ $policy->changedBy->name }}</small>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Locked Settings Summary -->
            @if($policy->getLockedSettingsMessage())
                <div class="card border-warning border-2 shadow-sm mt-3">
                    <div class="card-header bg-light border-warning">
                        <h6 class="mb-0"><i class="bi bi-info-circle text-warning"></i> Locked Settings</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach($policy->getLockedSettingsMessage() as $setting)
                                <li class="mb-2">
                                    <i class="bi bi-lock text-warning"></i>
                                    <small>{{ $setting }}</small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
