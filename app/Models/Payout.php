<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_cycle_id',
        'worker_id',
        'gross_amount',
        'platform_fee',
        'mpesa_fee',
        'net_amount',
        'status',
        'paid_at',
        'transaction_ref',
        'error_message',
        'mpesa_transaction_id',
        'escrow_status',
        'escrow_held_at',
        'escrow_released_at',
        'escrow_held_by',
        'escrow_reason',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'escrow_held_at' => 'datetime',
        'escrow_released_at' => 'datetime',
    ];

    // Relationships
    public function payCycle()
    {
        return $this->belongsTo(PayCycle::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function mpesaTransaction()
    {
        return $this->belongsTo(MpesaTransaction::class);
    }
}
