<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('status');
            }

            if (!Schema::hasColumn('invoices', 'payment_method')) {
                $table->enum('payment_method', ['auto_wallet', 'manual_mpesa'])
                    ->nullable()
                    ->after('due_date');
            }
        });

        $siteBilling = DB::table('sites')
            ->select('id', 'invoice_due_days', 'invoice_payment_method')
            ->get()
            ->keyBy('id');

        DB::table('invoices')
            ->select('id', 'site_id', 'period_end', 'due_date', 'payment_method')
            ->orderBy('id')
            ->chunkById(200, function ($invoices) use ($siteBilling) {
                foreach ($invoices as $invoice) {
                    $siteConfig = $siteBilling->get($invoice->site_id);
                    $dueDays = (int) ($siteConfig->invoice_due_days ?? 14);
                    $paymentMethod = $siteConfig->invoice_payment_method ?? 'auto_wallet';

                    $updates = [];

                    if (is_null($invoice->due_date) && !is_null($invoice->period_end)) {
                        $updates['due_date'] = Carbon::parse($invoice->period_end)->addDays($dueDays)->toDateString();
                    }

                    if (is_null($invoice->payment_method)) {
                        $updates['payment_method'] = $paymentMethod;
                    }

                    if (!empty($updates)) {
                        DB::table('invoices')->where('id', $invoice->id)->update($updates);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'payment_method')) {
                $table->dropColumn('payment_method');
            }

            if (Schema::hasColumn('invoices', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });
    }
};
