<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payout;
use App\Models\SiteWorker;
use App\Models\WorkerClaim;
use App\Models\OwnerWallet;
use App\Services\MpesaFeeService;
use App\Services\MpesaService;
use App\Services\WithdrawalWindowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $siteIds = SiteWorker::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->pluck('site_id');

        $availableBalance = $this->calculateAvailableBalance($user->id);

        $lastPayout = Payout::where('worker_id', $user->id)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->first();

        $pendingClaims = WorkerClaim::where('worker_id', $user->id)
            ->whereIn('status', ['pending_foreman', 'pending_owner', 'approved'])
            ->latest()
            ->limit(10)
            ->get();

        $thisWeekAttendance = Attendance::where('worker_id', $user->id)
            ->whereIn('site_id', $siteIds)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderByDesc('date')
            ->get();

        $payHistory = Payout::with('payCycle.site')
            ->where('worker_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        // Get withdrawal balance breakdown by site
        $windowService = new WithdrawalWindowService();
        $withdrawalBalancesBySite = [];
        
        foreach($user->siteWorkers()->whereNull('ended_at')->with('site')->get() as $siteWorker) {
            $site = $siteWorker->site;
            $breakdownData = $windowService->getWithdrawalBalancesByycle($user, $site);
            $withdrawalBalancesBysite[$site->id] = [
                'site' => $site,
                'current_cycle' => $breakdownData['current_cycle'],
                'previous_cycles' => $breakdownData['previous_cycles'],
                'total_available_anytime' => $breakdownData['total_available_anytime'],
                'total_current_cycle' => $breakdownData['total_current_cycle'],
            ];
        }

        return view('field.dashboard', [
            'mode' => 'worker',
            'availableBalance' => $availableBalance,
            'lastPayout' => $lastPayout,
            'pendingClaims' => $pendingClaims,
            'thisWeekAttendance' => $thisWeekAttendance,
            'payHistory' => $payHistory,
            'siteIds' => $siteIds,
            'withdrawalBalancesBySite' => $withdrawalBalancesBysite,
        ]);
    }

    public function storeClaim(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $site = \App\Models\Site::find($validated['site_id']);

        // Check if worker is assigned to this site
        $isAssigned = SiteWorker::where('user_id', $user->id)
            ->where('site_id', $validated['site_id'])
            ->whereNull('ended_at')
            ->exists();

        if (!$isAssigned) {
            abort(403, 'You are not assigned to this site.');
        }

        // ========== WITHDRAWAL WINDOW VALIDATION ==========
        $windowService = new WithdrawalWindowService();
        $validation = $windowService->validateWithdrawalRequest($user, $site, (float)$validated['requested_amount']);

        if (!$validation['can_withdraw']) {
            return back()
                ->with('error', $validation['reason'])
                ->with('next_window', $validation['next_window'] ?? null);
        }

        // If partial withdrawal from previous cycles only
        if ($validation['from_cycles'] === 'previous_only') {
            return back()
                ->with('warning', $validation['reason'])
                ->with('info', "You can request KES " . number_format($validation['max_allowed'], 2) . " from previous pay cycles right now.")
                ->withInput(['requested_amount' => $validation['max_allowed']]);
        }

        // ========== ORIGINAL CLAIM PROCESSING ==========
        $availableBalance = $this->calculateAvailableBalance($user->id);

        if ($availableBalance <= 0) {
            return back()->with('error', 'You cannot submit a withdrawal request with zero balance.');
        }

        // Check if site has auto-approval or fully automated enabled
        $payoutSettings = \App\Models\SiteSetting::where('site_id', $site->id)
            ->where('key', 'payouts')
            ->first();
        
        $fullyAutomated = $payoutSettings ? ($payoutSettings->value['fully_automated'] ?? false) : false;
        $autoApprove = $payoutSettings ? ($payoutSettings->value['auto_approve_claims'] ?? false) : false;
        
        if ($fullyAutomated) {
            // Fully automated: Skip all approvals and immediately disburse
            $status = 'approved';
            $message = 'Withdrawal request auto-approved! Payment is being processed to your M-Pesa.';
        } elseif ($autoApprove) {
            // Skip foreman only
            $status = 'pending_owner';
            $message = 'Claim submitted and forwarded directly to owner for approval.';
        } else {
            // Normal flow
            $status = 'pending_foreman';
            $message = 'Claim submitted and awaiting foreman approval.';
        }

        $claim = \App\Models\WorkerClaim::create([
            'site_id' => $validated['site_id'],
            'worker_id' => $user->id,
            'requested_amount' => $validated['requested_amount'],
            'status' => $status,
            'reason' => $validated['reason'] ?? null,
            'source' => 'web',
            'requested_at' => now(),
        ]);

        // If fully automated, trigger immediate disbursement
        if ($fullyAutomated) {
                try {
                    $this->processFullyAutomatedWithdrawal($claim, $site);
                    return back()->with('success', $message);
                } catch (\Exception $e) {
                    // Revert claim status to pending_foreman if disbursement fails
                    $claim->status = 'rejected';
                    $claim->rejection_reason = 'Auto-disbursement failed: ' . $e->getMessage();
                    $claim->rejected_by = null;
                    $claim->save();
                    return back()->with('error', 'Withdrawal request failed: ' . $e->getMessage());
                }
            }

            return back()->with('success', $message);
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

    public function claimsIndex(): View
    {
        $user = auth()->user();

        $availableBalance = $this->calculateAvailableBalance($user->id);

        $claims = WorkerClaim::where('worker_id', $user->id)
            ->latest()
            ->paginate(20);

        // Get payout window info for each site the worker is assigned to
        $windowService = new WithdrawalWindowService();
        $siteWindows = [];
        
        foreach($user->siteWorkers()->whereNull('ended_at')->with('site')->get() as $siteWorker) {
            $site = $siteWorker->site;
            $windowCheck = $windowService->isWithinWithdrawalWindow($site);
            $siteWindows[$site->id] = [
                'site' => $site,
                'is_within_window' => $windowCheck['allowed'],
                'window_message' => $windowCheck['reason'],
                'next_window' => $windowCheck['next_window'] ?? null,
                'settings' => $site->getPayoutSettings(),
            ];
        }

        return view('field.claims', [
            'claims' => $claims,
            'availableBalance' => $availableBalance,
            'siteWindows' => $siteWindows,
        ]);
    }

    public function attendanceIndex(): View
    {
        $user = auth()->user();
        $siteIds = SiteWorker::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->pluck('site_id');

        $attendance = Attendance::where('worker_id', $user->id)
            ->whereIn('site_id', $siteIds)
            ->orderByDesc('date')
            ->paginate(30);

        return view('field.attendance', [
            'attendance' => $attendance,
        ]);
    }

    public function payHistoryIndex(): View
    {
        $user = auth()->user();

        $payHistory = Payout::with('payCycle.site')
            ->where('worker_id', $user->id)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->paginate(20);

        return view('field.payhistory', [
            'payHistory' => $payHistory,
        ]);
    }

    public function settingsIndex(): View
    {
        $user = auth()->user();

        return view('field.settings', [
            'user' => $user,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'notification_preferences' => ['nullable', 'array'],
        ]);

        auth()->user()->update($validated);

        return back()->with('success', 'Settings updated successfully.');
    }

    private function calculateAvailableBalance(int $workerId): float
    {
        $earnedUnpaidAmount = (float) Payout::where('worker_id', $workerId)
            ->whereIn('status', ['pending', 'approved', 'queued'])
            ->sum('net_amount');

        $reservedClaimAmount = (float) WorkerClaim::where('worker_id', $workerId)
            ->whereIn('status', ['pending_foreman', 'pending_owner', 'approved', 'processing'])
            ->sum('requested_amount');

        return max(0, $earnedUnpaidAmount - $reservedClaimAmount);
    }
}

