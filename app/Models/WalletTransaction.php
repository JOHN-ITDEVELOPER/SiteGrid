<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet()
    {
        return $this->belongsTo(OwnerWallet::class, 'wallet_id');
    }

    /**
     * Get the related model (polymorphic relationship).
     */
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            $modelClass = 'App\\Models\\' . $this->reference_type;
            if (class_exists($modelClass)) {
                return $modelClass::find($this->reference_id);
            }
        }
        return null;
    }
}
