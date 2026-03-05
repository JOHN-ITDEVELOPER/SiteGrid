<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Primary account routing
            if (!Schema::hasColumn('platform_settings', 'platform_deposit_account_id')) {
                $table->foreignId('platform_deposit_account_id')
                    ->nullable()
                    ->constrained('platform_accounts')
                    ->nullOnDelete()
                    ->comment('Primary account for owner wallet deposits');
            }

            if (!Schema::hasColumn('platform_settings', 'platform_invoice_account_id')) {
                $table->foreignId('platform_invoice_account_id')
                    ->nullable()
                    ->constrained('platform_accounts')
                    ->nullOnDelete()
                    ->comment('Primary account for invoice payment collection');
            }

            if (!Schema::hasColumn('platform_settings', 'platform_payout_account_id')) {
                $table->foreignId('platform_payout_account_id')
                    ->nullable()
                    ->constrained('platform_accounts')
                    ->nullOnDelete()
                    ->comment('Primary account for worker payouts');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\PlatformAccount::class, 'platform_deposit_account_id');
            $table->dropForeignIdFor(\App\Models\PlatformAccount::class, 'platform_invoice_account_id');
            $table->dropForeignIdFor(\App\Models\PlatformAccount::class, 'platform_payout_account_id');
        });
    }
};
