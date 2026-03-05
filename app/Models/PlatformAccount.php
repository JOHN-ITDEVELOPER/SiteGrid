<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_type',
        'name',
        'description',
        'provider',
        'shortcode',
        'credentials',
        'status',
        'activated_at',
        'last_tested_at',
        'last_test_error',
        'is_primary',
        'routing_rules',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'credentials' => 'array',
        'routing_rules' => 'array',
        'activated_at' => 'datetime',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Get the user who created this account.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this account.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get revenue records for this account.
     */
    public function revenue()
    {
        return $this->hasMany(PlatformRevenue::class, 'platform_account_id');
    }

    /**
     * Scope: Get active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Get primary account for a type.
     */
    public function scopePrimary($query, $type)
    {
        return $query->where('account_type', $type)->where('is_primary', true)->first();
    }

    /**
     * Scope: Get all accounts of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Mark account as active and test successfully.
     */
    public function markAsActive()
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
            'last_tested_at' => now(),
            'last_test_error' => null,
        ]);
    }

    /**
     * Mark account as failed with error.
     */
    public function markAsFailed($error)
    {
        $this->update([
            'status' => 'failed',
            'last_tested_at' => now(),
            'last_test_error' => $error,
        ]);
    }

    /**
     * Get credentials with fallback safety.
     */
    public function getCredential($key)
    {
        return $this->credentials[$key] ?? null;
    }

    /**
     * Safe credential getter for sensitive fields.
     */
    public function getMaskedCredentials()
    {
        return [
            'shortcode' => $this->shortcode,
            'provider' => $this->provider,
            'consumer_key' => substr($this->getCredential('consumer_key') ?? '', 0, 4) . '****',
            'consumer_secret' => '****',
            'passkey' => '****',
        ];
    }
}
