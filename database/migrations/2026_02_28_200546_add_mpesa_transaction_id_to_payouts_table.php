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
        Schema::table('payouts', function (Blueprint $table) {
            $table->unsignedBigInteger('mpesa_transaction_id')->nullable()->after('error_message');
            $table->foreign('mpesa_transaction_id')->references('id')->on('mpesa_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropForeign(['mpesa_transaction_id']);
            $table->dropColumn('mpesa_transaction_id');
        });
    }
};
