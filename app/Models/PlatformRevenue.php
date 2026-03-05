<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformRevenue extends Model
{
    use HasFactory;

    protected $table = 'platform_revenue';

    protected $fillable = [
        'invoice_id',
        'mpesa_transaction_id',
        'amount',
        'currency',
        'mpesa_receipt',
        'platform_account_id',
        'destination_shortcode',
        'status',
        'received_at',
        'reconciled_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'received_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    /**
     * Get the invoice for this revenue.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the M-Pesa transaction.
     */
    public function mpesaTransaction(): BelongsTo
    {
        return $this->belongsTo(MpesaTransaction::class, 'mpesa_transaction_id');
    }

    /**
     * Get the platform account that received this revenue.
     */
    public function platformAccount(): BelongsTo
    {
        return $this->belongsTo(PlatformAccount::class, 'platform_account_id');
    }

    /**
     * Scope: Get received payments.
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope: Get reconciled revenue.
     */
    public function scopeReconciled($query)
    {
        return $query->where('status', 'reconciled');
    }

    /**
     * Mark as received with M-Pesa details.
     */
    public function markAsReceived($mpesaReceipt, $metadata = [])
    {
        $this->update([
            'status' => 'received',
            'mpesa_receipt' => $mpesaReceipt,
            'received_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    /**
     * Mark as reconciled (matched to bank statement, etc).
     */
    public function markAsReconciled($notes = null)
    {
        $this->update([
            'status' => 'reconciled',
            'reconciled_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed($reason)
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }
}
