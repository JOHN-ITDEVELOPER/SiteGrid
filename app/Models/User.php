<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'site_name',
        'avatar_url',
        'password',
        'role',
        'kyc_status',
        'is_suspended',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
        'password_reset_required',
        'timezone',
        'locale',
        'notification_preferences',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'is_suspended' => 'boolean',
        'password_reset_required' => 'boolean',
        'notification_preferences' => 'array',
    ];

    // Relationships for Site Owners
    public function ownedSites()
    {
        return $this->hasMany(Site::class, 'owner_id');
    }

    public function wallet()
    {
        return $this->hasOne(OwnerWallet::class);
    }

    // Relationships for Workers
    public function siteWorkers()
    {
        return $this->hasMany(SiteWorker::class);
    }

    public function sites()
    {
        return $this->belongsToMany(Site::class, 'site_workers');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'worker_id');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'worker_id');
    }

    public function siteMemberships()
    {
        return $this->hasMany(SiteMember::class);
    }

    public function siteMembers()
    {
        return $this->siteMemberships();
    }

    // Helpers
    public function isSiteOwner(): bool
    {
        return $this->role === 'site_owner';
    }

    public function isForeman($siteId): bool
    {
        return $this->siteWorkers()
            ->where('site_id', $siteId)
            ->where('is_foreman', true)
            ->exists();
    }

    public function isWorkerAtSite($siteId): bool
    {
        return $this->siteWorkers()
            ->where('site_id', $siteId)
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'platform_admin';
    }

    public function procurementRequests()
    {
        return $this->hasMany(ProcurementRequest::class, 'requested_by');
    }

    public function approvedProcurementRequests()
    {
        return $this->hasMany(ProcurementRequest::class, 'approved_by');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'performed_by');
    }

    public function progressLogs()
    {
        return $this->hasMany(SiteProgressLog::class, 'created_by');
    }

    /**
     * Check if email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Get email for verification
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }

    /**
     * Send email verification notification
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmail());
    }
}
