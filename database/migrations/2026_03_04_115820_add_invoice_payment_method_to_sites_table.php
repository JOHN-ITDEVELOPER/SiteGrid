<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('sites', function (Blueprint $table) {
            $table->enum('invoice_payment_method', ['auto_wallet', 'manual_mpesa'])->default('auto_wallet')->after('payout_method')->comment('How to handle platform billing invoices');
            $table->integer('invoice_due_days')->default(14)->after('invoice_payment_method')->comment('Days after invoice creation when payment is due');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('invoice_payment_method');
            $table->dropColumn('invoice_due_days');
        });
    }
};
