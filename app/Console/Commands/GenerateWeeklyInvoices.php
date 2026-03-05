<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\Invoice;
use App\Models\SiteWorker;
use App\Models\OwnerWallet;
use App\Models\PlatformSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-weekly {--period=last : Period to generate invoices for (last, current, date=YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly invoices for all sites based on active workers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $period = $this->option('period');
        $settings = PlatformSetting::firstOrCreate([]);
        
        // Determine the week to invoice
        if ($period === 'last') {
            // Last completed week (Mon-Sun)
            $weekStart = Carbon::now()->subWeek()->startOfWeek();
            $weekEnd = Carbon::now()->subWeek()->endOfWeek();
        } elseif ($period === 'current') {
            // Current week
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
        } else {
            // Specific date
            $dateValue = str_starts_with($period, 'date=')
                ? substr($period, 5)
                : $period;
            $date = Carbon::createFromFormat('Y-m-d', $dateValue);
            $weekStart = $date->startOfWeek();
            $weekEnd = $date->endOfWeek();
        }

        $this->info("Generating invoices for week: {$weekStart->format('Y-m-d')} to {$weekEnd->format('Y-m-d')}");

        $sites = Site::all();
        $invoicesCreated = 0;
        $totalAmount = 0;

        foreach ($sites as $site) {
            try {
                // Count workers who worked that week
                // Include: active workers + workers who were fired/removed mid-week but worked that week
                $workerCount = $this->countWorkersForWeek($site->id, $weekStart, $weekEnd);

                if ($workerCount == 0) {
                    $this->info("Site '{$site->name}': 0 workers - skipping invoice");
                    continue;
                }

                // Calculate amount based on platform fee configuration
                $feePerWorker = $settings->platform_fee_per_worker ?? 50;
                $feeModel = $settings->fee_model ?? 'flat';
                
                $amount = $this->calculateAmount($workerCount, $feePerWorker, $feeModel, $settings);

                // Check if site is in free trial
                $siteAge = $site->created_at->diffInWeeks(now());
                $freeTrialWorkers = $settings->free_trial_workers ?? 0;
                $freeTrialWeeks = $settings->free_trial_weeks ?? 0;
                $isInTrial = ($siteAge < $freeTrialWeeks) && ($workerCount <= $freeTrialWorkers);

                if ($isInTrial) {
                    $this->info("Site '{$site->name}': In trial period - invoice amount: KES 0.00");
                    $amount = 0;
                }

                // Check if invoice already exists for this period
                $existingInvoice = Invoice::where('site_id', $site->id)
                    ->whereDate('period_start', $weekStart->toDateString())
                    ->whereDate('period_end', $weekEnd->toDateString())
                    ->first();

                if ($existingInvoice) {
                    $this->warn("Invoice already exists for site '{$site->name}' for this period");
                    continue;
                }

                // Create invoice
                $defaultDueDays = (int) ($settings->default_invoice_due_days ?? 14);
                $dueDate = $weekEnd->copy()->addDays($defaultDueDays);
                $invoice = Invoice::create([
                    'site_id' => $site->id,
                    'period_start' => $weekStart->toDateString(),
                    'period_end' => $weekEnd->toDateString(),
                    'worker_count' => $workerCount,
                    'amount' => $amount,
                    'status' => 'unpaid',
                    'due_date' => $dueDate->toDateString(),
                    'notes' => "Weekly platform billing for {$workerCount} worker(s) @ KES {$feePerWorker}/worker",
                ]);

                // Process payment based on site's preference
                if ($amount > 0) {
                    $this->processInvoicePayment($site, $invoice);
                }

                $invoicesCreated++;
                $totalAmount += $amount;
                $this->info("✓ Invoice created for '{$site->name}': {$workerCount} workers = KES " . number_format($amount, 2));

            } catch (\Exception $e) {
                $this->error("Error processing site '{$site->name}': {$e->getMessage()}");
            }
        }

        $this->info("\n========================================");
        $this->info("Completed: {$invoicesCreated} invoices created");
        $this->info("Total Amount: KES " . number_format($totalAmount, 2));
        $this->info("========================================");

        return Command::SUCCESS;
    }

    /**
     * Count workers who worked in the given week
     * Includes active workers + workers fired/ended that week
     */
    private function countWorkersForWeek($siteId, $weekStart, $weekEnd)
    {
        return SiteWorker::where('site_id', $siteId)
            ->where(function ($query) use ($weekStart, $weekEnd) {
                // Active during week (started before week end, not ended or ended after week start)
                $query->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->where('started_at', '<=', $weekEnd)
                        ->where(function ($inner) use ($weekStart, $weekEnd) {
                            // Either no end date (still active) OR ended after week start
                            $inner->whereNull('ended_at')
                                ->orWhere('ended_at', '>=', $weekStart);
                        });
                });
            })
            ->count();
    }

    /**
     * Calculate invoice amount based on fee model
     */
    private function calculateAmount($workerCount, $feePerWorker, $feeModel, $settings)
    {
        return match($feeModel) {
            'flat' => $workerCount * $feePerWorker,
            'percentage' => 0, // Will be calculated from payroll data in future
            'hybrid' => ($workerCount * $feePerWorker) + 0, // Will add percentage in future
            default => $workerCount * $feePerWorker,
        };
    }

    /**
     * Process invoice payment based on site's payment method
     */
    private function processInvoicePayment($site, $invoice)
    {
        if ($site->invoice_payment_method === 'auto_wallet') {
            $owner = $site->owner;
            $wallet = $owner->wallet ?? OwnerWallet::firstOrCreate(
                ['user_id' => $owner->id],
                ['balance' => 0, 'currency' => 'KES']
            );

            if ($wallet->hasSufficientBalance($invoice->amount)) {
                try {
                    $wallet->debit(
                        $invoice->amount,
                        'Invoice',
                        $invoice->id,
                        "Weekly platform billing for site: {$site->name}"
                    );

                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    $this->info("   → Auto-debited from wallet: KES " . number_format($invoice->amount, 2));
                    return;
                } catch (\Exception $e) {
                    $this->warn("   → Auto-debit failed: {$e->getMessage()}");
                }
            }

            $invoice->update([
                'status' => 'unpaid',
                'payment_method' => 'manual_mpesa',
            ]);
            $this->warn("   → Insufficient wallet balance; invoice switched to manual M-Pesa for owner-triggered STK payment");
            return;
        }

        if ($site->invoice_payment_method === 'manual_mpesa') {
            $invoice->update([
                'status' => 'unpaid',
                'payment_method' => 'manual_mpesa',
            ]);
            $this->info("   → Manual M-Pesa selected; owner will trigger STK payment from invoices page");
        }
    }
}
