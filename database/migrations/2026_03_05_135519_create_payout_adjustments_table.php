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
        Schema::create('payout_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained('payouts')->onDelete('cascade');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('adjusted_amount', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->text('reason');
            $table->foreignId('adjusted_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('adjusted_at')->useCurrent();
            $table->timestamps();
            
            $table->index('payout_id');
            $table->index('adjusted_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payout_adjustments');
    }
};
