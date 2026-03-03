@extends('owner.layouts.app')

@section('title', 'Site Settings')
@section('page-title', 'Site Settings · ' . $site->name)

@section('content')
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <strong>Validation Errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('owner.sites.detail', $site) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Site Detail
    </a>
    <span class="badge text-bg-light border">Settings Panel</span>
</div>

<div class="card kpi-card">
    <div class="card-header bg-white border-0 pb-0">
        <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#payouts" type="button">Payouts</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#communications" type="button">Communications</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#features" type="button">Features</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#access" type="button">Access & Roles</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#notifications" type="button">Notifications</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#audit" type="button">Audit & History</button></li>
        </ul>
    </div>

    <div class="card-body tab-content pt-4">
        <div class="tab-pane fade show active" id="payouts">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="mb-0">Payout Configuration</h6>
                <span class="badge {{ ($payoutSettings['inherited'] ?? true) ? 'text-bg-warning' : 'text-bg-success' }}">{{ ($payoutSettings['inherited'] ?? true) ? 'Inherited from Platform' : 'Overridden' }}</span>
            </div>

            <form method="POST" action="{{ route('owner.sites.settings.payouts.update', $site) }}" class="row g-3" id="payoutSettingsForm">
                @csrf
                @method('PUT')

                <div class="col-md-4">
                    <label class="form-label">Payout Account</label>
                    <select class="form-select" name="account_type" id="accountType">
                        <option value="platform" {{ $payoutAccount->account_type === 'platform' ? 'selected' : '' }}>Platform</option>
                        <option value="owner" {{ $payoutAccount->account_type === 'owner' ? 'selected' : '' }}>Owner</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Status</label>
                    <div><span class="badge text-bg-light border">{{ strtoupper($payoutAccount->status) }}</span></div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="testPayoutBtn">Test payout to sandbox (mocked)</button>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Auto-payout (Schedule)</label>
                    <select class="form-select" name="auto_payout">
                        <option value="0" {{ !($payoutSettings['auto_payout'] ?? false) ? 'selected' : '' }}>OFF</option>
                        <option value="1" {{ ($payoutSettings['auto_payout'] ?? false) ? 'selected' : '' }}>ON - Auto-disburse approved payouts</option>
                    </select>
                    <small class="text-muted">When ON, approved payouts auto-disburse on schedule</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Claims Approval Workflow</label>
                    <select class="form-select" name="auto_approve_claims">
                        <option value="0" {{ !($payoutSettings['auto_approve_claims'] ?? false) ? 'selected' : '' }}>Normal - Worker → Foreman → Owner</option>
                        <option value="1" {{ (($payoutSettings['auto_approve_claims'] ?? false) && !($payoutSettings['fully_automated'] ?? false)) ? 'selected' : '' }}>Skip Foreman - Worker → Owner</option>
                        <option value="2" {{ ($payoutSettings['fully_automated'] ?? false) ? 'selected' : '' }}>Fully Automated - No approvals needed</option>
                    </select>
                    <small class="text-muted d-block mt-1">
                        <strong>Normal:</strong> Foreman & Owner both approve<br>
                        <strong>Skip Foreman:</strong> Goes directly to Owner<br>
                        <strong>Fully Automated:</strong> Auto-approved & immediately disbursed
                    </small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Enforce Windows</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" value="1" name="enforce_windows" id="enforceWindows" {{ ($payoutSettings['enforce_windows'] ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="enforceWindows">
                            Block withdrawals outside schedule
                        </label>
                    </div>
                    <small class="text-muted d-block mt-2">When OFF: workers can withdraw anytime. When ON: only on configured days/times (except owner override)</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Window Days</label>
                    @php $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; $selectedDays = $payoutSettings['windows'][0]['days'] ?? ['Fri']; @endphp
                    <select class="form-select" name="window_days[]" multiple size="4">
                        @foreach($days as $day)
                            <option value="{{ $day }}" {{ in_array($day, $selectedDays) ? 'selected' : '' }}>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Window Time</label>
                    <input type="time" class="form-control" name="window_time" value="{{ $payoutSettings['windows'][0]['time'] ?? '17:00' }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Timezone</label>
                    <input type="text" class="form-control" name="window_timezone" value="{{ $payoutSettings['windows'][0]['timezone'] ?? 'Africa/Nairobi' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min Balance Guard</label>
                    <input type="number" step="0.01" class="form-control" name="min_balance_guard" value="{{ $payoutSettings['min_balance_guard'] ?? 0 }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Batch Limit</label>
                    <input type="number" step="0.01" class="form-control" name="max_batch_limit" value="{{ $payoutSettings['max_batch_limit'] ?? 500000 }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min Days Between Withdrawals</label>
                    <input type="number" min="0" max="30" class="form-control" name="min_days_between_withdrawals" value="{{ $payoutSettings['min_days_between_withdrawals'] ?? 0 }}">
                    <small class="text-muted">Set 0 for no minimum (daily allowed). Set 7 for weekly intervals.</small>
                </div>

                <div class="col-12 border rounded p-3 bg-light" id="ownerCredentialsBlock" style="display: {{ $payoutAccount->account_type === 'owner' ? 'block' : 'none' }};">
                    <h6 class="mb-2">Owner Payout Credentials</h6>
                    @php
                        $payoutCredentials = is_array($payoutAccount->credentials ?? null) ? $payoutAccount->credentials : [];
                    @endphp
                    <div class="row g-2">
                        <div class="col-md-4"><input class="form-control" name="provider" placeholder="Provider" value="{{ $payoutAccount->provider ?? 'mpesa' }}"></div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <input class="form-control" type="password" name="shortcode" id="shortcodeField" placeholder="Shortcode" value="{{ $payoutCredentials['shortcode'] ?? '' }}" autocomplete="off">
                                <button class="btn btn-outline-secondary credential-toggle" type="button" data-target="shortcodeField">Show</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <input class="form-control" type="password" name="consumer_key" id="consumerKeyField" placeholder="Consumer key" value="{{ $payoutCredentials['consumer_key'] ?? '' }}" autocomplete="off">
                                <button class="btn btn-outline-secondary credential-toggle" type="button" data-target="consumerKeyField">Show</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input class="form-control" type="password" name="consumer_secret" id="consumerSecretField" placeholder="Consumer secret" value="{{ $payoutCredentials['consumer_secret'] ?? '' }}" autocomplete="off">
                                <button class="btn btn-outline-secondary credential-toggle" type="button" data-target="consumerSecretField">Show</button>
                            </div>
                        </div>
                        <div class="col-md-6"><small class="text-muted">Credentials are hidden by default. Use Show only when needed.</small></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Reason (optional)</label>
                    <input class="form-control" name="reason" placeholder="Reason for settings update">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="confirm_2fa" id="confirm2fa">
                        <label class="form-check-label" for="confirm2fa">I confirm owner 2FA/confirmation for sensitive change</label>
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Save Payout Settings</button>
                </div>
            </form>
        </div>

        <div class="tab-pane fade" id="communications">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="mb-0">Communications</h6>
                <span class="badge {{ ($communications['inherited'] ?? true) ? 'text-bg-warning' : 'text-bg-success' }}">{{ ($communications['inherited'] ?? true) ? 'Inherited from Platform' : 'Overridden' }}</span>
            </div>

            <form method="POST" action="{{ route('owner.sites.settings.communications.update', $site) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-4"><label class="form-label">SMS Sender</label><input class="form-control" name="sms_sender" value="{{ $communications['sms_sender'] ?? 'SITEGRID' }}"></div>
                <div class="col-md-4"><label class="form-label">Provider Override</label><input class="form-control" name="sms_provider_override" value="{{ $communications['sms_provider_override'] ?? '' }}"></div>
                <div class="col-md-4"><label class="form-label">Daily Send Limit</label><input class="form-control" type="number" name="daily_send_limit" value="{{ $communications['daily_send_limit'] ?? 100 }}"></div>
                <div class="col-md-6"><label class="form-label">USSD Root Menu Override</label><input class="form-control" name="ussd_root_menu_override" value="{{ $communications['ussd_root_menu_override'] ?? '' }}"></div>
                <div class="col-md-3"><label class="form-label">USSD Timeout (sec)</label><input class="form-control" type="number" name="ussd_session_timeout_seconds" value="{{ $communications['ussd_session_timeout_seconds'] ?? 120 }}"></div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="fallback_sms_for_long_messages" {{ ($communications['fallback_sms_for_long_messages'] ?? false) ? 'checked' : '' }}><label class="form-check-label">Fallback SMS</label></div>
                </div>

                @php
                    $templateMap = [];
                    foreach ($templates as $item) {
                        if ($item->channel === 'sms') {
                            $templateMap[$item->name] = $item->body;
                        }
                    }
                @endphp

                <div class="col-12"><h6 class="mb-2">SMS Templates</h6></div>
                @foreach(['payout_confirmation','claim_received','claim_approved','claim_rejected','attendance_reminder','low_wallet_balance','payout_failed'] as $templateName)
                    <div class="col-md-6">
                        <label class="form-label">{{ str_replace('_', ' ', ucfirst($templateName)) }}</label>
                        <textarea class="form-control template-body" rows="4" name="templates[{{ $templateName }}]">{{ $templateMap[$templateName] ?? 'Hello &#123;&#123;worker_name&#125;&#125;, update for &#123;&#123;site_name&#125;&#125;: &#123;&#123;amount&#125;&#125;. Ref: &#123;&#123;transaction_ref&#125;&#125;' }}</textarea>
                        <small class="text-muted d-block mt-1">Supports variables: {{ implode(', ', $templateVariables) }}</small>
                        <small class="text-muted">Chars: <span class="char-count">0</span> · Parts: <span class="sms-parts">0</span></small>
                    </div>
                @endforeach

                <div class="col-md-6">
                    <label class="form-label">Reason (optional)</label>
                    <input class="form-control" name="reason" placeholder="Why templates/settings changed">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Send Test SMS</label>
                    <div class="input-group">
                        <input class="form-control" id="testSmsPhone" value="{{ auth()->user()->phone }}" placeholder="Phone">
                        <button class="btn btn-outline-primary" type="button" id="sendTestSmsBtn">Send Test SMS</button>
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Save Communications</button>
                    <button type="button" class="btn btn-outline-secondary" id="previewTemplateBtn">Preview sample template</button>
                </div>
            </form>

            <div class="mt-3 p-3 border rounded bg-light">
                <div class="fw-semibold mb-1">Preview Output</div>
                <div id="templatePreviewOutput" class="small text-muted">Click preview to render sample substitutions.</div>
            </div>
        </div>

        <div class="tab-pane fade" id="features">
            <h6 class="mb-3">Feature Toggles</h6>
            <form method="POST" action="{{ route('owner.sites.settings.features.update', $site) }}" class="row g-3">
                @csrf
                @method('PUT')
                @foreach($featureFlags as $flagName => $flag)
                    <div class="col-md-6 border rounded p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ $flagName }}</strong>
                            <span class="badge {{ ($flag['inherited'] ?? false) ? 'text-bg-warning' : 'text-bg-success' }}">{{ ($flag['inherited'] ?? false) ? 'Inherited' : 'Overridden' }}</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Enabled</label>
                                <select class="form-select form-select-sm" name="features[{{ $flagName }}][value]">
                                    <option value="1" {{ ($flag['value'] ?? false) ? 'selected' : '' }}>ON</option>
                                    <option value="0" {{ !($flag['value'] ?? false) ? 'selected' : '' }}>OFF</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Rollout %</label>
                                <input class="form-control form-control-sm" type="number" min="1" max="100" name="features[{{ $flagName }}][rollout_percent]" value="{{ $flag['rollout_percent'] ?? 100 }}">
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-md-8"><input class="form-control" name="reason" placeholder="Reason for toggle changes"></div>
                <div class="col-md-4"><button class="btn btn-primary w-100">Save Feature Flags</button></div>
            </form>
        </div>

        <div class="tab-pane fade" id="access">
            <div class="row g-3">
                <div class="col-lg-5">
                    <h6 class="mb-3">Invite Site Member</h6>
                    <form method="POST" action="{{ route('owner.sites.settings.invitations.store', $site) }}" class="border rounded p-3">
                        @csrf
                        <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone" placeholder="+2547..."></div>
                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                @foreach(['owner_admin','attendance_approver','payroll_clerk','viewer','foreman','worker'] as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Expires in days</label><input class="form-control" type="number" min="1" max="30" name="expires_in_days" value="7"></div>
                        <div class="mb-3"><label class="form-label">Reason (optional)</label><input class="form-control" name="reason"></div>
                        <button class="btn btn-primary w-100">Create Invite (Mock SMS)</button>
                    </form>
                </div>

                <div class="col-lg-7">
                    <h6 class="mb-3">Site Members</h6>
                    <div class="table-responsive border rounded mb-3">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Name</th><th>Phone</th><th>Role</th><th>Assigned</th></tr></thead>
                            <tbody>
                                @forelse($members as $member)
                                    <tr>
                                        <td>{{ $member->user->name ?? 'Unknown' }}</td>
                                        <td>{{ $member->user->phone ?? '—' }}</td>
                                        <td><span class="badge text-bg-light border">{{ $member->role }}</span></td>
                                        <td>{{ $member->assigned_at?->format('d M Y H:i') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No site members yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2">Recent Invitations</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Phone</th><th>Role</th><th>Status</th><th>Expires</th><th>Created</th></tr></thead>
                            <tbody>
                                @forelse($invitations as $invite)
                                    <tr>
                                        <td>{{ $invite->phone }}</td>
                                        <td>{{ $invite->role }}</td>
                                        <td>
                                            @if($invite->used_at)
                                                <span class="badge text-bg-success">Used</span>
                                            @elseif($invite->isExpired())
                                                <span class="badge text-bg-danger">Expired</span>
                                            @else
                                                <span class="badge text-bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $invite->expires_at?->format('d M Y H:i') }}</td>
                                        <td>{{ $invite->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No invites yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="notifications">
            <h6 class="mb-3">Notification Triggers & Channels</h6>
            <div class="mb-2"><span class="badge {{ ($notifications['inherited'] ?? true) ? 'text-bg-warning' : 'text-bg-success' }}">{{ ($notifications['inherited'] ?? true) ? 'Inherited from Platform' : 'Overridden' }}</span></div>
            <form method="POST" action="{{ route('owner.sites.settings.notifications.update', $site) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="claim_created" {{ ($notifications['claim_created'] ?? false) ? 'checked' : '' }}><label class="form-check-label">Claim Created</label></div></div>
                <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="payout_failed" {{ ($notifications['payout_failed'] ?? false) ? 'checked' : '' }}><label class="form-check-label">Payout Failed</label></div></div>
                <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="low_wallet_balance" {{ ($notifications['low_wallet_balance'] ?? false) ? 'checked' : '' }}><label class="form-check-label">Low Wallet Balance</label></div></div>

                @php $channels = $notifications['channels'] ?? ['sms']; @endphp
                <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" value="sms" name="channels[]" {{ in_array('sms',$channels) ? 'checked' : '' }}><label class="form-check-label">SMS</label></div></div>
                <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" value="email" name="channels[]" {{ in_array('email',$channels) ? 'checked' : '' }}><label class="form-check-label">Email</label></div></div>

                <div class="col-md-8"><input class="form-control" name="reason" placeholder="Reason for notification changes"></div>
                <div class="col-md-4"><button class="btn btn-primary w-100">Save Notifications</button></div>
            </form>
        </div>

        <div class="tab-pane fade" id="audit">
            <h6 class="mb-3">Settings Change History</h6>
            <div class="table-responsive border rounded">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Changed By</th>
                            <th>Reason</th>
                            <th>Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            <tr>
                                <td>{{ $audit->created_at->format('d M Y H:i') }}</td>
                                <td><span class="badge text-bg-light border">{{ $audit->action }}</span></td>
                                <td>{{ $audit->changedBy->name ?? 'Unknown' }}</td>
                                <td>{{ $audit->reason ?: '—' }}</td>
                                <td><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#change{{ $audit->id }}">View</button></td>
                            </tr>
                            <tr class="collapse" id="change{{ $audit->id }}">
                                <td colspan="5"><pre class="small mb-0">{{ json_encode($audit->change, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No settings history yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const accountTypeEl = document.getElementById('accountType');
    const ownerCredentialsBlockEl = document.getElementById('ownerCredentialsBlock');

    if (accountTypeEl && ownerCredentialsBlockEl) {
        accountTypeEl.addEventListener('change', () => {
            ownerCredentialsBlockEl.style.display = accountTypeEl.value === 'owner' ? 'block' : 'none';
        });
    }

    const credentialToggleBtns = document.querySelectorAll('.credential-toggle');
    credentialToggleBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.textContent = isHidden ? 'Hide' : 'Show';
        });
    });

    // Handle test payout button
    const testPayoutBtn = document.getElementById('testPayoutBtn');
    if (testPayoutBtn) {
        testPayoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('owner.sites.settings.payouts.test', $site) }}";
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(form);
            form.submit();
        });
    }

    const templateTextareas = document.querySelectorAll('.template-body');
    templateTextareas.forEach((textarea) => {
        const countEl = textarea.parentElement.querySelector('.char-count');
        const partsEl = textarea.parentElement.querySelector('.sms-parts');
        const updateMetrics = () => {
            const len = textarea.value.length;
            const parts = Math.max(1, Math.ceil(len / 160));
            countEl.textContent = len;
            partsEl.textContent = parts;
            partsEl.classList.toggle('text-danger', parts > 1);
        };
        textarea.addEventListener('input', updateMetrics);
        updateMetrics();
    });

    const previewBtn = document.getElementById('previewTemplateBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', async () => {
            const body = document.querySelector('.template-body')?.value || '';
            const response = await fetch("{{ route('owner.sites.settings.communications.preview', $site) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ body })
            });
            const data = await response.json();
            const target = document.getElementById('templatePreviewOutput');
            target.innerHTML = `${data.preview}<br><small>Length: ${data.length}, Parts: ${data.parts}</small>${data.multipart_warning ? '<br><span class="text-danger">Multipart SMS warning (higher cost)</span>' : ''}`;
        });
    }

    const testSmsBtn = document.getElementById('sendTestSmsBtn');
    if (testSmsBtn) {
        testSmsBtn.addEventListener('click', async () => {
            const phone = document.getElementById('testSmsPhone').value;
            const message = document.querySelector('.template-body')?.value || 'Test message';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('owner.sites.settings.communications.test-sms', $site) }}";
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="phone" value="${phone}">
                <input type="hidden" name="message" value="${message.replace(/"/g, '&quot;')}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }
</script>
@endsection
