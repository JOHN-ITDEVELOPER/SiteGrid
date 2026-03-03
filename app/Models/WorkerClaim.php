<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'worker_id',
        'pay_cycle_id',
        'requested_amount',
        'computed_amount',
        'status',
        'reason',
        'source',
        'approved_by_foreman',
        'approved_by_owner',
        'rejected_by',
        'rejection_reason',
        'transaction_ref',
        'override_reason',
        'requested_at',
        'approved_at',
        'paid_at',
        'overridden_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'computed_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'overridden_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function payCycle(): BelongsTo
    {
        return $this->belongsTo(PayCycle::class);
    }
}
