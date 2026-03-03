<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Site;
use App\Models\SiteFeatureFlag;
use App\Models\SiteMember;
use App\Models\SitePayoutAccount;
use App\Models\SiteSetting;
use App\Models\SiteSettingsAudit;
use App\Models\SiteTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SiteSettingsController extends Controller
{
    private array $defaultFeatures = [
        'allow_ussd_claims' => ['value' => true, 'rollout_percent' => 100, 'inherited' => true],
        'qr_checkin_enabled' => ['value' => false, 'rollout_percent' => 100, 'inherited' => true],
        'photo_evidence_required' => ['value' => false, 'rollout_percent' => 100, 'inherited' => true],
        'gps_verify_checkin' => ['value' => false, 'rollout_percent' => 100, 'inherited' => true],
        'auto_approve_small_claims' => ['value' => false, 'rollout_percent' => 100, 'inherited' => true],
    ];

    private array $templateVariables = [
        '{{worker_name}}',
        '{{site_name}}',
        '{{amount}}',
        '{{paycycle_start}}',
        '{{paycycle_end}}',
        '{{transaction_ref}}',
        '{{claim_id}}',
    ];

    public function edit(Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $settingsByKey = SiteSetting::where('site_id', $site->id)->get()->keyBy('key');

        $payoutSettings = $settingsByKey->get('payouts')?->value ?? [
            'auto_payout' => false,
            'auto_approve_claims' => false,
                        'fully_automated' => false,
            'windows' => [['days' => ['Friday'], 'time' => '17:00', 'timezone' => 'Africa/Nairobi']],
            'min_balance_guard' => 0,
            'max_batch_limit' => 500000,
            'inherited' => true,
        ];

        $communications = $settingsByKey->get('communications')?->value ?? [
            'sms_sender' => 'SITEGRID',
            'sms_provider_override' => null,
            'daily_send_limit' => 100,
            'ussd_root_menu_override' => null,
            'ussd_session_timeout_seconds' => 120,
            'fallback_sms_for_long_messages' => true,
            'inherited' => true,
        ];

        $notifications = $settingsByKey->get('notifications')?->value ?? [
            'claim_created' => true,
            'payout_failed' => true,
            'low_wallet_balance' => true,
            'channels' => ['sms'],
            'inherited' => true,
        ];

        $payoutAccount = SitePayoutAccount::firstOrCreate(
            ['site_id' => $site->id],
            ['account_type' => 'platform', 'status' => 'pending', 'created_by' => auth()->id()]
        );

        $featureFlags = collect($this->defaultFeatures);
        $siteFlags = SiteFeatureFlag::where('site_id', $site->id)->get();
        foreach ($siteFlags as $flag) {
            $featureFlags[$flag->flag_name] = [
                'value' => (bool) $flag->value,
                'rollout_percent' => (int) $flag->rollout_percent,
                'inherited' => false,
            ];
        }

        $templates = SiteTemplate::where('site_id', $site->id)
            ->orderByDesc('version')
            ->get()
            ->groupBy(fn($row) => $row->channel . ':' . $row->name)
            ->map(fn($group) => $group->first());

        $invitations = Invitation::with('createdBy', 'usedBy')
            ->where('site_id', $site->id)
            ->latest()
            ->limit(15)
            ->get();

        $members = SiteMember::with('user', 'assignedBy')
            ->where('site_id', $site->id)
            ->latest()
            ->get();

        $audits = SiteSettingsAudit::with('changedBy')
            ->where('site_id', $site->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('owner.sites.settings', compact(
            'site',
            'payoutSettings',
            'communications',
            'notifications',
            'payoutAccount',
            'featureFlags',
            'templates',
            'invitations',
            'members',
            'audits'
        ))->with('templateVariables', $this->templateVariables);
    }

    public function updatePayouts(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'account_type' => 'required|in:platform,owner',
            'auto_payout' => 'nullable|boolean',
            'auto_approve_claims' => 'required|in:0,1,2',
            'confirm_2fa' => 'nullable|accepted',
            'enforce_windows' => 'nullable|boolean',
            'window_days' => 'required|array|min:1',
            'window_days.*' => 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
            'window_time' => 'required|date_format:H:i',
            'window_timezone' => 'required|string|max:80',
            'min_days_between_withdrawals' => 'required|numeric|min:0|max:30',
            'min_balance_guard' => 'required|numeric|min:0',
            'max_batch_limit' => 'required|numeric|min:0',
            'provider' => 'nullable|string|max:50',
            'shortcode' => 'nullable|string|max:60',
            'consumer_key' => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldPayoutAccount = SitePayoutAccount::where('site_id', $site->id)->first();

        $payoutSettings = [
            'auto_payout' => (bool)($validated['auto_payout'] ?? false),
            'auto_approve_claims' => (int)($validated['auto_approve_claims'] ?? 0) > 0,
            'fully_automated' => (int)($validated['auto_approve_claims'] ?? 0) === 2,
            'enforce_windows' => (bool)($validated['enforce_windows'] ?? true),
            'min_days_between_withdrawals' => (int)($validated['min_days_between_withdrawals'] ?? 0),
            'windows' => [[
                'days' => $validated['window_days'],
                'time' => $validated['window_time'],
                'timezone' => $validated['window_timezone'],
            ]],
            'min_balance_guard' => (float) $validated['min_balance_guard'],
            'max_batch_limit' => (float) $validated['max_batch_limit'],
            'inherited' => false,
        ];

        SiteSetting::updateOrCreate(
            ['site_id' => $site->id, 'key' => 'payouts'],
            ['value' => $payoutSettings, 'overridden_by' => auth()->id(), 'overridden_at' => now()]
        );

        $credentials = [];  // Default to empty array for platform accounts
        if ($validated['account_type'] === 'owner') {
            $credentials = [
                'shortcode' => $validated['shortcode'] ?? null,
                'consumer_key' => $validated['consumer_key'] ?? null,
                'consumer_secret' => $validated['consumer_secret'] ?? null,
            ];
        }

        $payoutAccount = SitePayoutAccount::updateOrCreate(
            ['site_id' => $site->id],
            [
                'account_type' => $validated['account_type'],
                'provider' => $validated['provider'] ?? ($validated['account_type'] === 'owner' ? 'mpesa' : null),
                'credentials' => $credentials,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]
        );

        // Map settings to sites table columns for backward compatibility
        $dayMap = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 
                   'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];
        
        $windowDays = $validated['window_days'];
        $firstDay = $dayMap[$windowDays[0]] ?? 'Saturday';
        $lastDay = $dayMap[end($windowDays)] ?? 'Sunday';
        
        // Parse window_time (HH:MM) and create time range (same time for open/close since we use specific days now)
        $windowTime = $validated['window_time'] . ':00'; // Convert "17:00" to "17:00:00"
        $closeTime = \Carbon\Carbon::parse($validated['window_time'])->addHour()->format('H:i:s'); // Add 1 hour
        
        $site->update([
            'payout_method' => $validated['account_type'] === 'platform' ? 'platform_managed' : 'owner_managed',
            'owner_mpesa_account' => $validated['shortcode'] ?? $site->owner_mpesa_account,
            'payout_window_start' => $firstDay,
            'payout_window_end' => $lastDay,
            'payout_opens_at' => $windowTime,
            'payout_closes_at' => $closeTime,
        ]);

        $this->logSettingsAudit(
            $site->id,
            'settings.payouts.updated',
            [
                'old' => [
                    'payout_account' => $oldPayoutAccount ? [
                        'account_type' => $oldPayoutAccount->account_type,
                        'provider' => $oldPayoutAccount->provider,
                        'status' => $oldPayoutAccount->status,
                    ] : null,
                ],
                'new' => [
                    'payout_account' => [
                        'account_type' => $payoutAccount->account_type,
                        'provider' => $payoutAccount->provider,
                        'status' => $payoutAccount->status,
                    ],
                    'payouts' => $payoutSettings,
                ],
            ],
            $validated['reason'] ?? null
        );

        return back()->with('success', 'Payout settings updated successfully.');
    }

    public function testPayoutAccount(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $payoutAccount = SitePayoutAccount::where('site_id', $site->id)->firstOrFail();
        $mpesaService = new \App\Services\MpesaService();

        $forceFail = $request->boolean('force_fail');
        $isSuccess = false;
        $validationMessage = '';

        if ($payoutAccount->account_type === 'platform') {
            // Platform account: just verify basic config
            $isSuccess = true;
            $validationMessage = 'Platform account (no credential test needed).';
        } else {
            // Owner account: validate M-Pesa credentials against Daraja API
            $consumerKey = $payoutAccount->credentials['consumer_key'] ?? null;
            $consumerSecret = $payoutAccount->credentials['consumer_secret'] ?? null;

            if (empty($consumerKey) || empty($consumerSecret)) {
                $isSuccess = false;
                $validationMessage = 'Consumer key or secret is missing.';
            } else {
                // Call real M-Pesa credential validation
                $validationResult = $mpesaService->validateCredentials($consumerKey, $consumerSecret);
                $isSuccess = $validationResult['valid'];
                $validationMessage = $validationResult['message'];
            }
        }

        // Override with force_fail if requested
        if ($forceFail) {
            $isSuccess = false;
            $validationMessage = 'Test forced to fail for demonstration.';
        }

        $payoutAccount->status = $isSuccess ? 'valid' : 'invalid';
        $payoutAccount->last_tested_at = now();
        $payoutAccount->save();

        // If owner account test fails, fallback to platform
        if (!$isSuccess && $payoutAccount->account_type === 'owner') {
            $payoutAccount->update(['account_type' => 'platform', 'provider' => null, 'credentials' => null]);
            $site->update(['payout_method' => 'platform_managed']);
        }

        $this->logSettingsAudit(
            $site->id,
            'settings.payout_account.tested',
            [
                'old' => null,
                'new' => [
                    'result' => $isSuccess ? 'success' : 'failed',
                    'status' => $payoutAccount->status,
                    'message' => $validationMessage,
                    'tested_at' => $payoutAccount->last_tested_at?->toIso8601String(),
                    'credentials_validated' => $payoutAccount->account_type === 'owner',
                ],
            ]
        );

        return back()->with($isSuccess ? 'success' : 'error', $isSuccess
            ? 'Payout account test succeeded. Status: ' . ucfirst($payoutAccount->status)
            : 'Payout account test failed: ' . $validationMessage . ' Fallback to platform mode applied.');
    }

    public function updateCommunications(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'sms_sender' => 'required|string|max:20',
            'sms_provider_override' => 'nullable|string|max:60',
            'daily_send_limit' => 'required|integer|min:1|max:10000',
            'ussd_root_menu_override' => 'nullable|string|max:255',
            'ussd_session_timeout_seconds' => 'required|integer|min:30|max:600',
            'fallback_sms_for_long_messages' => 'nullable|boolean',
            'templates' => 'required|array',
            'templates.*' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $communicationSettings = [
            'sms_sender' => $validated['sms_sender'],
            'sms_provider_override' => $validated['sms_provider_override'],
            'daily_send_limit' => $validated['daily_send_limit'],
            'ussd_root_menu_override' => $validated['ussd_root_menu_override'],
            'ussd_session_timeout_seconds' => $validated['ussd_session_timeout_seconds'],
            'fallback_sms_for_long_messages' => (bool) ($validated['fallback_sms_for_long_messages'] ?? false),
            'inherited' => false,
        ];

        SiteSetting::updateOrCreate(
            ['site_id' => $site->id, 'key' => 'communications'],
            ['value' => $communicationSettings, 'overridden_by' => auth()->id(), 'overridden_at' => now()]
        );

        foreach ($validated['templates'] as $name => $body) {
            $latestVersion = (int) SiteTemplate::where('site_id', $site->id)
                ->where('channel', 'sms')
                ->where('name', $name)
                ->max('version');

            SiteTemplate::create([
                'site_id' => $site->id,
                'channel' => 'sms',
                'name' => $name,
                'body' => $body,
                'version' => $latestVersion + 1,
                'created_by' => auth()->id(),
            ]);
        }

        $this->logSettingsAudit(
            $site->id,
            'settings.communications.updated',
            ['old' => null, 'new' => $communicationSettings],
            $validated['reason'] ?? null
        );

        return back()->with('success', 'Communications settings and templates saved.');
    }

    public function previewTemplate(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $sample = [
            '{{worker_name}}' => 'John Kamau',
            '{{site_name}}' => $site->name,
            '{{amount}}' => 'KES 5,500',
            '{{paycycle_start}}' => now()->startOfWeek()->format('d M Y'),
            '{{paycycle_end}}' => now()->endOfWeek()->format('d M Y'),
            '{{transaction_ref}}' => 'TRX123456',
            '{{claim_id}}' => 'CLM-2026-001',
        ];

        $preview = str_replace(array_keys($sample), array_values($sample), $validated['body']);
        $length = mb_strlen($preview);
        $parts = (int) ceil($length / 160);

        return response()->json([
            'preview' => $preview,
            'length' => $length,
            'parts' => $parts,
            'multipart_warning' => $parts > 1,
            'variables' => $this->templateVariables,
        ]);
    }

    public function sendTestSms(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:1000',
        ]);

        $this->logSettingsAudit(
            $site->id,
            'settings.communications.test_sms',
            [
                'old' => null,
                'new' => [
                    'phone' => $validated['phone'],
                    'message_length' => mb_strlen($validated['message']),
                    'mocked' => true,
                ],
            ]
        );

        return back()->with('success', 'Mock test SMS sent successfully.');
    }

    public function updateFeatures(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'features' => 'required|array',
            'features.*.value' => 'required|boolean',
            'features.*.rollout_percent' => 'required|integer|min:1|max:100',
            'reason' => 'nullable|string|max:500',
        ]);

        foreach ($validated['features'] as $flagName => $payload) {
            SiteFeatureFlag::updateOrCreate(
                ['site_id' => $site->id, 'flag_name' => $flagName],
                [
                    'value' => (bool) $payload['value'],
                    'rollout_percent' => (int) $payload['rollout_percent'],
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->logSettingsAudit(
            $site->id,
            'settings.features.updated',
            ['old' => null, 'new' => $validated['features']],
            $validated['reason'] ?? null
        );

        return back()->with('success', 'Feature flags updated successfully.');
    }

    public function updateNotifications(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'claim_created' => 'nullable|boolean',
            'payout_failed' => 'nullable|boolean',
            'low_wallet_balance' => 'nullable|boolean',
            'channels' => 'nullable|array',
            'channels.*' => 'in:sms,email',
            'reason' => 'nullable|string|max:500',
        ]);

        $payload = [
            'claim_created' => (bool) ($validated['claim_created'] ?? false),
            'payout_failed' => (bool) ($validated['payout_failed'] ?? false),
            'low_wallet_balance' => (bool) ($validated['low_wallet_balance'] ?? false),
            'channels' => $validated['channels'] ?? ['sms'],
            'inherited' => false,
        ];

        SiteSetting::updateOrCreate(
            ['site_id' => $site->id, 'key' => 'notifications'],
            ['value' => $payload, 'overridden_by' => auth()->id(), 'overridden_at' => now()]
        );

        $this->logSettingsAudit($site->id, 'settings.notifications.updated', ['old' => null, 'new' => $payload], $validated['reason'] ?? null);

        return back()->with('success', 'Notification preferences saved.');
    }

    public function storeInvitation(Request $request, Site $site)
    {
        $this->assertOwnerHasSite($site->id);

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'role' => 'required|in:owner_admin,attendance_approver,payroll_clerk,viewer,foreman,worker',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
            'reason' => 'nullable|string|max:500',
        ]);

        $token = Str::random(60);
        $expiresInDays = $validated['expires_in_days'] ?? 7;

        $invitation = Invitation::create([
            'site_id' => $site->id,
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'token' => $token,
            'expires_at' => now()->addDays($expiresInDays),
            'created_by' => auth()->id(),
        ]);

        $acceptLink = route('invites.accept.form', ['token' => $token]);

        $this->logSettingsAudit(
            $site->id,
            'settings.access.invitation_created',
            [
                'old' => null,
                'new' => [
                    'phone' => $validated['phone'],
                    'role' => $validated['role'],
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                    'accept_link' => $acceptLink,
                    'mocked_sms' => true,
                ],
            ],
            $validated['reason'] ?? null
        );

        return back()->with('success', 'Invitation created. Mock SMS sent with link: ' . $acceptLink);
    }

    public function acceptInvitationForm(string $token)
    {
        $invitation = Invitation::with('site')
            ->where('token', $token)
            ->first();

        if (!$invitation || $invitation->used_at || $invitation->isExpired()) {
            return view('invitations.accept-expired');
        }

        return view('invitations.accept', compact('invitation'));
    }

    public function acceptInvitation(Request $request, string $token)
    {
        $invitation = Invitation::with('site')
            ->where('token', $token)
            ->first();

        if (!$invitation || $invitation->used_at || $invitation->isExpired()) {
            return back()->with('error', 'This invitation is expired or already used.');
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validated['phone'] !== $invitation->phone) {
            return back()->withErrors(['phone' => 'Phone does not match invitation target.']);
        }

        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password'] ?: Str::random(12)),
                'role' => 'worker',
                'kyc_status' => 'pending',
            ]);
        }

        SiteMember::firstOrCreate(
            ['site_id' => $invitation->site_id, 'user_id' => $user->id, 'role' => $invitation->role],
            [
                'assigned_by' => $invitation->created_by,
                'assigned_at' => now(),
                'notification_preferences' => [
                    'claim_created' => true,
                    'payout_failed' => true,
                ],
            ]
        );

        $invitation->update([
            'used_by' => $user->id,
            'used_at' => now(),
            'accepted_ip' => $request->ip(),
            'accepted_user_agent' => $request->userAgent(),
        ]);

        SiteSettingsAudit::create([
            'site_id' => $invitation->site_id,
            'changed_by' => $user->id,
            'action' => 'settings.access.invitation_accepted',
            'change' => [
                'old' => null,
                'new' => [
                    'role' => $invitation->role,
                    'phone' => $invitation->phone,
                ],
            ],
        ]);

        return redirect()->route('login')->with('success', 'Invitation accepted successfully. You can now login.');
    }

    private function assertOwnerHasSite(int $siteId): void
    {
        $ownsSite = auth()->user()->ownedSites()->where('id', $siteId)->exists();
        if (!$ownsSite) {
            abort(403, 'Unauthorized action for this site.');
        }
    }

    private function logSettingsAudit(int $siteId, string $action, array $change, ?string $reason = null): void
    {
        SiteSettingsAudit::create([
            'site_id' => $siteId,
            'changed_by' => auth()->id(),
            'action' => $action,
            'change' => $change,
            'reason' => $reason,
        ]);
    }
}
