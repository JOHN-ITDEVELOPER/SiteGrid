<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'start_date',
        'end_date',
        'status',
        'total_amount',
        'worker_count',
        'recurrence_pattern',
        'next_cycle_date',
        'is_auto_generated',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_cycle_date' => 'date',
        'is_auto_generated' => 'boolean',
    ];

    // Relationships
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
