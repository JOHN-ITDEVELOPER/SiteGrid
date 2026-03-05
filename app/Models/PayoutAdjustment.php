<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_id',
        'original_amount',
        'adjusted_amount',
        'difference',
        'reason',
        'adjusted_by',
        'adjusted_at',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'adjusted_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'adjusted_at' => 'datetime',
    ];

    /**
     * Get the payout that was adjusted.
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    /**
     * Get the user who made the adjustment.
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
