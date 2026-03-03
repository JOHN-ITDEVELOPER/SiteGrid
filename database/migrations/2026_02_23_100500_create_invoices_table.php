<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('worker_count');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->datetime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['site_id', 'period_start', 'period_end']);
            $table->index('site_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
