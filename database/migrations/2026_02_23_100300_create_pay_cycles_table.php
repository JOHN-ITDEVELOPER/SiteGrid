<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pay_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'computed', 'approved', 'paid', 'disputed'])->default('open');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->integer('worker_count')->default(0);
            $table->timestamps();
            
            $table->unique(['site_id', 'start_date', 'end_date']);
            $table->index('site_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pay_cycles');
    }
};
