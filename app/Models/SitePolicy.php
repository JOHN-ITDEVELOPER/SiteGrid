<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'lock_payout_method',
        'lock_payout_window',
        'lock_invoice_payment_method',
        'lock_compliance_settings',
        'lock_auto_payout',
        'lock_approval_workflow',
        'allowed_payout_methods',
        'payout_window_constraints',
        'sms_provider_whitelist',
        'max_team_members',
        'max_foremen',
        'is_locked_down',
        'lockdown_reason',
        'lockdown_until',
        'last_policy_changed_at',
        'last_policy_changed_by',
    ];

    protected $casts = [
        'lock_payout_method' => 'boolean',
        'lock_payout_window' => 'boolean',
        'lock_invoice_payment_method' => 'boolean',
        'lock_compliance_settings' => 'boolean',
        'lock_auto_payout' => 'boolean',
        'lock_approval_workflow' => 'boolean',
        'is_locked_down' => 'boolean',
        'payout_window_constraints' => 'array',
        'sms_provider_whitelist' => 'array',
        'lockdown_until' => 'datetime',
        'last_policy_changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_policy_changed_by');
    }

    // Helpers to check if setting is locked
    public function isPayoutMethodLocked(): bool
    {
        if ($this->is_locked_down) return true;
        return $this->lock_payout_method;
    }

    public function isPayoutWindowLocked(): bool
    {
        if ($this->is_locked_down) return true;
        return $this->lock_payout_window;
    }

    public function isInvoicePaymentMethodLocked(): bool
    {
        if ($this->is_locked_down) return true;
        return $this->lock_invoice_payment_method;
    }

    public function isComplianceSettingsLocked(): bool
    {
        if ($this->is_locked_down) return true;
        return $this->lock_compliance_settings;
    }

    public function isAutoPayoutLocked(): bool
    {
        if ($this->is_locked_down) return true;
        return $this->lock_auto_payout;
    }

    public function isApprovalWorkflowLocked(): bool
    {
        if ($this->is_locked_down) return true;
        return $this->lock_approval_workflow;
    }

    // Check if site is currently in lockdown
    public function isCurrentlyLockedDown(): bool
    {
        if (!$this->is_locked_down) {
            return false;
        }

        if ($this->lockdown_until && now()->isAfter($this->lockdown_until)) {
            // Lockdown period has expired
            $this->update([
                'is_locked_down' => false,
                'lockdown_until' => null,
            ]);
            return false;
        }

        return true;
    }

    // Get message about what's locked
    public function getLockedSettingsMessage(): array
    {
        $locked = [];

        if ($this->isPayoutMethodLocked()) {
            $locked[] = 'Payout Method (locked by admin)';
        }
        if ($this->isPayoutWindowLocked()) {
            $locked[] = 'Payout Window (locked by admin)';
        }
        if ($this->isInvoicePaymentMethodLocked()) {
            $locked[] = 'Invoice Payment Method (locked by admin)';
        }
        if ($this->isComplianceSettingsLocked()) {
            $locked[] = 'Compliance Settings (locked by admin)';
        }
        if ($this->isAutoPayoutLocked()) {
            $locked[] = 'Auto-Payout Settings (locked by admin)';
        }
        if ($this->isApprovalWorkflowLocked()) {
            $locked[] = 'Approval Workflow (locked by admin)';
        }

        return $locked;
    }
};
