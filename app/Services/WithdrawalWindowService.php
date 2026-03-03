<?php

namespace App\Services;

use App\Models\PayCycle;
use App\Models\Payout;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkerClaim;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WithdrawalWindowService
{
    /**
     * Check if current time is within withdrawal window for a site
     * 
     * @param Site $site
     * @return array ['allowed' => bool, 'reason' => string, 'next_window' => string]
     */
    public function isWithinWithdrawalWindow(Site $site): array
    {
        $settings = $site->getPayoutSettings();
        
        if (!($settings['enforce_windows'] ?? true)) {
            return ['allowed' => true, 'reason' => 'Window enforcement disabled'];
        }

        $window = $settings['windows'][0] ?? null;
        if (!$window) {
            return ['allowed' => true, 'reason' => 'No window configured'];
        }

        $timezone = $window['timezone'] ?? 'Africa/Nairobi';
        $now = Carbon::now($timezone);
        $allowedDays = $window['days'] ?? [];
        $windowTime = $window['time'] ?? '17:00'; // HH:mm format

        $currentDay = $now->format('D'); // Mon, Tue, Wed, etc.
        
        // Check if today is a withdrawal day
        $isWithdrawalDay = in_array($currentDay, $allowedDays);
        
        if (!$isWithdrawalDay) {
            $nextWindow = $this->getNextWindowDate($now, $allowedDays, $timezone);
            return [
                'allowed' => false,
                'reason' => 'Withdrawals only allowed on ' . implode(', ', $allowedDays),
                'next_window' => $nextWindow
            ];
        }

        // Check if current time is after window time
        $windowTimestamp = Carbon::createFromFormat('H:i', $windowTime, $timezone);
        $currentTime = $now->copy()->setHours($windowTimestamp->hour)->setMinutes($windowTimestamp->minute)->setSeconds(0);

        if ($now->isBefore($currentTime)) {
            $hoursRemaining = $now->diffInHours($currentTime, false);
            $minutesRemaining = $now->copy()->addHours($hoursRemaining)->diffInMinutes($currentTime);
            
            return [
                'allowed' => false,
                'reason' => "Withdrawal window opens at {$windowTime}. Time remaining: {$hoursRemaining}h {$minutesRemaining}m",
                'next_window' => $currentTime->toIso8601String()
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Within withdrawal window',
            'next_window' => null
        ];
    }

    /**
     * Get available balances grouped by pay cycle
     * 
     * @param User $worker
     * @param Site $site
     * @return array [
     *     'current_cycle' => ['cycle' => PayCycle, 'balance' => float, 'in_window' => bool],
     *     'previous_cycles' => [['cycle' => PayCycle, 'balance' => float], ...]
     * ]
     */
    public function getWithdrawalBalancesByycle(User $worker, Site $site): array
    {
        $windowCheck = $this->isWithinWithdrawalWindow($site);

        // Get current open cycle
        $currentCycle = PayCycle::where('site_id', $site->id)
            ->whereIn('status', ['open', 'computed'])
            ->latest('start_date')
            ->first();

        $currentCycleBalance = 0;
        $previousCyclesBalance = [];

        if ($currentCycle) {
            $currentCycleBalance = $this->calculateCycleBalance($worker->id, $currentCycle->id);
        }

        // Get all previous paid/approved cycles
        $previousCycles = PayCycle::where('site_id', $site->id)
            ->whereIn('status', ['paid', 'approved'])
            ->where('id', '!=', $currentCycle?->id)
            ->latest('end_date')
            ->get();

        foreach ($previousCycles as $cycle) {
            $balance = $this->calculateCycleBalance($worker->id, $cycle->id);
            if ($balance > 0) {
                $previousCyclesBalance[] = [
                    'cycle' => $cycle,
                    'balance' => $balance,
                ];
            }
        }

        return [
            'current_cycle' => [
                'cycle' => $currentCycle,
                'balance' => $currentCycleBalance,
                'in_window' => $windowCheck['allowed'],
                'window_reason' => $windowCheck['reason'],
                'next_window' => $windowCheck['next_window'],
            ],
            'previous_cycles' => $previousCyclesBalance,
            'total_available_anytime' => array_sum(array_column($previousCyclesBalance, 'balance')),
            'total_current_cycle' => $currentCycleBalance,
        ];
    }

    /**
     * Calculate unpaid balance for a worker in a specific pay cycle
     * (pending + approved + queued payout statuses)
     * 
     * @param int $workerId
     * @param int $payCycleId
     * @return float
     */
    private function calculateCycleBalance(int $workerId, int $payCycleId): float
    {
        return Payout::where('worker_id', $workerId)
            ->where('pay_cycle_id', $payCycleId)
            ->whereIn('status', ['pending', 'approved', 'queued'])
            ->sum('net_amount');
    }

    /**
     * Get next available withdrawal window date/time
     * 
     * @param Carbon $now
     * @param array $allowedDays
     * @param string $timezone
     * @return string ISO8601 timestamp
     */
    private function getNextWindowDate(Carbon $now, array $allowedDays, string $timezone): string
    {
        $dayMap = ['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 0];
        
        $allowedDayNums = array_map(fn($d) => $dayMap[$d], $allowedDays);
        sort($allowedDayNums);

        $currentDayNum = (int)$now->format('w');
        $nextDay = null;

        // Find next allowed day
        foreach ($allowedDayNums as $allowedNum) {
            if ($allowedNum > $currentDayNum) {
                $nextDay = $allowedNum;
                break;
            }
        }

        // If no day found this week, get first day of next week
        if (!$nextDay) {
            $nextDay = $allowedDayNums[0];
            $now = $now->addWeek();
        }

        $daysToAdd = $nextDay - $currentDayNum;
        return $now->addDays($daysToAdd)->setTime(0, 0)->toIso8601String();
    }

    /**
     * Check if worker can withdraw from current cycle
     * 
     * @param User $worker
     * @param Site $site
     * @param float $amount
     * @param bool $overrideByOwner
     * @return array ['can_withdraw' => bool, 'reason' => string, 'from_cycle' => 'current'|'previous'|'mixed']
     */
    public function validateWithdrawalRequest(User $worker, Site $site, float $amount, bool $overrideByOwner = false): array
    {
        $balances = $this->getWithdrawalBalancesByycle($worker, $site);

        // Owner override bypasses window check
        if ($overrideByOwner) {
            $totalAvailable = $balances['total_available_anytime'] + $balances['total_current_cycle'];
            if ($amount <= $totalAvailable) {
                return [
                    'can_withdraw' => true,
                    'reason' => 'Approved by owner override',
                    'from_cycles' => 'all',
                    'override' => true,
                ];
            }
            return [
                'can_withdraw' => false,
                'reason' => "Insufficient balance. Available: {$totalAvailable}",
                'from_cycles' => null,
                'override' => true,
            ];
        }

        // Check previous cycles first (anytime)
        $previousBalance = $balances['total_available_anytime'];
        if ($amount <= $previousBalance) {
            return [
                'can_withdraw' => true,
                'reason' => 'Withdrawing from previous pay cycles (no window restriction)',
                'from_cycles' => 'previous',
                'override' => false,
            ];
        }

        // If requesting more than previous cycles, check current cycle window
        $remainingNeeded = $amount - $previousBalance;
        
        if (!$balances['current_cycle']['in_window']) {
            if ($previousBalance > 0) {
                return [
                    'can_withdraw' => true,
                    'reason' => "Can withdraw KES {$previousBalance} from previous cycles. Current cycle ({$remainingNeeded} needed) requires {$balances['current_cycle']['window_reason']}",
                    'from_cycles' => 'previous_only',
                    'max_allowed' => $previousBalance,
                    'override' => false,
                ];
            }
            
            return [
                'can_withdraw' => false,
                'reason' => $balances['current_cycle']['window_reason'],
                'from_cycles' => null,
                'next_window' => $balances['current_cycle']['next_window'],
                'override' => false,
            ];
        }

        // Within window, can withdraw from both
        $totalAvailable = $balances['total_available_anytime'] + $balances['total_current_cycle'];
        if ($amount <= $totalAvailable) {
            return [
                'can_withdraw' => true,
                'reason' => 'Within withdrawal window',
                'from_cycles' => 'mixed',
                'override' => false,
            ];
        }

        return [
            'can_withdraw' => false,
            'reason' => "Insufficient balance. Available: {$totalAvailable}",
            'from_cycles' => null,
            'override' => false,
        ];
    }
}
