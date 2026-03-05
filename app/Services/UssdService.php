<?php

namespace App\Services;

use App\Models\User;
use App\Models\Site;
use App\Models\SiteWorker;
use App\Models\WorkerClaim;
use App\Models\Attendance;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UssdService
{
    private const SESSION_PREFIX = 'ussd_session_';
    private const SESSION_TIMEOUT = 120; // 2 minutes

    /**
     * Handle incoming USSD request
     */
    public function handleRequest(string $sessionId, string $phoneNumber, string $text): array
    {
        // Find worker by phone using flexible format matching (+254 / 254 / 07)
        $worker = $this->findWorkerByPhone($phoneNumber);

        if (!$worker) {
            return $this->endSession("Welcome to SiteGrid. Your phone number is not registered. Please contact your site manager.");
        }

        // Get or create session state
        $state = $this->getSessionState($sessionId);
        
        // Parse and normalize cumulative USSD input levels
        $levels = $this->parseLevels($text);

        // Route to appropriate menu handler
        return $this->routeRequest($worker, $sessionId, $levels, $state);
    }

    /**
     * Parse cumulative USSD text and support back/home navigation.
     * Africa's Talking sends cumulative input like: 1*0*3
     * In that case, treat it as selecting "3" from main menu.
     */
    private function parseLevels(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $levels = array_values(array_filter(explode('*', trim($text)), fn ($value) => $value !== ''));

        if (empty($levels)) {
            return [];
        }

        // Global home shortcut: 00
        $homeIndex = array_search('00', $levels, true);
        if ($homeIndex !== false) {
            $levels = array_slice($levels, $homeIndex + 1);
            return $levels ?: [];
        }

        // Back handling: keep only entries after the last explicit back token
        $backIndexes = array_keys($levels, '0', true);
        if (!empty($backIndexes)) {
            $lastBackIndex = end($backIndexes);
            $levels = array_slice($levels, $lastBackIndex + 1);
            return $levels ?: [];
        }

        return $levels;
    }

    /**
     * Route request based on menu level
     */
    private function routeRequest(User $worker, string $sessionId, array $levels, array $state): array
    {
        $level = count($levels);

        // Level 0: Main menu
        if ($level === 0) {
            return $this->showMainMenu($worker);
        }

        $choice = $levels[0];

        // Level 1: Main menu choices
        switch ($choice) {
            case '1':
                return $this->handleBalanceMenu($worker, $levels, $sessionId, $state);
            case '2':
                return $this->handleClaimPayMenu($worker, $levels, $sessionId, $state);
            case '3':
                return $this->handleAttendanceMenu($worker, $levels, $sessionId, $state);
            case '4':
                return $this->handleHelpMenu($worker, $levels);
            default:
                return $this->endSession("Invalid option. Please try again.");
        }
    }

    /**
     * Show main menu
     */
    private function showMainMenu(User $worker): array
    {
        $message = "Welcome to SiteGrid, {$worker->name}\n\n";
        $message .= "1. My Balance\n";
        $message .= "2. Claim Pay\n";
        $message .= "3. Attendance Summary\n";
        $message .= "4. Help";

        return $this->continueSession($message);
    }

    /**
     * Handle balance menu
     */
    private function handleBalanceMenu(User $worker, array $levels, string $sessionId, array $state): array
    {
        if (count($levels) === 1) {
            // Get active sites for this worker
            $activeSites = SiteWorker::where('user_id', $worker->id)
                ->whereNull('ended_at')
                ->with('site')
                ->get();

            if ($activeSites->isEmpty()) {
                return $this->endSession("You are not assigned to any active sites.");
            }

            $message = "YOUR BALANCE\n\n";
            
            foreach ($activeSites as $assignment) {
                $site = $assignment->site;
                
                // Calculate available balance
                $balance = $this->calculateWorkerBalance($worker->id, $site->id);
                
                $message .= "{$site->name}\n";
                $message .= "Available: KES " . number_format($balance, 2) . "\n\n";
            }

            $message .= "\n0. Back to Menu";

            return $this->continueSession($message);
        }

        // Return to main menu
        if (count($levels) === 2 && $levels[1] === '0') {
            return $this->showMainMenu($worker);
        }

        return $this->endSession("Thank you for using SiteGrid.");
    }

    /**
     * Handle claim pay menu
     */
    private function handleClaimPayMenu(User $worker, array $levels, string $sessionId, array $state): array
    {
        // Level 1: Show sites
        if (count($levels) === 1) {
            $activeSites = SiteWorker::where('user_id', $worker->id)
                ->whereNull('ended_at')
                ->with('site')
                ->get();

            if ($activeSites->isEmpty()) {
                return $this->endSession("You are not assigned to any active sites.");
            }

            $windowService = new WithdrawalWindowService();

            // Store sites in session
            $siteOptions = [];
            $message = "CLAIM PAY - Select Site\n\n";
            
            foreach ($activeSites as $index => $assignment) {
                $site = $assignment->site;
                $balances = $windowService->getWithdrawalBalancesByycle($worker, $site);
                $previousAnytime = (float) ($balances['total_available_anytime'] ?? 0);
                $currentCycleBalance = (float) ($balances['total_current_cycle'] ?? 0);
                $inWindow = (bool) ($balances['current_cycle']['in_window'] ?? false);
                $balance = $inWindow ? ($previousAnytime + $currentCycleBalance) : $previousAnytime;
                
                $optionNum = $index + 1;
                $siteOptions[$optionNum] = $site->id;
                
                $message .= "{$optionNum}. {$site->name}\n";
                $message .= "   KES " . number_format($balance, 2) . "\n\n";
            }

            $message .= "0. Cancel";

            $this->saveSessionState($sessionId, [
                'site_options' => $siteOptions,
            ]);

            return $this->continueSession($message);
        }

        // Level 2: Site selected, show amount input
        if (count($levels) === 2) {
            $siteChoice = $levels[1];

            if ($siteChoice === '0') {
                return $this->showMainMenu($worker);
            }

            $siteOptions = $state['site_options'] ?? [];
            $siteId = $siteOptions[$siteChoice] ?? null;

            if (!$siteId) {
                return $this->endSession("Invalid site selection.");
            }

            $site = Site::find($siteId);
            $windowService = new WithdrawalWindowService();
            $balances = $windowService->getWithdrawalBalancesByycle($worker, $site);

            $previousAnytime = (float) ($balances['total_available_anytime'] ?? 0);
            $currentCycleBalance = (float) ($balances['total_current_cycle'] ?? 0);
            $inWindow = (bool) ($balances['current_cycle']['in_window'] ?? false);
            $windowReason = (string) ($balances['current_cycle']['window_reason'] ?? 'Withdrawal window is closed');

            $balance = $inWindow ? ($previousAnytime + $currentCycleBalance) : $previousAnytime;

            if ($balance <= 0) {
                return $this->endSession("No balance available for withdrawal from {$site->name}. {$windowReason}");
            }

            $message = "CLAIM PAY - {$site->name}\n\n";
            $message .= "Available: KES " . number_format($balance, 2) . "\n\n";

            if (!$inWindow) {
                $message .= "Note: Window closed. You can withdraw previous cycle balance only.\n\n";
            }

            $message .= "Enter amount to claim:\n";
            $message .= "(or 0 to cancel)";

            $this->saveSessionState($sessionId, [
                'site_id' => $siteId,
                'max_amount' => $balance,
            ]);

            return $this->continueSession($message);
        }

        // Level 3: Amount entered, confirm
        if (count($levels) === 3) {
            $amount = $levels[2];

            if ($amount === '0') {
                return $this->showMainMenu($worker);
            }

            if (!is_numeric($amount) || $amount <= 0) {
                return $this->endSession("Invalid amount. Please try again.");
            }

            $siteId = $state['site_id'] ?? null;
            $maxAmount = $state['max_amount'] ?? 0;

            if ($amount > $maxAmount) {
                return $this->endSession("Amount exceeds available balance. Max: KES " . number_format($maxAmount, 2));
            }

            $site = Site::find($siteId);
            $windowService = new WithdrawalWindowService();
            $validation = $windowService->validateWithdrawalRequest($worker, $site, (float) $amount);

            if (!$validation['can_withdraw']) {
                return $this->endSession("Withdrawal blocked: " . $validation['reason']);
            }

            if (($validation['from_cycles'] ?? null) === 'previous_only') {
                $maxAllowed = (float) ($validation['max_allowed'] ?? 0);
                return $this->endSession(
                    "Window closed for current cycle. Max you can claim now is KES " .
                    number_format($maxAllowed, 2) .
                    ". Please retry with that amount or wait for window opening."
                );
            }

            $message = "CONFIRM WITHDRAWAL\n\n";
            $message .= "Site: {$site->name}\n";
            $message .= "Amount: KES " . number_format($amount, 2) . "\n\n";
            $message .= "1. Confirm\n";
            $message .= "0. Cancel";

            $this->saveSessionState($sessionId, [
                'site_id' => $siteId,
                'amount' => $amount,
            ]);

            return $this->continueSession($message);
        }

        // Level 4: Confirmation
        if (count($levels) === 4) {
            $confirmation = $levels[3];

            if ($confirmation === '0') {
                return $this->endSession("Withdrawal cancelled.");
            }

            if ($confirmation !== '1') {
                return $this->endSession("Invalid option.");
            }

            // Create worker claim
            $siteId = $state['site_id'] ?? null;
            $amount = $state['amount'] ?? 0;

            try {
                $site = Site::find($siteId);
                $windowService = new WithdrawalWindowService();
                $validation = $windowService->validateWithdrawalRequest($worker, $site, (float) $amount);

                if (!$validation['can_withdraw'] || ($validation['from_cycles'] ?? null) === 'previous_only') {
                    return $this->endSession("Withdrawal blocked: " . ($validation['reason'] ?? 'Outside payout window'));
                }

                $claim = WorkerClaim::create([
                    'site_id' => $siteId,
                    'worker_id' => $worker->id,
                    'requested_amount' => $amount,
                    'status' => 'pending_owner',
                    'source' => 'ussd',
                    'reason' => 'USSD withdrawal request',
                    'requested_at' => now(),
                ]);

                // Check if site has auto-disburse enabled
                $settings = $site->getPayoutSettings();
                $autoDisburse = $settings['auto_disburse'] ?? false;

                if ($autoDisburse) {
                    $message = "SUCCESS!\n\n";
                    $message .= "Withdrawal processing...\n";
                    $message .= "Amount: KES " . number_format($amount, 2) . "\n";
                    $message .= "You'll receive payment shortly.\n\n";
                    $message .= "Ref: #" . $claim->id;
                } else {
                    $message = "SUCCESS!\n\n";
                    $message .= "Withdrawal request submitted.\n";
                    $message .= "Amount: KES " . number_format($amount, 2) . "\n";
                    $message .= "Awaiting approval.\n\n";
                    $message .= "Ref: #" . $claim->id;
                }

                // Clear session
                $this->clearSessionState($sessionId);

                return $this->endSession($message);
            } catch (\Exception $e) {
                \Log::error('USSD claim creation failed: ' . $e->getMessage());
                return $this->endSession("Failed to process request. Please try again or contact support.");
            }
        }

        return $this->endSession("Invalid request.");
    }

    /**
     * Handle attendance summary menu
     */
    private function handleAttendanceMenu(User $worker, array $levels, string $sessionId, array $state): array
    {
        if (count($levels) === 1) {
            $activeSites = SiteWorker::where('user_id', $worker->id)
                ->whereNull('ended_at')
                ->with('site')
                ->get();

            if ($activeSites->isEmpty()) {
                return $this->endSession("You are not assigned to any active sites.");
            }

            $message = "ATTENDANCE SUMMARY\n\n";
            
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();

            foreach ($activeSites as $assignment) {
                $site = $assignment->site;
                
                // Count attendance this week
                $daysWorked = Attendance::where('worker_id', $worker->id)
                    ->where('site_id', $site->id)
                    ->whereBetween('date', [$weekStart, $weekEnd])
                    ->where('is_present', true)
                    ->count();

                // Get last pay cycle info
                $lastPayout = WorkerClaim::where('worker_id', $worker->id)
                    ->where('site_id', $site->id)
                    ->where('status', 'paid')
                    ->latest('paid_at')
                    ->first();

                $message .= "{$site->name}\n";
                $message .= "This Week: {$daysWorked} days\n";
                
                if ($lastPayout) {
                    $lastPaidDate = $lastPayout->paid_at ? $lastPayout->paid_at->format('M d') : 'N/A';
                    $message .= "Last Paid: " . $lastPaidDate . "\n";
                    $message .= "Amount: KES " . number_format($lastPayout->requested_amount, 2) . "\n";
                }

                $message .= "\n";
            }

            $message .= "0. Back to Menu";

            return $this->continueSession($message);
        }

        // Return to main menu
        if (count($levels) === 2 && $levels[1] === '0') {
            return $this->showMainMenu($worker);
        }

        return $this->endSession("Thank you for using SiteGrid.");
    }

    /**
     * Handle help menu
     */
    private function handleHelpMenu(User $worker, array $levels): array
    {
        if (count($levels) === 1) {
            $message = "HELP\n\n";
            $message .= "SiteGrid Help:\n";
            $message .= "- Balance: Check available funds\n";
            $message .= "- Claim Pay: Request withdrawal\n";
            $message .= "- Attendance: View work summary\n\n";
            $message .= "Support: 0757886522\n";
            $message .= "Email: support@sitegrid.co.ke\n\n";
            $message .= "0. Back to Menu";

            return $this->continueSession($message);
        }

        // Return to main menu
        if (count($levels) === 2 && $levels[1] === '0') {
            return $this->showMainMenu($worker);
        }

        return $this->endSession("Thank you for using SiteGrid.");
    }

    /**
     * Calculate worker's available balance for a site
     */
    private function calculateWorkerBalance(int $workerId, int $siteId): float
    {
        $assignment = SiteWorker::where('site_id', $siteId)
            ->where('user_id', $workerId)
            ->whereNull('ended_at')
            ->first();

        if (!$assignment) {
            return 0;
        }

        $daysWorked = Attendance::where('worker_id', $workerId)
            ->where('site_id', $siteId)
            ->where('is_present', true)
            ->count();

        $dailyRate = (float) ($assignment->daily_rate ?? 0);
        $totalEarned = $daysWorked * $dailyRate;

        // Subtract pending claims
        $pendingClaims = WorkerClaim::where('worker_id', $workerId)
            ->where('site_id', $siteId)
            ->whereIn('status', ['pending_owner', 'pending_foreman', 'approved', 'processing'])
            ->sum('requested_amount');

        return max(0, $totalEarned - $pendingClaims);
    }

    /**
     * Normalize phone number to 254XXXXXXXXX format
     */
    private function normalizePhone(string $phone): string
    {
        // Remove any spaces, hyphens, or special characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Convert to 254 format
        if (str_starts_with($phone, '+254')) {
            return substr($phone, 1);
        } elseif (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '254')) {
            return $phone;
        }

        // If it's just 9 digits (without country code)
        if (strlen($phone) === 9) {
            return '254' . $phone;
        }

        return $phone;
    }

    /**
     * Find worker by trying equivalent phone formats.
     */
    private function findWorkerByPhone(string $incomingPhone): ?User
    {
        $normalized = $this->normalizePhone($incomingPhone); // 2547XXXXXXXX

        $candidates = array_values(array_unique(array_filter([
            $normalized,
            '+' . $normalized,
            preg_match('/^254\d{9}$/', $normalized) ? '0' . substr($normalized, 3) : null,
            $incomingPhone,
        ])));

        return User::whereIn('phone', $candidates)->first();
    }

    /**
     * Continue USSD session (CON response)
     */
    private function continueSession(string $message): array
    {
        return [
            'response' => 'CON ' . $message,
            'type' => 'continue',
        ];
    }

    /**
     * End USSD session (END response)
     */
    private function endSession(string $message): array
    {
        return [
            'response' => 'END ' . $message,
            'type' => 'end',
        ];
    }

    /**
     * Get session state from cache
     */
    private function getSessionState(string $sessionId): array
    {
        return Cache::get(self::SESSION_PREFIX . $sessionId, []);
    }

    /**
     * Save session state to cache
     */
    private function saveSessionState(string $sessionId, array $state): void
    {
        Cache::put(
            self::SESSION_PREFIX . $sessionId,
            array_merge($this->getSessionState($sessionId), $state),
            self::SESSION_TIMEOUT
        );
    }

    /**
     * Clear session state
     */
    private function clearSessionState(string $sessionId): void
    {
        Cache::forget(self::SESSION_PREFIX . $sessionId);
    }
}
