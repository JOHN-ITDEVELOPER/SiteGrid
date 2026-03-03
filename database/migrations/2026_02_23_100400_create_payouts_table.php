<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_cycle_id')->constrained('pay_cycles')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('mpesa_fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'queued', 'processing', 'paid', 'failed'])->default('pending');
            $table->datetime('paid_at')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->unique(['pay_cycle_id', 'worker_id']);
            $table->index('pay_cycle_id');
            $table->index('worker_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payouts');
    }
};
