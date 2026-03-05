<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Auto-create pay cycles daily at 2 AM
        $schedule->command('paycycles:auto-create')
            ->dailyAt('02:00')
            ->name('auto-create-paycycles')
            ->onFailure(function () {
                \Log::warning('PayCycle auto-creation command failed');
            })
            ->onSuccess(function () {
                \Log::info('PayCycle auto-creation command completed successfully');
            });

        // Generate weekly invoices for the last completed week every Monday
        $schedule->command('invoices:generate-weekly --period=last')
            ->weeklyOn(1, '02:15')
            ->name('generate-weekly-invoices')
            ->onFailure(function () {
                \Log::warning('Weekly invoice generation command failed');
            })
            ->onSuccess(function () {
                \Log::info('Weekly invoice generation command completed successfully');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
