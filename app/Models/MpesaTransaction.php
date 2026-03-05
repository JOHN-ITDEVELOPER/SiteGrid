<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'merchant_request_id',
        'checkout_request_id',
        'conversation_id',
        'originator_conversation_id',
        'mpesa_receipt_number',
        'phone_number',
        'amount',
        'result_code',
        'result_description',
        'status',
        'related_model',
        'related_id',
        'platform_account_id',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_response' => 'array',
    ];

    /**
     * Mark transaction as completed.
     */
    public function markAsCompleted($receipt, $resultDescription = null)
    {
        $this->update([
            'status' => 'completed',
            'mpesa_receipt_number' => $receipt,
            'result_code' => '0',
            'result_description' => $resultDescription ?? 'Transaction completed successfully',
        ]);
    }

    /**
     * Mark transaction as failed.
     */
    public function markAsFailed($resultCode, $resultDescription)
    {
        $this->update([
            'status' => 'failed',
            'result_code' => $resultCode,
            'result_description' => $resultDescription,
        ]);
    }

    /**
     * Get the platform account that processed this transaction.
     */
    public function platformAccount()
    {
        return $this->belongsTo(PlatformAccount::class, 'platform_account_id');
    }

    /**
     * Get the related model (polymorphic).
     */
    public function relatedModel()
    {
        if ($this->related_model && $this->related_id) {
            if (class_exists($this->related_model)) {
                return $this->related_model::find($this->related_id);
            }
        }
        return null;
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
