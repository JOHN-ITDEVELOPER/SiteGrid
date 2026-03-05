<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Site $site) {
            if (is_null($site->invoice_due_days)) {
                $settings = PlatformSetting::firstOrCreate([]);
                $site->invoice_due_days = (int) ($settings->default_invoice_due_days ?? 14);
            }

            if (empty($site->invoice_payment_method)) {
                $site->invoice_payment_method = 'auto_wallet';
            }
        });

        static::created(function (Site $site) {
            // Create default policy when site is created
            SitePolicy::create([
                'site_id' => $site->id,
                'lock_payout_method' => true,
                'lock_invoice_payment_method' => true,
                'lock_compliance_settings' => true,
                'lock_payout_window' => false,
                'lock_auto_payout' => false,
                'lock_approval_workflow' => false,
            ]);
        });
    }

    protected $fillable = [
        'owner_id',
        'name',
        'location',
        'is_completed',
        'payout_window_start',
        'payout_window_end',
        'payout_opens_at',
        'payout_closes_at',
        'payout_method',
        'owner_mpesa_account',
        'billing_plan',
        'invoice_payment_method',
        'invoice_due_days',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'billing_plan' => 'json',
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function policy()
    {
        return $this->hasOne(SitePolicy::class);
    }

    public function workers(): HasMany
    {
        return $this->hasMany(SiteWorker::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payCycles(): HasMany
    {
        return $this->hasMany(PayCycle::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(SiteSetting::class);
    }

    public function featureFlags(): HasMany
    {
        return $this->hasMany(SiteFeatureFlag::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(SiteTemplate::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(SiteMember::class);
    }

    public function inventoryCategories(): HasMany
    {
        return $this->hasMany(InventoryCategory::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(SiteProgressLog::class);
    }

    /**
     * Get payout settings for this site
     * Returns cached result or defaults, with fallback to sites table columns
     */
    public function getPayoutSettings(): array
    {
        $jsonSettings = $this->settings()
            ->where('key', 'payouts')
            ->value('value');
        
        // If site_settings JSON exists, use it
        if ($jsonSettings !== null) {
            return $jsonSettings;
        }
        
        // Fallback: Convert sites table columns to expected format (use raw attributes to avoid accessor recursion)
        $rawPayoutWindowStart = $this->getAttributeFromArray('payout_window_start');
        $rawPayoutWindowEnd = $this->getAttributeFromArray('payout_window_end');
        $rawPayoutOpensAt = $this->getAttributeFromArray('payout_opens_at');

        if ($rawPayoutWindowStart && $rawPayoutOpensAt) {
            $dayMap = [
                'Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu',
                'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'
            ];
            
            // Build days array from start to end
            $days = [];
            $allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $startIdx = array_search($rawPayoutWindowStart, $allDays);
            $endIdx = array_search($rawPayoutWindowEnd, $allDays);
            
            if ($startIdx !== false && $endIdx !== false) {
                if ($startIdx <= $endIdx) {
                    // Normal range: Mon-Fri
                    for ($i = $startIdx; $i <= $endIdx; $i++) {
                        $days[] = $dayMap[$allDays[$i]];
                    }
                } else {
                    // Wraps around week: Fri-Mon
                    for ($i = $startIdx; $i < count($allDays); $i++) {
                        $days[] = $dayMap[$allDays[$i]];
                    }
                    for ($i = 0; $i <= $endIdx; $i++) {
                        $days[] = $dayMap[$allDays[$i]];
                    }
                }
            }
            
            // Extract time from payout_opens_at (e.g., "17:00:00" → "17:00")
            $time = substr((string) $rawPayoutOpensAt, 0, 5);
            
            return [
                'auto_payout' => false,
                'auto_approve_claims' => false,
                'fully_automated' => false,
                'enforce_windows' => true,
                'min_days_between_withdrawals' => 0,
                'windows' => [['days' => $days ?: ['Fri'], 'time' => $time ?: '17:00', 'timezone' => 'Africa/Nairobi']],
                'min_balance_guard' => 0,
                'max_batch_limit' => 500000,
                'inherited' => true, // Mark as inherited from sites table
            ];
        }
        
        // Ultimate fallback: platform defaults
        return [
            'auto_payout' => false,
            'auto_approve_claims' => false,
            'fully_automated' => false,
            'enforce_windows' => true,
            'min_days_between_withdrawals' => 0,
            'windows' => [['days' => ['Fri'], 'time' => '17:00', 'timezone' => 'Africa/Nairobi']],
            'min_balance_guard' => 0,
            'max_batch_limit' => 500000,
            'inherited' => true,
        ];
    }

    /**
     * Accessor: Get payout_window_start from site_settings if available, else from DB column
     */
    public function getPayoutWindowStartAttribute($value)
    {
        $settings = $this->getPayoutSettings();
        if (!($settings['inherited'] ?? true)) {
            // site_settings exists, convert first day back to full name
            $days = $settings['windows'][0]['days'] ?? [];
            if (empty($days)) return $value;
            
            $dayMap = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday',
                       'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];
            return $dayMap[$days[0]] ?? $value;
        }
        return $value;
    }

    /**
     * Accessor: Get payout_window_end from site_settings if available, else from DB column
     */
    public function getPayoutWindowEndAttribute($value)
    {
        $settings = $this->getPayoutSettings();
        if (!($settings['inherited'] ?? true)) {
            $days = $settings['windows'][0]['days'] ?? [];
            if (empty($days)) return $value;
            
            $dayMap = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday',
                       'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];
            return $dayMap[end($days)] ?? $value;
        }
        return $value;
    }

    /**
     * Accessor: Get payout_opens_at from site_settings if available, else from DB column
     */
    public function getPayoutOpensAtAttribute($value)
    {
        $settings = $this->getPayoutSettings();
        if (!($settings['inherited'] ?? true)) {
            $time = $settings['windows'][0]['time'] ?? '17:00';
            return $time . ':00'; // Convert "17:00" to "17:00:00"
        }
        return $value;
    }

    /**
     * Accessor: Get payout_closes_at from site_settings if available, else from DB column
     */
    public function getPayoutClosesAtAttribute($value)
    {
        $settings = $this->getPayoutSettings();
        if (!($settings['inherited'] ?? true)) {
            $time = $settings['windows'][0]['time'] ?? '17:00';
            $closeTime = \Carbon\Carbon::parse($time)->addHour()->format('H:i:s');
            return $closeTime;
        }
        return $value;
    }
}
