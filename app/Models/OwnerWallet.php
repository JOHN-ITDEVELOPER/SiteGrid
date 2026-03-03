<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OwnerWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Get the owner that owns the wallet.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all transactions for this wallet.
     */
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    /**
     * Credit the wallet (add money).
     */
    public function credit($amount, $referenceType = null, $referenceId = null, $description = null)
    {
        return DB::transaction(function () use ($amount, $referenceType, $referenceId, $description) {
            $balanceBefore = $this->balance;
            $this->balance += $amount;
            $this->save();

            return $this->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description ?? "Wallet credited with KES {$amount}",
            ]);
        });
    }

    /**
     * Debit the wallet (deduct money).
     */
    public function debit($amount, $referenceType = null, $referenceId = null, $description = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        return DB::transaction(function () use ($amount, $referenceType, $referenceId, $description) {
            $balanceBefore = $this->balance;
            $this->balance -= $amount;
            $this->save();

            return $this->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $this->balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description ?? "Wallet debited with KES {$amount}",
            ]);
        });
    }

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance($amount)
    {
        return $this->balance >= $amount;
    }
}
