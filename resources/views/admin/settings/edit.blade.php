@extends('admin.layouts.app')

@section('page-title', 'Platform Settings')

@section('style')
<style>
    .settings-container {
        display: flex;
        gap: 20px;
    }
    .settings-nav {
        width: 250px;
        flex-shrink: 0;
    }
    .settings-nav .nav-link {
        color: #4b5563;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 4px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .settings-nav .nav-link:hover {
        background: #f3f4f6;
        color: #1e1b4b;
    }
    .settings-nav .nav-link.active {
        background: #1e1b4b;
        color: white;
    }
    .settings-content {
        flex: 1;
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .env-badge {
        position: fixed;
        top: 80px;
        right: 30px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        z-index: 1000;
    }
    .env-sandbox {
        background: #fef3c7;
        color: #92400e;
    }
    .env-production {
        background: #fee2e2;
        color: #991b1b;
    }
    .secret-value {
        filter: blur(4px);
        cursor: pointer;
        user-select: none;
    }
    .secret-value.revealed {
        filter: none;
    }
    .test-btn {
        margin-left: 10px;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Platform Settings</h1>
        <p class="text-muted mb-0">Configure platform behavior and integrations</p>
    </div>
    <div class="env-badge {{ $settings->environment === 'production' ? 'env-production' : 'env-sandbox' }}">
        {{ strtoupper($settings->environment ?? 'sandbox') }}
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="settings-container">
    <!-- Left Navigation -->
    <div class="settings-nav">
        <div class="nav flex-column">
            <a class="nav-link {{ (!request('tab') || request('tab') === 'general') ? 'active' : '' }}" href="#general" data-bs-toggle="pill">
                <i class="bi bi-gear"></i> General
            </a>
            <a class="nav-link {{ request('tab') === 'authentication' ? 'active' : '' }}" href="#authentication" data-bs-toggle="pill">
                <i class="bi bi-shield-lock"></i> Authentication
            </a>
            <a class="nav-link {{ request('tab') === 'integrations' ? 'active' : '' }}" href="#integrations" data-bs-toggle="pill">
                <i class="bi bi-plug"></i> Integrations
            </a>
            <a class="nav-link {{ request('tab') === 'accounts' ? 'active' : '' }}" href="#accounts" data-bs-toggle="pill">
                <i class="bi bi-credit-card"></i> Payment Accounts
            </a>
            <a class="nav-link {{ request('tab') === 'billing' ? 'active' : '' }}" href="#billing" data-bs-toggle="pill">
                <i class="bi bi-receipt"></i> Billing & Pricing
            </a>
            <a class="nav-link {{ request('tab') === 'payroll' ? 'active' : '' }}" href="#payroll" data-bs-toggle="pill">
                <i class="bi bi-cash-coin"></i> Payroll & Payouts
            </a>
            <a class="nav-link {{ request('tab') === 'notifications' ? 'active' : '' }}" href="#notifications" data-bs-toggle="pill">
                <i class="bi bi-bell"></i> Notifications
            </a>
            <a class="nav-link {{ request('tab') === 'webhooks' ? 'active' : '' }}" href="#webhooks" data-bs-toggle="pill">
                <i class="bi bi-lightning"></i> Webhooks & API
            </a>
            <a class="nav-link {{ request('tab') === 'ussd' ? 'active' : '' }}" href="#ussd" data-bs-toggle="pill">
                <i class="bi bi-phone"></i> USSD & Shortcodes
            </a>
            <a class="nav-link {{ request('tab') === 'security' ? 'active' : '' }}" href="#security" data-bs-toggle="pill">
                <i class="bi bi-lock"></i> Security
            </a>
            <a class="nav-link {{ request('tab') === 'features' ? 'active' : '' }}" href="#features" data-bs-toggle="pill">
                <i class="bi bi-flag"></i> Feature Flags
            </a>
            <a class="nav-link {{ request('tab') === 'data' ? 'active' : '' }}" href="#data" data-bs-toggle="pill">
                <i class="bi bi-database"></i> Data & Backups
            </a>
            <a class="nav-link {{ request('tab') === 'legal' ? 'active' : '' }}" href="#legal" data-bs-toggle="pill">
                <i class="bi bi-file-text"></i> Legal
            </a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="settings-content">
        <div class="tab-content">
            <!-- 1. General Settings -->
            <div class="tab-pane fade {{ (!request('tab') || request('tab') === 'general') ? 'show active' : '' }}" id="general">
                <h4 class="mb-3">General Platform Settings</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="general">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Platform Name</label>
                            <input type="text" name="platform_name" class="form-control" 
                                value="{{ old('platform_name', $settings->platform_name ?? 'Mjengo') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Support Email</label>
                            <input type="email" name="support_email" class="form-control" 
                                value="{{ old('support_email', $settings->support_email) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Platform Description</label>
                            <textarea name="platform_description" class="form-control" rows="2">{{ old('platform_description', $settings->platform_description) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Country Code</label>
                            <input type="text" name="default_country_code" class="form-control" 
                                value="{{ old('default_country_code', $settings->default_country_code ?? '+254') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Timezone</label>
                            <select name="timezone" class="form-select" required>
                                <option value="Africa/Nairobi" {{ ($settings->timezone ?? 'Africa/Nairobi') === 'Africa/Nairobi' ? 'selected' : '' }}>Africa/Nairobi (EAT)</option>
                                <option value="UTC" {{ ($settings->timezone ?? '') === 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Workweek Start</label>
                            <select name="workweek_start" class="form-select" required>
                                <option value="Monday" {{ ($settings->workweek_start ?? 'Monday') === 'Monday' ? 'selected' : '' }}>Monday</option>
                                <option value="Sunday" {{ ($settings->workweek_start ?? '') === 'Sunday' ? 'selected' : '' }}>Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Number Format</label>
                            <input type="text" name="number_format" class="form-control" 
                                value="{{ old('number_format', $settings->number_format ?? 'en_KE') }}" required>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. Authentication & Access -->
            <div class="tab-pane fade {{ request('tab') === 'authentication' ? 'show active' : '' }}" id="authentication">
                <h4 class="mb-3">Authentication & Access Control</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="authentication">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">OTP Method</label>
                            <select name="otp_method" class="form-select" required>
                                <option value="sms" {{ ($settings->otp_method ?? 'sms') === 'sms' ? 'selected' : '' }}>SMS</option>
                                <option value="voice" {{ ($settings->otp_method ?? '') === 'voice' ? 'selected' : '' }}>Voice Call</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">OTP Expiry (seconds)</label>
                            <input type="number" name="otp_expiry_seconds" class="form-control" min="30" max="300"
                                value="{{ old('otp_expiry_seconds', $settings->otp_expiry_seconds ?? 120) }}" required>
                            <small class="text-muted">Range: 30-300 seconds</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password Min Length</label>
                            <input type="number" name="password_min_length" class="form-control" min="6" max="32"
                                value="{{ old('password_min_length', $settings->password_min_length ?? 8) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout_minutes" class="form-control" min="15" max="480"
                                value="{{ old('session_timeout_minutes', $settings->session_timeout_minutes ?? 120) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Concurrent Sessions</label>
                            <input type="number" name="max_concurrent_sessions" class="form-control" min="1" max="10"
                                value="{{ old('max_concurrent_sessions', $settings->max_concurrent_sessions ?? 3) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin 2FA Grace Period (days)</label>
                            <input type="number" name="admin_2fa_grace_days" class="form-control" min="0" max="30"
                                value="{{ old('admin_2fa_grace_days', $settings->admin_2fa_grace_days ?? 7) }}" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="password_require_complexity" value="1"
                                    {{ old('password_require_complexity', $settings->password_require_complexity ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">Require Password Complexity</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="admin_2fa_required" value="1"
                                    {{ old('admin_2fa_required', $settings->admin_2fa_required ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">Require Admin 2FA</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 3. Integrations -->
            <div class="tab-pane fade {{ request('tab') === 'integrations' ? 'show active' : '' }}" id="integrations">
                <h4 class="mb-3">Integrations (Payments & USSD)</h4>
                
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Sensitive:</strong> Changing live credentials requires admin 2FA
                </div>

                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="integrations">

                    <!-- MPesa Config -->
                    <h5 class="mb-3 mt-4">MPesa (Daraja API)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Consumer Key</label>
                            <input type="text" name="mpesa_config[consumer_key]" class="form-control" 
                                value="{{ old('mpesa_config.consumer_key', $settings->mpesa_config['consumer_key'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Consumer Secret</label>
                            <div class="input-group">
                                <input type="password" name="mpesa_config[consumer_secret]" class="form-control secret-input" 
                                    value="{{ old('mpesa_config.consumer_secret', $settings->mpesa_config['consumer_secret'] ?? '') }}">
                                <button class="btn btn-outline-secondary reveal-btn" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shortcode</label>
                            <input type="text" name="mpesa_config[shortcode]" class="form-control" 
                                value="{{ old('mpesa_config.shortcode', $settings->mpesa_config['shortcode'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passkey</label>
                            <input type="password" name="mpesa_config[passkey]" class="form-control" 
                                value="{{ old('mpesa_config.passkey', $settings->mpesa_config['passkey'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Environment</label>
                            <select name="mpesa_config[environment]" class="form-select">
                                <option value="sandbox" {{ ($settings->mpesa_config['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                <option value="live" {{ ($settings->mpesa_config['environment'] ?? '') === 'live' ? 'selected' : '' }}>Live</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-outline-primary test-mpesa-btn">
                                <i class="bi bi-play-circle me-1"></i>Test Connection
                            </button>
                            <span class="test-result ms-2"></span>
                        </div>
                    </div>

                    <!-- USSD Config -->
                    <h5 class="mb-3 mt-4">USSD Provider</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Provider Username</label>
                            <input type="text" name="ussd_config[username]" class="form-control" 
                                value="{{ old('ussd_config.username', $settings->ussd_config['username'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Key</label>
                            <input type="password" name="ussd_config[api_key]" class="form-control" 
                                value="{{ old('ussd_config.api_key', $settings->ussd_config['api_key'] ?? '') }}">
                        </div>
                    </div>

                    <!-- SMS Config -->
                    <h5 class="mb-3 mt-4">SMS Provider (for OTP)</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Provider</label>
                            <input type="text" name="sms_config[provider]" class="form-control" 
                                value="{{ old('sms_config.provider', $settings->sms_config['provider'] ?? 'AfricasTalking') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Key</label>
                            <input type="password" name="sms_config[api_key]" class="form-control" 
                                value="{{ old('sms_config.api_key', $settings->sms_config['api_key'] ?? '') }}">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 4. Billing & Pricing -->
            <div class="tab-pane fade {{ request('tab') === 'billing' ? 'show active' : '' }}" id="billing">
                <h4 class="mb-3">Billing & Pricing</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="billing">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Fee per Worker</label>
                            <input type="number" step="0.01" name="platform_fee_per_worker" class="form-control" 
                                value="{{ old('platform_fee_per_worker', $settings->platform_fee_per_worker ?? 50) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Currency</label>
                            <input type="text" name="default_currency" class="form-control" 
                                value="{{ old('default_currency', $settings->default_currency ?? 'KES') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Billing Cadence</label>
                            <select name="billing_cadence" class="form-select" required>
                                <option value="weekly" {{ ($settings->billing_cadence ?? 'weekly') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ ($settings->billing_cadence ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Free Trial Workers</label>
                            <input type="number" name="free_trial_workers" class="form-control" min="0"
                                value="{{ old('free_trial_workers', $settings->free_trial_workers ?? 0) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Free Trial Weeks</label>
                            <input type="number" name="free_trial_weeks" class="form-control" min="0"
                                value="{{ old('free_trial_weeks', $settings->free_trial_weeks ?? 0) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fee Model</label>
                            <select name="fee_model" class="form-select" required>
                                <option value="flat" {{ ($settings->fee_model ?? 'flat') === 'flat' ? 'selected' : '' }}>Flat per Worker</option>
                                <option value="percentage" {{ ($settings->fee_model ?? '') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                <option value="hybrid" {{ ($settings->fee_model ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fee Percentage (if applicable)</label>
                            <input type="number" step="0.01" name="fee_percentage" class="form-control" min="0" max="100"
                                value="{{ old('fee_percentage', $settings->fee_percentage) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Late Fee Amount</label>
                            <input type="number" step="0.01" name="late_fee_amount" class="form-control" min="0"
                                value="{{ old('late_fee_amount', $settings->late_fee_amount ?? 0) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice Reminder Days (comma-separated)</label>
                            <input type="text" name="invoice_reminder_days" class="form-control" 
                                value="{{ old('invoice_reminder_days', $settings->invoice_reminder_days ?? '3,7,14') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Invoice Due Days</label>
                            <input type="number" name="default_invoice_due_days" class="form-control" min="1" max="90"
                                value="{{ old('default_invoice_due_days', $settings->default_invoice_due_days ?? 14) }}" required>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-outline-primary preview-invoice-btn">
                                <i class="bi bi-eye me-1"></i>Preview Invoice
                            </button>
                            <span class="preview-result ms-2"></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 5. Payroll & Payouts -->
            <div class="tab-pane fade {{ request('tab') === 'payroll' ? 'show active' : '' }}" id="payroll">
                <h4 class="mb-3">Payroll & Payout Rules</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="payroll">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Payout Window Start Day (1-7)</label>
                            <input type="number" name="payout_window_start_day" class="form-control" min="1" max="7"
                                value="{{ old('payout_window_start_day', $settings->payout_window_start_day ?? 1) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payout Window End Day (1-7)</label>
                            <input type="number" name="payout_window_end_day" class="form-control" min="1" max="7"
                                value="{{ old('payout_window_end_day', $settings->payout_window_end_day ?? 7) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payout Delay (days)</label>
                            <input type="number" name="payout_delay_days" class="form-control" min="0"
                                value="{{ old('payout_delay_days', $settings->payout_delay_days ?? 0) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Payout per Batch</label>
                            <input type="number" step="0.01" name="max_payout_per_batch" class="form-control" min="0"
                                value="{{ old('max_payout_per_batch', $settings->max_payout_per_batch) }}">
                            <small class="text-muted">Leave empty for no limit</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Retry Attempts</label>
                            <input type="number" name="payout_retry_attempts" class="form-control" min="1" max="10"
                                value="{{ old('payout_retry_attempts', $settings->payout_retry_attempts ?? 3) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Retry Backoff (minutes)</label>
                            <input type="number" name="payout_retry_backoff_minutes" class="form-control" min="5"
                                value="{{ old('payout_retry_backoff_minutes', $settings->payout_retry_backoff_minutes ?? 30) }}" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="escrow_enabled" value="1"
                                    {{ old('escrow_enabled', $settings->escrow_enabled ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">Enable Escrow for Disputed Payouts</label>
                            </div>
                            <small class="text-muted">Requires 2FA to enable</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Escrow Release Conditions</label>
                            <textarea name="escrow_release_conditions" class="form-control" rows="2">{{ old('escrow_release_conditions', $settings->escrow_release_conditions) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 6. Notifications -->
            <div class="tab-pane fade {{ request('tab') === 'notifications' ? 'show active' : '' }}" id="notifications">
                <h4 class="mb-3">Notifications & Message Templates</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="notifications">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Use variables: <code>@{{worker_name}}</code>, <code>@{{amount}}</code>, <code>@{{site_name}}</code>, <code>@{{transaction_ref}}</code>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="receipts_enabled" value="1"
                                    {{ old('receipts_enabled', $settings->receipts_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label">Enable Payment Receipts</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <h5 class="mt-3">SMS Templates</h5>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Payout Success</label>
                            <textarea name="sms_templates[payout_success]" class="form-control" rows="2">{{ old('sms_templates.payout_success', isset($settings->sms_templates['payout_success']) ? $settings->sms_templates['payout_success'] : '') }}</textarea>
                            <small class="text-muted">Use: @{{worker_name}}, @{{amount}}, @{{site_name}}, @{{transaction_ref}}</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">OTP Message</label>
                            <textarea name="sms_templates[otp]" class="form-control" rows="2">{{ old('sms_templates.otp', isset($settings->sms_templates['otp']) ? $settings->sms_templates['otp'] : '') }}</textarea>
                            <small class="text-muted">Use: @{{otp}}, @{{expiry}}</small>
                        </div>

                        <div class="col-12">
                            <h5 class="mt-3">Email Templates</h5>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Invoice Reminder Subject</label>
                            <input type="text" name="email_templates[invoice_reminder_subject]" class="form-control"
                                value="{{ old('email_templates.invoice_reminder_subject', isset($settings->email_templates['invoice_reminder_subject']) ? $settings->email_templates['invoice_reminder_subject'] : '') }}">
                            <small class="text-muted">Use: @{{invoice_number}}</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 7. Webhooks & API -->
            <div class="tab-pane fade {{ request('tab') === 'webhooks' ? 'show active' : '' }}" id="webhooks">
                <h4 class="mb-3">Webhooks & API</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="webhooks">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Webhook Callback URLs (one per line)</label>
                            <textarea name="webhook_urls" class="form-control" rows="3">{{ old('webhook_urls', is_array($settings->webhook_urls ?? null) ? implode("\n", $settings->webhook_urls) : '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Webhook Signing Key</label>
                            <input type="text" name="webhook_signing_key" class="form-control" 
                                value="{{ old('webhook_signing_key', $settings->webhook_signing_key) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Retries</label>
                            <input type="number" name="webhook_max_retries" class="form-control" min="1" max="10"
                                value="{{ old('webhook_max_retries', $settings->webhook_max_retries ?? 3) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Retry Backoff (seconds)</label>
                            <input type="number" name="webhook_retry_backoff_seconds" class="form-control" min="10"
                                value="{{ old('webhook_retry_backoff_seconds', $settings->webhook_retry_backoff_seconds ?? 60) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">API IP Whitelist (comma-separated)</label>
                            <input type="text" name="api_ip_whitelist" class="form-control" 
                                value="{{ old('api_ip_whitelist', is_array($settings->api_ip_whitelist ?? null) ? implode(',', $settings->api_ip_whitelist) : '') }}">
                            <small class="text-muted">Leave empty to allow all IPs</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 8. USSD & Shortcodes -->
            <div class="tab-pane fade {{ request('tab') === 'ussd' ? 'show active' : '' }}" id="ussd">
                <h4 class="mb-3">USSD & Shortcodes</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="ussd">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Primary Shortcode</label>
                            <input type="text" name="ussd_shortcodes[primary]" class="form-control" 
                                value="{{ old('ussd_shortcodes.primary', $settings->ussd_shortcodes['primary'] ?? '*384*96#') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session Timeout (seconds)</label>
                            <input type="number" name="ussd_session_timeout_seconds" class="form-control" min="30" max="180"
                                value="{{ old('ussd_session_timeout_seconds', $settings->ussd_session_timeout_seconds ?? 60) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Root Menu Text</label>
                            <textarea name="ussd_menu_config[root_menu]" class="form-control" rows="3">{{ old('ussd_menu_config.root_menu', $settings->ussd_menu_config['root_menu'] ?? "Welcome to Mjengo\n1. Check Balance\n2. Claim Attendance\n3. View Payouts") }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fallback Message</label>
                            <textarea name="ussd_menu_config[fallback]" class="form-control" rows="2">{{ old('ussd_menu_config.fallback', $settings->ussd_menu_config['fallback'] ?? 'Invalid option. Please try again.') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-outline-primary simulate-ussd-btn">
                                <i class="bi bi-play-circle me-1"></i>Simulate USSD Session
                            </button>
                            <span class="simulate-result ms-2"></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 9. Security -->
            <div class="tab-pane fade {{ request('tab') === 'security' ? 'show active' : '' }}" id="security">
                <h4 class="mb-3">Security & Secrets</h4>
                
                <div class="alert alert-danger">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    <strong>Critical:</strong> All changes require 2FA and are logged
                </div>

                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="security">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Key Rotation Period (days)</label>
                            <input type="number" name="key_rotation_days" class="form-control" min="30" max="365"
                                value="{{ old('key_rotation_days', $settings->key_rotation_days ?? 90) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Audit Log Retention (days)</label>
                            <input type="number" name="audit_retention_days" class="form-control" min="90" max="3650"
                                value="{{ old('audit_retention_days', $settings->audit_retention_days ?? 365) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">CORS Allowed Origins (one per line)</label>
                            <textarea name="cors_origins" class="form-control" rows="3">{{ old('cors_origins', is_array($settings->cors_origins ?? null) ? implode("\n", $settings->cors_origins) : '') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 10. Feature Flags -->
            <div class="tab-pane fade {{ request('tab') === 'features' ? 'show active' : '' }}" id="features">
                <h4 class="mb-3">Feature Flags & Pilots</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="features">

                    <div class="row g-3">
                        <div class="col-12">
                            <h5>Global Feature Flags</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="feature_flags[qr_checkin]" value="1"
                                    {{ old('feature_flags.qr_checkin', $settings->feature_flags['qr_checkin'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">QR Code Check-in</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="feature_flags[gps_photo]" value="1"
                                    {{ old('feature_flags.gps_photo', $settings->feature_flags['gps_photo'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">GPS Photo Verification</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="feature_flags[auto_approve_claims]" value="1"
                                    {{ old('feature_flags.auto_approve_claims', $settings->feature_flags['auto_approve_claims'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">Auto-approve USSD Claims</label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <h5>Pilot Sites</h5>
                            <label class="form-label">Select sites for pilot features</label>
                            <select name="pilot_sites[]" class="form-select" multiple size="5">
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" 
                                        {{ in_array($site->id, old('pilot_sites', $settings->pilot_sites ?? [])) ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 11. Data & Backups -->
            <div class="tab-pane fade {{ request('tab') === 'data' ? 'show active' : '' }}" id="data">
                <h4 class="mb-3">Data & Backups</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="data">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Backup Schedule</label>
                            <select name="backup_schedule" class="form-select" required>
                                <option value="daily" {{ ($settings->backup_schedule ?? 'daily') === 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ ($settings->backup_schedule ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Backup Retention (days)</label>
                            <input type="number" name="backup_retention_days" class="form-control" min="7" max="365"
                                value="{{ old('backup_retention_days', $settings->backup_retention_days ?? 30) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Backup Storage</label>
                            <input type="text" name="backup_storage" class="form-control" 
                                value="{{ old('backup_storage', $settings->backup_storage ?? 'local') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Archive Inactive Sites After (months)</label>
                            <input type="number" name="inactive_archive_months" class="form-control" min="3" max="60"
                                value="{{ old('inactive_archive_months', $settings->inactive_archive_months ?? 12) }}" required>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-outline-primary trigger-backup-btn">
                                <i class="bi bi-cloud-download me-1"></i>Trigger Manual Backup
                            </button>
                            <span class="backup-result ms-2"></span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 12. Legal / Compliance -->
            <div class="tab-pane fade {{ request('tab') === 'legal' ? 'show active' : '' }}" id="legal">
                <h4 class="mb-3">Legal / Compliance</h4>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="legal">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Terms & Conditions</label>
                            <textarea name="terms_content" class="form-control" rows="4">{{ old('terms_content', $settings->terms_content) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Privacy Policy</label>
                            <textarea name="privacy_content" class="form-control" rows="4">{{ old('privacy_content', $settings->privacy_content) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">KYC Threshold Amount</label>
                            <input type="number" step="0.01" name="kyc_threshold_amount" class="form-control" min="0"
                                value="{{ old('kyc_threshold_amount', $settings->kyc_threshold_amount ?? 100000) }}" required>
                            <small class="text-muted">Amount that triggers required KYC</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deletion Approvers Required</label>
                            <input type="number" name="deletion_approvers_required" class="form-control" min="1" max="5"
                                value="{{ old('deletion_approvers_required', $settings->deletion_approvers_required ?? 2) }}" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="deletion_requires_approval" value="1"
                                    {{ old('deletion_requires_approval', $settings->deletion_requires_approval ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label">User Deletion Requires Multi-Admin Approval</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- 15. Payment Accounts Management -->
            <div class="tab-pane fade {{ request('tab') === 'accounts' ? 'show active' : '' }}" id="accounts">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">Payment Accounts Management</h4>
                        <p class="text-muted mb-0">Configure platform M-Pesa accounts for deposits and invoice payments</p>
                    </div>
                </div>

                @if(session('account_success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>{{ session('account_success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row g-4">
                    <!-- Escrow Account -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="bi bi-safe me-2 text-primary"></i>Escrow Account
                                    </h5>
                                    <small class="text-muted">For worker deposit payments</small>
                                </div>
                                @if($escrowAccount)
                                    <span class="badge {{ ($escrowAccount->status ?? 'inactive') === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($escrowAccount->status) }}
                                    </span>
                                @endif
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.accounts.save') }}" class="account-form">
                                    @csrf
                                    <input type="hidden" name="account_type" value="deposit">
                                    @if($escrowAccount)
                                        <input type="hidden" name="account_id" value="{{ $escrowAccount->id }}">
                                    @endif

                                    <div class="mb-3">
                                        <label class="form-label">Account Name</label>
                                        <input type="text" name="name" class="form-control" 
                                            value="{{ $escrowAccount->name ?? 'Platform Escrow' }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">M-Pesa Shortcode</label>
                                        <input type="text" name="shortcode" class="form-control" 
                                            value="{{ $escrowAccount->shortcode ?? '' }}" required
                                            placeholder="e.g., 174379">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Consumer Key</label>
                                        <div class="input-group">
                                            <input type="password" name="consumer_key" class="form-control secret-input"
                                                value="{{ $escrowAccount->credentials['consumer_key'] ?? '' }}" required>
                                            <button type="button" class="btn btn-outline-secondary reveal-btn">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Consumer Secret</label>
                                        <div class="input-group">
                                            <input type="password" name="consumer_secret" class="form-control secret-input"
                                                value="{{ $escrowAccount->credentials['consumer_secret'] ?? '' }}" required>
                                            <button type="button" class="btn btn-outline-secondary reveal-btn">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Passkey</label>
                                        <div class="input-group">
                                            <input type="password" name="passkey" class="form-control secret-input"
                                                value="{{ $escrowAccount->credentials['passkey'] ?? '' }}" required>
                                            <button type="button" class="btn btn-outline-secondary reveal-btn">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    @if($escrowAccount)
                                        <div class="mb-3 p-2 bg-light rounded">
                                            <small class="text-muted">
                                                Last tested: {{ $escrowAccount->last_tested_at?->diffForHumans() ?? 'Never' }}
                                            </small>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="is_primary" class="form-check-input" 
                                                id="escrowPrimary" value="1"
                                                {{ $escrowAccount && $escrowAccount->is_primary ? 'checked' : '' }}>
                                            <label class="form-check-label" for="escrowPrimary">
                                                Set as Primary Account
                                                <small class="text-muted d-block">Use this account for all deposit payments by default</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-1"></i>Save Account
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary test-account-btn" data-account-type="escrow">
                                            <i class="bi bi-lightning me-1"></i>Test Connection
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Revenue Account -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="bi bi-cash-coin me-2 text-success"></i>Invoice Revenue Account
                                    </h5>
                                    <small class="text-muted">For invoice payment collection</small>
                                </div>
                                @if($revenueAccount)
                                    <span class="badge {{ ($revenueAccount->status ?? 'inactive') === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($revenueAccount->status) }}
                                    </span>
                                @endif
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.accounts.save') }}" class="account-form">
                                    @csrf
                                    <input type="hidden" name="account_type" value="invoice">
                                    @if($revenueAccount)
                                        <input type="hidden" name="account_id" value="{{ $revenueAccount->id }}">
                                    @endif

                                    <div class="mb-3">
                                        <label class="form-label">Account Name</label>
                                        <input type="text" name="name" class="form-control" 
                                            value="{{ $revenueAccount->name ?? 'Platform Revenue' }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">M-Pesa Shortcode</label>
                                        <input type="text" name="shortcode" class="form-control" 
                                            value="{{ $revenueAccount->shortcode ?? '' }}" required
                                            placeholder="e.g., 174379">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Consumer Key</label>
                                        <div class="input-group">
                                            <input type="password" name="consumer_key" class="form-control secret-input"
                                                value="{{ $revenueAccount->credentials['consumer_key'] ?? '' }}" required>
                                            <button type="button" class="btn btn-outline-secondary reveal-btn">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Consumer Secret</label>
                                        <div class="input-group">
                                            <input type="password" name="consumer_secret" class="form-control secret-input"
                                                value="{{ $revenueAccount->credentials['consumer_secret'] ?? '' }}" required>
                                            <button type="button" class="btn btn-outline-secondary reveal-btn">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Passkey</label>
                                        <div class="input-group">
                                            <input type="password" name="passkey" class="form-control secret-input"
                                                value="{{ $revenueAccount->credentials['passkey'] ?? '' }}" required>
                                            <button type="button" class="btn btn-outline-secondary reveal-btn">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    @if($revenueAccount)
                                        <div class="mb-3 p-2 bg-light rounded">
                                            <small class="text-muted">
                                                Last tested: {{ $revenueAccount->last_tested_at?->diffForHumans() ?? 'Never' }}
                                            </small>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="is_primary" class="form-check-input" 
                                                id="revenuePrimary" value="1"
                                                {{ $revenueAccount && $revenueAccount->is_primary ? 'checked' : '' }}>
                                            <label class="form-check-label" for="revenuePrimary">
                                                Set as Primary Account
                                                <small class="text-muted d-block">Use this account for all invoice payments by default</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-1"></i>Save Account
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary test-account-btn" data-account-type="revenue">
                                            <i class="bi bi-lightning me-1"></i>Test Connection
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>How it works:</strong>
                            <ul class="mb-0 mt-2">
                                <li>When workers deposit to their wallet via STK push → routes to <strong>Escrow Account</strong></li>
                                <li>When owners pay invoices via STK push → routes to <strong>Invoice Revenue Account</strong></li>
                                <li>Each account can use the same or different M-Pesa credentials</li>
                                <li>Test each account to verify credentials before going live</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('script')
<script>
// Reveal secret inputs
document.querySelectorAll('.reveal-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            this.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
            input.type = 'password';
            this.innerHTML = '<i class="bi bi-eye"></i>';
        }
    });
});

// Test Payment Account - Save then Test Connection
document.querySelectorAll('.test-account-btn').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        const form = this.closest('form');
        const originalHTML = this.innerHTML;
        this.disabled = true;
        
        try {
            // Step 1: Save the account
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
            
            const formData = new FormData(form);
            const saveResponse = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('[name="_token"]').value}
            });
            
            if (!saveResponse.ok) {
                throw new Error('Failed to save account');
            }
            
            // Step 2: Get account ID 
            let accountId = form.querySelector('[name="account_id"]')?.value;
            
            if (!accountId) {
                showToast('Account saved! Reloading page...', 'info');
                setTimeout(() => { window.location.reload(); }, 1500);
                return;
            }
            
            // Step 3: Test the connection
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing...';
            
            const testUrl = '{{ route("admin.accounts.test", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', accountId);
            const testResponse = await fetch(testUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            // Check if response is JSON before parsing
            const contentType = testResponse.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const errorText = await testResponse.text();
                throw new Error(`Server returned ${testResponse.status}: ${errorText.substring(0, 100)}...`);
            }
            
            const testResult = await testResponse.json();
            
            if (testResult.success) {
                showToast(`✓ Account verified! (Sandbox)<br>Shortcode: ${testResult.shortcode}<br>Token expires: ${testResult.token_expires}s`, 'success');
            } else {
                showToast(`✗ Test failed:<br>${testResult.message}<br>${testResult.hint || ''}`, 'danger');
            }
        } catch (error) {
            console.error('Account test error:', error);
            showToast('Error testing account: ' + error.message, 'danger');
        } finally {
            this.disabled = false;
            this.innerHTML = originalHTML;
        }
    });
});

// Toast notification helper
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Test MPesa connection
document.querySelector('.test-mpesa-btn')?.addEventListener('click', async function() {
    const btn = this;
    const resultSpan = document.querySelector('.test-result');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing...';
    
    try {
        const response = await fetch('{{ route("admin.settings.test-mpesa") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                mpesa_config: {
                    consumer_key: document.querySelector('input[name="mpesa_config[consumer_key]"]').value,
                    consumer_secret: document.querySelector('input[name="mpesa_config[consumer_secret]"]').value
                }
            })
        });
        
        const data = await response.json();
        resultSpan.innerHTML = data.success 
            ? '<span class="badge bg-success">✓ Connected</span>'
            : '<span class="badge bg-danger">✗ Failed: ' + data.message + '</span>';
    } catch (error) {
        resultSpan.innerHTML = '<span class="badge bg-danger">✗ Error</span>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-circle me-1"></i>Test Connection';
    }
});

// Preview invoice
document.querySelector('.preview-invoice-btn')?.addEventListener('click', async function() {
    const btn = this;
    const resultSpan = document.querySelector('.preview-result');
    btn.disabled = true;
    
    try {
        const response = await fetch('{{ route("admin.settings.preview-invoice") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                workers: 10
            })
        });
        
        const data = await response.json();
        resultSpan.innerHTML = `<span class="badge bg-info">Sample: ${data.workers} workers = ${data.currency} ${data.total}</span>`;
    } catch (error) {
        resultSpan.innerHTML = '<span class="badge bg-danger">✗ Error</span>';
    } finally {
        btn.disabled = false;
    }
});

// Trigger backup
document.querySelector('.trigger-backup-btn')?.addEventListener('click', async function() {
    if (!confirm('Trigger manual backup now?')) return;
    
    const btn = this;
    const resultSpan = document.querySelector('.backup-result');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Backing up...';
    
    try {
        const response = await fetch('{{ route("admin.settings.trigger-backup") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        const data = await response.json();
        resultSpan.innerHTML = data.success 
            ? '<span class="badge bg-success">✓ Backup initiated: ' + data.filename + '</span>'
            : '<span class="badge bg-danger">✗ Failed</span>';
    } catch (error) {
        resultSpan.innerHTML = '<span class="badge bg-danger">✗ Error</span>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-download me-1"></i>Trigger Manual Backup';
    }
});

// Simulate USSD
document.querySelector('.simulate-ussd-btn')?.addEventListener('click', async function() {
    const btn = this;
    const resultSpan = document.querySelector('.simulate-result');
    btn.disabled = true;
    
    try {
        const response = await fetch('{{ route("admin.settings.simulate-ussd") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        const data = await response.json();
        resultSpan.innerHTML = `<span class="badge bg-info">Session: ${data.session_id} (${data.timeout}s timeout)</span>`;
    } catch (error) {
        resultSpan.innerHTML = '<span class="badge bg-danger">✗ Error</span>';
    } finally {
        btn.disabled = false;
    }
});
</script>
@endsection
