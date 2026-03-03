<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // 1. Platform (General)
            $table->string('platform_name')->default('Mjengo')->after('id');
            $table->text('platform_description')->nullable()->after('platform_name');
            $table->string('support_email')->nullable()->after('platform_description');
            $table->string('default_country_code', 10)->default('+254')->after('support_email');
            $table->string('timezone')->default('Africa/Nairobi')->after('default_country_code');
            $table->string('number_format')->default('en_KE')->after('timezone');
            $table->string('workweek_start')->default('Monday')->after('number_format');

            // 2. Authentication & Access
            $table->enum('otp_method', ['sms', 'voice'])->default('sms')->after('workweek_start');
            $table->integer('otp_expiry_seconds')->default(120)->after('otp_method');
            $table->integer('password_min_length')->default(8)->after('otp_expiry_seconds');
            $table->boolean('password_require_complexity')->default(false)->after('password_min_length');
            $table->integer('session_timeout_minutes')->default(120)->after('password_require_complexity');
            $table->integer('max_concurrent_sessions')->default(3)->after('session_timeout_minutes');
            $table->boolean('admin_2fa_required')->default(false)->after('max_concurrent_sessions');
            $table->integer('admin_2fa_grace_days')->default(7)->after('admin_2fa_required');

            // 3. Integrations (Payments & USSD)
            $table->json('mpesa_config')->nullable()->after('admin_2fa_grace_days');
            $table->json('ussd_config')->nullable()->after('mpesa_config');
            $table->json('sms_config')->nullable()->after('ussd_config');

            // 4. Billing & Pricing (already has platform_fee_per_worker and currency)
            $table->enum('billing_cadence', ['weekly', 'monthly'])->default('weekly')->after('default_currency');
            $table->integer('free_trial_workers')->default(0)->after('billing_cadence');
            $table->integer('free_trial_weeks')->default(0)->after('free_trial_workers');
            $table->enum('fee_model', ['flat', 'percentage', 'hybrid'])->default('flat')->after('free_trial_weeks');
            $table->decimal('fee_percentage', 5, 2)->nullable()->after('fee_model');
            $table->decimal('late_fee_amount', 10, 2)->default(0)->after('fee_percentage');
            $table->string('invoice_reminder_days')->default('3,7,14')->after('late_fee_amount');

            // 5. Payroll & Payout Rules
            $table->integer('payout_delay_days')->default(0)->after('payout_window_end_day');
            $table->boolean('escrow_enabled')->default(false)->after('payout_delay_days');
            $table->text('escrow_release_conditions')->nullable()->after('escrow_enabled');
            $table->decimal('max_payout_per_batch', 12, 2)->nullable()->after('escrow_release_conditions');
            $table->integer('payout_retry_attempts')->default(3)->after('max_payout_per_batch');
            $table->integer('payout_retry_backoff_minutes')->default(30)->after('payout_retry_attempts');

            // 6. Notifications & Message Templates
            $table->json('sms_templates')->nullable()->after('payout_retry_backoff_minutes');
            $table->json('email_templates')->nullable()->after('sms_templates');
            $table->boolean('receipts_enabled')->default(true)->after('email_templates');

            // 7. Webhooks & API
            $table->json('webhook_urls')->nullable()->after('receipts_enabled');
            $table->string('webhook_signing_key')->nullable()->after('webhook_urls');
            $table->json('api_ip_whitelist')->nullable()->after('webhook_signing_key');
            $table->integer('webhook_max_retries')->default(3)->after('api_ip_whitelist');
            $table->integer('webhook_retry_backoff_seconds')->default(60)->after('webhook_max_retries');

            // 8. USSD & Shortcodes
            $table->json('ussd_shortcodes')->nullable()->after('webhook_retry_backoff_seconds');
            $table->json('ussd_menu_config')->nullable()->after('ussd_shortcodes');
            $table->integer('ussd_session_timeout_seconds')->default(60)->after('ussd_menu_config');

            // 9. Security & Secrets
            $table->json('encrypted_secrets')->nullable()->after('ussd_session_timeout_seconds');
            $table->integer('key_rotation_days')->default(90)->after('encrypted_secrets');
            $table->json('cors_origins')->nullable()->after('key_rotation_days');
            $table->integer('audit_retention_days')->default(365)->after('cors_origins');

            // 10. Feature Flags & Pilots
            $table->json('feature_flags')->nullable()->after('audit_retention_days');
            $table->json('pilot_sites')->nullable()->after('feature_flags');

            // 11. Data & Backups
            $table->enum('backup_schedule', ['daily', 'weekly'])->default('daily')->after('pilot_sites');
            $table->integer('backup_retention_days')->default(30)->after('backup_schedule');
            $table->string('backup_storage')->default('local')->after('backup_retention_days');
            $table->integer('inactive_archive_months')->default(12)->after('backup_storage');

            // 12. Legal / Compliance
            $table->text('terms_content')->nullable()->after('inactive_archive_months');
            $table->text('privacy_content')->nullable()->after('terms_content');
            $table->decimal('kyc_threshold_amount', 12, 2)->default(100000)->after('privacy_content');
            $table->boolean('deletion_requires_approval')->default(true)->after('kyc_threshold_amount');
            $table->integer('deletion_approvers_required')->default(2)->after('deletion_requires_approval');

            // Environment indicator
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox')->after('deletion_approvers_required');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'platform_name', 'platform_description', 'support_email', 'default_country_code',
                'timezone', 'number_format', 'workweek_start',
                'otp_method', 'otp_expiry_seconds', 'password_min_length', 'password_require_complexity',
                'session_timeout_minutes', 'max_concurrent_sessions', 'admin_2fa_required', 'admin_2fa_grace_days',
                'mpesa_config', 'ussd_config', 'sms_config',
                'billing_cadence', 'free_trial_workers', 'free_trial_weeks', 'fee_model', 'fee_percentage',
                'late_fee_amount', 'invoice_reminder_days',
                'payout_delay_days', 'escrow_enabled', 'escrow_release_conditions', 'max_payout_per_batch',
                'payout_retry_attempts', 'payout_retry_backoff_minutes',
                'sms_templates', 'email_templates', 'receipts_enabled',
                'webhook_urls', 'webhook_signing_key', 'api_ip_whitelist', 'webhook_max_retries',
                'webhook_retry_backoff_seconds',
                'ussd_shortcodes', 'ussd_menu_config', 'ussd_session_timeout_seconds',
                'encrypted_secrets', 'key_rotation_days', 'cors_origins', 'audit_retention_days',
                'feature_flags', 'pilot_sites',
                'backup_schedule', 'backup_retention_days', 'backup_storage', 'inactive_archive_months',
                'terms_content', 'privacy_content', 'kyc_threshold_amount', 'deletion_requires_approval',
                'deletion_approvers_required', 'environment'
            ]);
        });
    }
};
