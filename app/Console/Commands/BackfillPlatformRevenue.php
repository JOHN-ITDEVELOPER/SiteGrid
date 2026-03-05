<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\PlatformRevenue;
use App\Models\PlatformSetting;
use Illuminate\Console\Command;

class BackfillPlatformRevenue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:platform-revenue {--simulate}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Backfill platform_revenue records for paid invoices without revenue entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $simulate = $this->option('simulate');
        
        if ($simulate) {
            $this->info('Running in SIMULATION mode - no changes will be made');
        }

        // Get invoice account ID from settings
        $settings = PlatformSetting::firstOrCreate([]);
        $invoiceAccountId = $settings->platform_invoice_account_id;

        if (!$invoiceAccountId) {
            $this->error('❌ Platform invoice account not configured in settings');
            $this->info('Please set up the Invoice Revenue account in Admin Settings → Payment Accounts');
            return 1;
        }

        // Find all paid invoices
        $paidInvoices = Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->get();

        if ($paidInvoices->isEmpty()) {
            $this->info('No paid invoices found');
            return 0;
        }

        $this->info("Found {$paidInvoices->count()} paid invoices");

        $count = 0;
        $skipped = 0;

        foreach ($paidInvoices as $invoice) {
            // Check if revenue entry already exists
            $exists = PlatformRevenue::where('invoice_id', $invoice->id)->exists();

            if ($exists) {
                $this->line("⏭️  Invoice #{$invoice->id} - Revenue entry already exists");
                $skipped++;
                continue;
            }

            $this->line("✓ Creating revenue entry for Invoice #{$invoice->id} - KES {$invoice->amount}");

            if (!$simulate) {
                PlatformRevenue::create([
                    'invoice_id' => $invoice->id,
                    'mpesa_transaction_id' => null,
                    'amount' => $invoice->amount,
                    'currency' => 'KES',
                    'mpesa_receipt' => null,
                    'platform_account_id' => $invoiceAccountId,
                    'destination_shortcode' => null,
                    'status' => 'received',
                    'received_at' => $invoice->paid_at,
                    'reconciled_at' => null,
                    'metadata' => [
                        'backfill' => true,
                        'backfill_date' => now()->toIso8601String(),
                        'note' => 'Retroactively created for paid invoice',
                    ],
                    'notes' => 'Backfilled from paid invoice (no M-Pesa receipt)',
                ]);
            }

            $count++;
        }

        $this->info("\n" . str_repeat('=', 50));
        
        if ($simulate) {
            $this->info("SIMULATION: Would create {$count} revenue entries");
        } else {
            $this->info("✅ Created {$count} revenue entries");
        }
        
        $this->info("⏭️  Skipped {$skipped} (already have revenue entries)");
        $this->info("Using Invoice Account ID: {$invoiceAccountId}");
        
        if ($simulate) {
            $this->warn("\nTo actually create the records, run without --simulate:");
            $this->info("php artisan backfill:platform-revenue");
        }

        return 0;
    }
}
