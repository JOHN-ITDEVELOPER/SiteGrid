<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        // General
        'platform_name',
        'platform_description',
        'support_email',
        'default_country_code',
        'timezone',
        'number_format',
        'workweek_start',
        
        // Authentication
        'otp_method',
        'otp_expiry_seconds',
        'password_min_length',
        'password_require_complexity',
        'session_timeout_minutes',
        'max_concurrent_sessions',
        'admin_2fa_required',
        'admin_2fa_grace_days',
        
        // Integrations
        'mpesa_config',
        'ussd_config',
        'sms_config',
        
        // Payment Account Routing (references to PlatformAccount IDs)
        'platform_deposit_account_id',
        'platform_invoice_account_id',
        'platform_payout_account_id',
        
        // Billing & Pricing
        'platform_fee_per_worker',
        'default_currency',
        'billing_cadence',
        'free_trial_workers',
        'free_trial_weeks',
        'fee_model',
        'fee_percentage',
        'late_fee_amount',
        'invoice_reminder_days',
        'default_invoice_due_days',
        
        // Payroll & Payout
        'payout_window_start_day',
        'payout_window_end_day',
        'payout_delay_days',
        'escrow_enabled',
        'escrow_release_conditions',
        'max_payout_per_batch',
        'payout_retry_attempts',
        'payout_retry_backoff_minutes',
        
        // Notifications
        'sms_templates',
        'email_templates',
        'receipts_enabled',
        
        // Webhooks & API
        'webhook_urls',
        'webhook_signing_key',
        'api_ip_whitelist',
        'webhook_max_retries',
        'webhook_retry_backoff_seconds',
        
        // USSD
        'ussd_shortcodes',
        'ussd_menu_config',
        'ussd_session_timeout_seconds',
        
        // Security
        'encrypted_secrets',
        'key_rotation_days',
        'cors_origins',
        'audit_retention_days',
        
        // Feature Flags
        'feature_flags',
        'pilot_sites',
        
        // Data & Backups
        'backup_schedule',
        'backup_retention_days',
        'backup_storage',
        'inactive_archive_months',
        
        // Legal
        'terms_content',
        'privacy_content',
        'kyc_threshold_amount',
        'deletion_requires_approval',
        'deletion_approvers_required',
        
        // System
        'environment',
        'updated_by',
    ];

    protected $casts = [
        'password_require_complexity' => 'boolean',
        'admin_2fa_required' => 'boolean',
        'escrow_enabled' => 'boolean',
        'receipts_enabled' => 'boolean',
        'deletion_requires_approval' => 'boolean',
        'mpesa_config' => 'array',
        'ussd_config' => 'array',
        'sms_config' => 'array',
        'sms_templates' => 'array',
        'email_templates' => 'array',
        'webhook_urls' => 'array',
        'api_ip_whitelist' => 'array',
        'ussd_shortcodes' => 'array',
        'ussd_menu_config' => 'array',
        'encrypted_secrets' => 'array',
        'cors_origins' => 'array',
        'feature_flags' => 'array',
        'pilot_sites' => 'array',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
