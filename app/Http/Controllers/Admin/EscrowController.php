<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\ActivityLog;
use App\Models\OwnerWallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EscrowController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'overview');

        $query = Payout::whereIn('escrow_status', ['held', 'disputed']);

        if ($request->filled('escrow_status')) {
            $query->where('escrow_status', $request->escrow_status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reference', 'like', '%' . $request->search . '%')
                  ->orWhereHas('worker', function ($q2) use ($request) {
                      $q2->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $escrows = $query->with(['worker', 'payCycle.site'])->latest()->paginate(30);

        // Stats for held/disputed
        $stats = [
            'total_held' => Payout::where('escrow_status', 'held')->sum('net_amount'),
            'total_disputed' => Payout::where('escrow_status', 'disputed')->sum('net_amount'),
            'count_held' => Payout::where('escrow_status', 'held')->count(),
            'count_disputed' => Payout::where('escrow_status', 'disputed')->count(),
        ];

        // Overview data
        $totalEscrowLiquidity = OwnerWallet::sum('balance');
        $systemStats = [
            'total_liquidity' => $totalEscrowLiquidity,
            'total_owners' => OwnerWallet::count(),
            'total_transactions' => WalletTransaction::count(),
            'total_held_payouts' => Payout::where('escrow_status', 'held')->sum('net_amount'),
            'total_disputed_payouts' => Payout::where('escrow_status', 'disputed')->sum('net_amount'),
        ];

        // Owners with their details
        $owners = OwnerWallet::with('owner')
            ->latest()
            ->get()
            ->map(function ($wallet) {
                $heldPayouts = Payout::whereHas('payCycle.site', function ($query) use ($wallet) {
                    $query->where('owner_id', $wallet->user_id);
                })->where('escrow_status', 'held')->sum('net_amount');

                $disputedPayouts = Payout::whereHas('payCycle.site', function ($query) use ($wallet) {
                    $query->where('owner_id', $wallet->user_id);
                })->where('escrow_status', 'disputed')->sum('net_amount');

                return [
                    'id' => $wallet->id,
                    'owner_id' => $wallet->user_id,
                    'owner_name' => $wallet->owner->name,
                    'owner_email' => $wallet->owner->email,
                    'balance' => $wallet->balance,
                    'held_amount' => $heldPayouts,
                    'disputed_amount' => $disputedPayouts,
                    'total_in_system' => $wallet->balance + $heldPayouts + $disputedPayouts,
                    'transaction_count' => $wallet->transactions()->count(),
                ];
            });

        // Transactions
        $transactions = WalletTransaction::with('wallet.owner')
            ->latest()
            ->limit(100)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'owner_name' => $transaction->wallet->owner->name,
                    'owner_email' => $transaction->wallet->owner->email,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'reference_type' => $transaction->reference_type,
                    'reference_id' => $transaction->reference_id,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                ];
            });

        return view('admin.escrow.index', compact('escrows', 'stats', 'tab', 'systemStats', 'owners', 'transactions'));
    }

    public function hold(Request $request, Payout $payout)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payout->escrow_status = 'held';
        $payout->escrow_held_at = now();
        $payout->escrow_held_by = Auth::id();
        $payout->escrow_reason = $request->reason;
        $payout->save();

        ActivityLog::create([
            'type' => 'payout_held',
            'severity' => 'warning',
            'message' => 'Payout held in escrow for ' . $payout->worker->name,
            'user_id' => Auth::id(),
            'entity_type' => 'Payout',
            'entity_id' => $payout->id,
            'meta' => [
                'worker' => $payout->worker->name,
                'amount' => $payout->net_amount,
                'reason' => $request->reason,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Payout held in escrow.');
    }

    public function release(Request $request, Payout $payout)
    {
        if (!in_array($payout->escrow_status, ['held', 'disputed'])) {
            return back()->with('error', 'Payout is not in escrow.');
        }

        $payout->escrow_status = 'released';
        $payout->escrow_released_at = now();
        
        // If payout was pending, move to processing
        if ($payout->status === 'pending') {
            $payout->status = 'processing';
        }
        
        $payout->save();

        ActivityLog::create([
            'type' => 'payout_released',
            'severity' => 'info',
            'message' => 'Payout released from escrow for ' . $payout->worker->name,
            'user_id' => Auth::id(),
            'entity_type' => 'Payout',
            'entity_id' => $payout->id,
            'meta' => [
                'worker' => $payout->worker->name,
                'amount' => $payout->net_amount,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Payout released from escrow.');
    }

    public function dispute(Request $request, Payout $payout)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payout->escrow_status = 'disputed';
        $payout->escrow_reason = $request->reason;
        $payout->save();

        ActivityLog::create([
            'type' => 'payout_disputed',
            'severity' => 'critical',
            'message' => 'Payout marked as disputed for ' . $payout->worker->name,
            'user_id' => Auth::id(),
            'entity_type' => 'Payout',
            'entity_id' => $payout->id,
            'meta' => [
                'worker' => $payout->worker->name,
                'amount' => $payout->net_amount,
                'reason' => $request->reason,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Payout marked as disputed.');
    }
}
