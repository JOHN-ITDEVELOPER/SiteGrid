<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayCycle;
use App\Models\Payout;
use App\Models\SiteWorker;
use App\Models\WorkerClaim;
use App\Models\OwnerWallet;
use App\Services\MpesaFeeService;
use App\Services\MpesaService;
use App\Models\WorkerKycSubmission;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function balance(Request $request, int $id)
    {
        $this->assertSelf($request, $id);

        $availableBalance = Payout::where('worker_id', $id)
            ->whereIn('status', ['approved', 'queued'])
            ->sum('net_amount');

        $lastPayout = Payout::where('worker_id', $id)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->first();

        $pendingClaims = WorkerClaim::where('worker_id', $id)
            ->whereIn('status', ['pending_foreman', 'pending_owner', 'approved'])
            ->count();

        return response()->json([
            'worker_id' => $id,
            'available_balance' => (float) $availableBalance,
            'last_payout' => $lastPayout ? [
                'date' => optional($lastPayout->paid_at)?->toDateTimeString(),
                'amount' => (float) $lastPayout->net_amount,
                'transaction_ref' => $lastPayout->transaction_ref,
            ] : null,
            'pending_claims' => $pendingClaims,
        ]);
    }

    public function createClaim(Request $request, int $id)
    {
        $this->assertSelf($request, $id);

        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', 'in:web,api,ussd'],
        ]);

        $availableBalance = Payout::where('worker_id', $id)
            ->whereIn('status', ['approved', 'queued'])
            ->sum('net_amount');

        if ($availableBalance <= 0) {
            return response()->json([
                'message' => 'You cannot submit a withdrawal request with zero balance.',
            ], 422);
        }

        $assigned = SiteWorker::where('user_id', $id)
            ->where('site_id', $validated['site_id'])
            ->whereNull('ended_at')
            ->exists();

        if (!$assigned) {
            abort(403, 'Worker not assigned to this site.');
        }

        // Check if site has auto-approval enabled
        $site = \App\Models\Site::find($validated['site_id']);
        $payoutSettings = \App\Models\SiteSetting::where('site_id', $site->id)
            ->where('key', 'payouts')
            ->first();
        
        $fullyAutomated = $payoutSettings ? ($payoutSettings->value['fully_automated'] ?? false) : false;
        $autoApprove = $payoutSettings ? ($payoutSettings->value['auto_approve_claims'] ?? false) : false;
        
        if ($fullyAutomated) {
            $status = 'approved';
            $message = 'Withdrawal request auto-approved! Payment is being processed to your M-Pesa.';
        } elseif ($autoApprove) {
            $status = 'pending_owner';
            $message = 'Claim submitted and forwarded directly to owner for approval.';
        } else {
            $status = 'pending_foreman';
            $message = 'Claim submitted and awaiting foreman approval.';
        }

        $claim = WorkerClaim::create([
            'site_id' => $validated['site_id'],
            'worker_id' => $id,
            'requested_amount' => $validated['requested_amount'],
            'status' => $status,
            'reason' => $validated['reason'] ?? null,
            'source' => $validated['source'] ?? 'api',
            'requested_at' => now(),
        ]);

        // If fully automated, trigger immediate disbursement
        if ($fullyAutomated) {
            try {
                $this->processFullyAutomatedWithdrawal($claim, $site);
                return response()->json([
                    'message' => $message,
                    'claim' => $claim->fresh(),
                    'status' => 'processing',
                ], 201);
            } catch (\Exception $e) {
                // Revert claim status if disbursement fails
                $claim->status = 'rejected';
                $claim->rejection_reason = 'Auto-disbursement failed: ' . $e->getMessage();
                $claim->rejected_by = null;
                $claim->save();
                return response()->json([
                    'message' => 'Withdrawal request failed: ' . $e->getMessage(),
                    'claim' => $claim,
                ], 422);
            }
        }

        return response()->json([
            'message' => $message,
            'claim' => $claim,
        ], 201);
    }

    protected function processFullyAutomatedWithdrawal(WorkerClaim $claim, $site)
    {
        // Get owner wallet
        $owner = $site->owner;
        $wallet = OwnerWallet::where('owner_id', $owner->id)->first();
        
        if (!$wallet) {
            throw new \Exception('Owner wallet not found. Please contact site administrator.');
        }

        $feeBreakdown = app(MpesaFeeService::class)->resolveB2CFee($claim->requested_amount, $claim->worker->phone);
        $mpesaFee = $feeBreakdown['fee'];
        $totalOwnerCost = $claim->requested_amount + $mpesaFee;

        // Check wallet balance (worker amount + M-Pesa transfer fee)
        if (!$wallet->hasSufficientBalance($totalOwnerCost)) {
            throw new \Exception("Insufficient owner wallet balance. Required: KES {$totalOwnerCost}, Available: KES {$wallet->balance}. Please contact site administrator.");
        }

        // Deduct from wallet
        $wallet->debit(
            $totalOwnerCost,
            'WorkerClaim',
            $claim->id,
            "Auto-disbursed withdrawal for {$claim->worker->name} - Worker amount KES {$claim->requested_amount} + M-Pesa fee KES {$mpesaFee} - Site: {$site->name}"
        );

        // Initiate M-Pesa B2C payment
        $mpesaService = new MpesaService();
        $workerPhone = $claim->worker->phone;
        
        // Ensure phone is in correct format (254XXXXXXXXX)
        if (!str_starts_with($workerPhone, '254')) {
            $workerPhone = preg_replace('/^(\+?254|0)/', '254', $workerPhone);
        }

        $result = $mpesaService->b2c(
            $workerPhone,
            $claim->requested_amount,
            $claim->id,
            'App\\Models\\WorkerClaim'
        );

        if (!$result['success']) {
            // Refund wallet if B2C initiation fails
            $wallet->credit(
                $totalOwnerCost,
                'WorkerClaim',
                $claim->id,
                "Refund: Auto-disbursement failed for {$claim->worker->name}"
            );
            throw new \Exception($result['message'] ?? 'M-Pesa payment initiation failed');
        }

        // Update claim with transaction details
        $claim->status = 'processing'; // Will be updated to 'paid' by callback
        $claim->transaction_ref = $result['transaction_id'] ?? null;
        $claim->approved_by_owner = $site->owner_id;
        $claim->approved_at = now();
        $claim->save();
    }

    public function claims(Request $request, int $id)
    {
        $this->assertSelf($request, $id);

        $claims = WorkerClaim::with('site:id,name')
            ->where('worker_id', $id)
            ->latest('requested_at')
            ->paginate(20);

        return response()->json($claims);
    }

    public function paycycles(Request $request, int $id)
    {
        $this->assertSelf($request, $id);

        $payCycles = PayCycle::whereHas('payouts', function ($query) use ($id) {
                $query->where('worker_id', $id);
            })
            ->with(['site:id,name', 'payouts' => function ($query) use ($id) {
                $query->where('worker_id', $id)
                    ->select('id', 'pay_cycle_id', 'worker_id', 'gross_amount', 'platform_fee', 'mpesa_fee', 'net_amount', 'status', 'paid_at', 'transaction_ref');
            }])
            ->latest('end_date')
            ->paginate(20);

        return response()->json($payCycles);
    }

    public function uploadKyc(Request $request, int $id)
    {
        $this->assertSelf($request, $id);

        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'document_type' => ['nullable', 'string', 'max:50'],
        ]);

        $path = $request->file('document')->store('kyc-documents', 'public');

        $submission = WorkerKycSubmission::create([
            'user_id' => $id,
            'document_type' => $validated['document_type'] ?? 'id_card',
            'document_path' => $path,
            'status' => 'pending',
        ]);

        $request->user()->update(['kyc_status' => 'pending']);

        return response()->json([
            'message' => 'KYC document uploaded successfully.',
            'submission' => $submission,
        ], 201);
    }

    private function assertSelf(Request $request, int $workerId): void
    {
        if ((int) $request->user()->id !== $workerId) {
            abort(403, 'You can only access your own worker resources.');
        }
    }
}
