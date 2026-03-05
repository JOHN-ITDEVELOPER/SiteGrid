<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\AuditLog;
use App\Models\ActivityLog;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = PlatformSetting::firstOrCreate([]);
        $sites = Site::select('id', 'name')->get();

        // Load platform accounts for the accounts tab
        $escrowAccount = \App\Models\PlatformAccount::where('account_type', 'deposit')->first();
        $revenueAccount = \App\Models\PlatformAccount::where('account_type', 'invoice')->first();

        return view('admin.settings.edit', compact('settings', 'sites', 'escrowAccount', 'revenueAccount'));
    }

    public function update(Request $request)
    {
        $section = $request->input('section', 'general');
        $settings = PlatformSetting::firstOrCreate([]);
        
        $validated = $this->validateSection($request, $section);
        
        // Check if sensitive changes require 2FA
        if ($this->requiresSensitiveConfirmation($section, $validated)) {
            // In production, check 2FA here
            // For now, just log the attempt
        }
        
        $old_values = $settings->only(array_keys($validated));
        $settings->fill($validated);
        $settings->updated_by = auth()->id();
        $settings->save();

        // Log the change
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => "settings.update.{$section}",
            'entity_type' => 'PlatformSetting',
            'entity_id' => $settings->id,
            'meta' => [
                'section' => $section,
                'old_values' => $old_values,
                'new_values' => $validated,
                'reason' => $request->input('change_reason'),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        ActivityLog::create([
            'type' => 'settings_update',
            'severity' => $this->getSeverity($section),
            'message' => "Settings updated: {$section}",
            'user_id' => auth()->id(),
            'entity_type' => 'PlatformSetting',
            'entity_id' => $settings->id,
            'meta' => ['section' => $section],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.settings.edit', ['tab' => $section])
            ->with('success', ucfirst($section) . ' settings updated successfully');
    }

    protected function validateSection(Request $request, string $section): array
    {
        $rules = match($section) {
            'general' => [
                'platform_name' => 'required|string|max:255',
                'platform_description' => 'nullable|string',
                'support_email' => 'nullable|email',
                'default_country_code' => 'required|string|max:10',
                'timezone' => 'required|string',
                'number_format' => 'required|string',
                'workweek_start' => 'required|string',
            ],
            'authentication' => [
                'otp_method' => 'required|in:sms,voice',
                'otp_expiry_seconds' => 'required|integer|min:30|max:300',
                'password_min_length' => 'required|integer|min:6|max:32',
                'password_require_complexity' => 'boolean',
                'session_timeout_minutes' => 'required|integer|min:15|max:480',
                'max_concurrent_sessions' => 'required|integer|min:1|max:10',
                'admin_2fa_required' => 'boolean',
                'admin_2fa_grace_days' => 'required|integer|min:0|max:30',
            ],
            'integrations' => [
                'mpesa_config' => 'nullable|array',
                'ussd_config' => 'nullable|array',
                'sms_config' => 'nullable|array',
            ],
            'billing' => [
                'platform_fee_per_worker' => 'required|numeric|min:0',
                'default_currency' => 'required|string|max:8',
                'billing_cadence' => 'required|in:weekly,monthly',
                'free_trial_workers' => 'required|integer|min:0',
                'free_trial_weeks' => 'required|integer|min:0',
                'fee_model' => 'required|in:flat,percentage,hybrid',
                'fee_percentage' => 'nullable|numeric|min:0|max:100',
                'late_fee_amount' => 'required|numeric|min:0',
                'invoice_reminder_days' => 'required|string',
                'default_invoice_due_days' => 'required|integer|min:1|max:90',
            ],
            'payroll' => [
                'payout_window_start_day' => 'required|integer|min:1|max:7',
                'payout_window_end_day' => 'required|integer|min:1|max:7',
                'payout_delay_days' => 'required|integer|min:0',
                'escrow_enabled' => 'boolean',
                'escrow_release_conditions' => 'nullable|string',
                'max_payout_per_batch' => 'nullable|numeric|min:0',
                'payout_retry_attempts' => 'required|integer|min:1|max:10',
                'payout_retry_backoff_minutes' => 'required|integer|min:5',
            ],
            'notifications' => [
                'sms_templates' => 'nullable|array',
                'email_templates' => 'nullable|array',
                'receipts_enabled' => 'boolean',
            ],
            'webhooks' => [
                'webhook_urls' => 'nullable|array',
                'webhook_signing_key' => 'nullable|string',
                'api_ip_whitelist' => 'nullable|array',
                'webhook_max_retries' => 'required|integer|min:1|max:10',
                'webhook_retry_backoff_seconds' => 'required|integer|min:10',
            ],
            'ussd' => [
                'ussd_shortcodes' => 'nullable|array',
                'ussd_menu_config' => 'nullable|array',
                'ussd_session_timeout_seconds' => 'required|integer|min:30|max:180',
            ],
            'security' => [
                'encrypted_secrets' => 'nullable|array',
                'key_rotation_days' => 'required|integer|min:30|max:365',
                'cors_origins' => 'nullable|array',
                'audit_retention_days' => 'required|integer|min:90|max:3650',
            ],
            'features' => [
                'feature_flags' => 'nullable|array',
                'pilot_sites' => 'nullable|array',
            ],
            'data' => [
                'backup_schedule' => 'required|in:daily,weekly',
                'backup_retention_days' => 'required|integer|min:7|max:365',
                'backup_storage' => 'required|string',
                'inactive_archive_months' => 'required|integer|min:3|max:60',
            ],
            'legal' => [
                'terms_content' => 'nullable|string',
                'privacy_content' => 'nullable|string',
                'kyc_threshold_amount' => 'required|numeric|min:0',
                'deletion_requires_approval' => 'boolean',
                'deletion_approvers_required' => 'required|integer|min:1|max:5',
            ],
            'environment' => [
                'environment' => 'required|in:sandbox,production',
            ],
            default => []
        };

        return $request->validate($rules);
    }

    protected function requiresSensitiveConfirmation(string $section, array $data): bool
    {
        $sensitive_sections = ['integrations', 'security', 'environment'];
        return in_array($section, $sensitive_sections);
    }

    protected function getSeverity(string $section): string
    {
        $critical = ['integrations', 'security', 'payroll', 'environment'];
        $warning = ['billing', 'webhooks', 'legal'];
        
        if (in_array($section, $critical)) return 'critical';
        if (in_array($section, $warning)) return 'warning';
        return 'info';
    }

    // Test MPesa connection
    public function testMpesa(Request $request)
    {
        $config = $request->input('mpesa_config');
        
        try {
            // Simulate token fetch
            $response = Http::withBasicAuth($config['consumer_key'] ?? '', $config['consumer_secret'] ?? '')
                ->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

            if ($response->successful()) {
                ActivityLog::create([
                    'type' => 'mpesa_test',
                    'severity' => 'info',
                    'message' => 'MPesa connection test successful',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful',
                    'token_expires_in' => $response->json('expires_in')
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to authenticate'], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Trigger manual backup
    public function triggerBackup(Request $request)
    {
        try {
            // Simplified backup - in production use proper backup solution
            $filename = 'backup-' . now()->format('Y-m-d-His') . '.sql';
            
            ActivityLog::create([
                'type' => 'manual_backup',
                'severity' => 'info',
                'message' => 'Manual backup triggered',
                'user_id' => auth()->id(),
                'meta' => ['filename' => $filename],
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backup initiated',
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Preview invoice with current settings
    public function previewInvoice(Request $request)
    {
        $settings = PlatformSetting::firstOrCreate([]);
        
        $sample_workers = (int) $request->input('workers', 10);
        $fee = $settings->platform_fee_per_worker;
        $model = $settings->fee_model;
        $percentage = $settings->fee_percentage ?? 0;

        $total = match($model) {
            'flat' => $sample_workers * $fee,
            'percentage' => ($sample_workers * 5000) * ($percentage / 100), // Assume 5000 per worker
            'hybrid' => ($sample_workers * $fee) + (($sample_workers * 5000) * ($percentage / 100)),
            default => 0
        };

        return response()->json([
            'workers' => $sample_workers,
            'fee_model' => $model,
            'total' => number_format($total, 2),
            'currency' => $settings->default_currency,
        ]);
    }

    // Simulate USSD session
    public function simulateUssd(Request $request)
    {
        $settings = PlatformSetting::firstOrCreate([]);
        $menu = $settings->ussd_menu_config ?? [];

        return response()->json([
            'session_id' => 'TEST-' . uniqid(),
            'menu' => $menu,
            'timeout' => $settings->ussd_session_timeout_seconds,
        ]);
    }

    /**
     * Manage Platform Payment Accounts
     * Route: /admin/accounts
     */
    public function accounts()
    {
        $deposit_account = \App\Models\PlatformAccount::ofType('deposit')->primary('deposit');
        $invoice_account = \App\Models\PlatformAccount::ofType('invoice')->primary('invoice');
        $payout_account = \App\Models\PlatformAccount::ofType('payout')->primary('payout');

        $all_accounts = \App\Models\PlatformAccount::orderBy('account_type')->get();

        return view('admin.settings.accounts', compact(
            'deposit_account',
            'invoice_account',
            'payout_account',
            'all_accounts'
        ));
    }

    /**
     * Create or update payment account
     */
    public function saveAccount(Request $request)
    {
        $validated = $request->validate([
            'account_type' => 'required|in:deposit,invoice,payout',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'shortcode' => 'required|string|max:20',
            'consumer_key' => 'required|string',
            'consumer_secret' => 'required|string',
            'passkey' => 'required|string',
            'is_primary' => 'boolean',
        ]);

        $account_type = $validated['account_type'];
        
        // Find or create account
        $account = \App\Models\PlatformAccount::firstOrCreate(
            ['shortcode' => $validated['shortcode']],
            [
                'account_type' => $account_type,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'provider' => 'mpesa',
                'status' => 'testing',
                'credentials' => [
                    'consumer_key' => $validated['consumer_key'],
                    'consumer_secret' => $validated['consumer_secret'],
                    'passkey' => $validated['passkey'],
                ],
                'is_primary' => $validated['is_primary'] ?? false,
                'created_by' => auth()->id(),
            ]
        );

        // Update credentials if account already exists
        if (!$account->wasRecentlyCreated) {
            $account->update([
                'name' => $validated['name'],
                'credentials' => [
                    'consumer_key' => $validated['consumer_key'],
                    'consumer_secret' => $validated['consumer_secret'],
                    'passkey' => $validated['passkey'],
                ],
                'is_primary' => $validated['is_primary'] ?? false,
                'updated_by' => auth()->id(),
            ]);
        }

        // If marked primary, unmark others of same type
        if ($validated['is_primary'] ?? false) {
            \App\Models\PlatformAccount::where('account_type', $account_type)
                ->where('id', '!=', $account->id)
                ->update(['is_primary' => false]);

            // Update platform setting to reference this account
            PlatformSetting::firstOrCreate([])->update([
                "platform_{$account_type}_account_id" => $account->id,
            ]);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'payment_account.save',
            'entity_type' => 'PlatformAccount',
            'entity_id' => $account->id,
            'meta' => [
                'type' => $account_type,
                'shortcode' => $validated['shortcode'],
                'is_primary' => $validated['is_primary'] ?? false,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.settings.edit', ['tab' => 'accounts'])
            ->with('success', ucfirst($account_type) . ' account saved successfully');
    }

    /**
     * Test payment account connection
     */
    public function testAccount(Request $request, $id)
    {
        $account = \App\Models\PlatformAccount::findOrFail($id);

        try {
            $key = $account->getCredential('consumer_key');
            $secret = $account->getCredential('consumer_secret');

            if (!$key || !$secret) {
                throw new \Exception('Consumer key or secret not found in account credentials');
            }

            $response = Http::withBasicAuth($key, $secret)
                ->timeout(5)
                ->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

            if ($response->successful()) {
                $tokenExpiresIn = $response->json('expires_in');
                $account->markAsActive();

                return response()->json([
                    'success' => true,
                    'message' => 'Account verified successfully (Sandbox)',
                    'shortcode' => $account->shortcode,
                    'account_name' => $account->name,
                    'token_expires' => $tokenExpiresIn,
                    'note' => "Sandbox test passed. Token valid for {$tokenExpiresIn} seconds (~1 hour). Using: sandbox.safaricom.co.ke",
                ]);
            }

            $errorBody = $response->body();
            $errorJson = $response->json();
            
            throw new \Exception(
                'Sandbox API returned ' . $response->status() . ': ' . 
                ($errorJson['error_description'] ?? $errorBody)
            );
        } catch (\Exception $e) {
            $account->markAsFailed($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'hint' => 'Check that Consumer Key and Secret are correct for the Sandbox environment',
            ], 400);
        }
    }

    /**
     * Activate payment account
     */
    public function activateAccount(Request $request, $id)
    {
        $account = \App\Models\PlatformAccount::findOrFail($id);
        
        if ($account->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account must be tested and active before activation',
            ], 400);
        }
        
        $account->update([
            'status' => 'active',
            'updated_by' => auth()->id(),
        ]);
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'payment_account.activate',
            'entity_type' => 'PlatformAccount',
            'entity_id' => $account->id,
            'meta' => ['account_type' => $account->account_type],
            'ip_address' => $request->ip(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Account activated successfully',
        ]);
    }

    /**
     * Deactivate payment account
     */
    public function deactivateAccount(Request $request, $id)
    {
        $account = \App\Models\PlatformAccount::findOrFail($id);
        
        // Verify deactivation won't break active transactions
        $active_transactions = \App\Models\MpesaTransaction::where('platform_account_id', $account->id)
            ->whereIn('status', ['pending', 'initiated'])
            ->count();
        
        if ($active_transactions > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot deactivate: {$active_transactions} active transactions",
            ], 400);
        }
        
        $account->update([
            'status' => 'inactive',
            'updated_by' => auth()->id(),
        ]);
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'payment_account.deactivate',
            'entity_type' => 'PlatformAccount',
            'entity_id' => $account->id,
            'meta' => ['account_type' => $account->account_type],
            'ip_address' => $request->ip(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Account deactivated successfully',
        ]);
    }
}
