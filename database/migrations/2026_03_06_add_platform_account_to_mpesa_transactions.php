<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Track which platform account (deposit/invoice/payout) processed each M-Pesa transaction.
     */
    public function up(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('mpesa_transactions', 'platform_account_id')) {
                $table->foreignId('platform_account_id')
                    ->nullable()
                    ->after('related_id')
                    ->constrained('platform_accounts')
                    ->nullOnDelete()
                    ->comment('Which platform account (deposit/invoice/payout) processed this transaction');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('mpesa_transactions', 'platform_account_id')) {
                $table->dropForeignIdFor('PlatformAccount');
                $table->dropColumn('platform_account_id');
            }
        });
    }
};
