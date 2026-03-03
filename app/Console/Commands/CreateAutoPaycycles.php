<?php

namespace App\Console\Commands;

use App\Models\PayCycle;
use App\Models\Site;
use Illuminate\Console\Command;

class CreateAutoPaycycles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paycycles:auto-create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-create next pay cycles for sites with recurring patterns when previous cycle is paid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find pay cycles that need auto-creation
        $eligibleCycles = PayCycle::where('status', 'paid')
            ->whereNotNull('recurrence_pattern')
            ->where('is_auto_generated', false)
            ->where(function ($query) {
                $query->whereNull('next_cycle_date')
                    ->orWhereDate('next_cycle_date', '<=', now());
            })
            ->get();

        if ($eligibleCycles->isEmpty()) {
            $this->info('No pay cycles eligible for auto-creation at this time.');
            return Command::SUCCESS;
        }

        $createdCount = 0;

        foreach ($eligibleCycles as $previousCycle) {
            try {
                // Calculate new dates based on recurrence pattern
                $newStartDate = \Carbon\Carbon::parse($previousCycle->end_date)->addDays(3);
                $cycleDuration = \Carbon\Carbon::parse($previousCycle->start_date)
                    ->diffInDays(\Carbon\Carbon::parse($previousCycle->end_date));

                $newEndDate = $newStartDate->copy()->addDays($cycleDuration);

                // Create new pay cycle
                $newCycle = PayCycle::create([
                    'site_id' => $previousCycle->site_id,
                    'start_date' => $newStartDate->toDateString(),
                    'end_date' => $newEndDate->toDateString(),
                    'status' => 'open',
                    'total_amount' => 0,
                    'worker_count' => 0,
                    'recurrence_pattern' => $previousCycle->recurrence_pattern,
                    'is_auto_generated' => true,
                ]);

                // Calculate next cycle date
                $nextDate = null;
                if (!empty($previousCycle->recurrence_pattern)) {
                    match($previousCycle->recurrence_pattern) {
                        'weekly' => $nextDate = $newEndDate->copy()->addDays(3)->toDateString(), // Fri + 3 = Mon
                        'bi-weekly' => $nextDate = $newEndDate->copy()->addDays(10)->toDateString(), // +10 days
                        'monthly' => $nextDate = $newEndDate->copy()->addMonths(1)->toDateString(), // Same day next month
                    };
                }
                
                $newCycle->update(['next_cycle_date' => $nextDate]);

                // Optional: Notify owner
                // $owner = $previousCycle->site->owner;
                // Notification::send($owner, new PayCycleAutoCreatedNotification($newCycle));

                $this->info("✓ Auto-created pay cycle for Site ID {$previousCycle->site_id}: {$newStartDate->format('Y-m-d')} to {$newEndDate->format('Y-m-d')}");
                $createdCount++;

            } catch (\Exception $e) {
                $this->error("✗ Failed to auto-create pay cycle for Site ID {$previousCycle->site_id}: {$e->getMessage()}");
                \Log::error('PayCycle auto-creation failed', [
                    'previous_cycle_id' => $previousCycle->id,
                    'site_id' => $previousCycle->site_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Completed: {$createdCount} pay cycle(s) auto-created successfully.");
        return Command::SUCCESS;
    }
}
