<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('hours', 5, 2)->nullable();
            $table->enum('source', ['foreman_web', 'ussd', 'qr'])->default('foreman_web');
            $table->boolean('is_present')->default(true);
            $table->timestamps();
            
            $table->unique(['site_id', 'worker_id', 'date']);
            $table->index('site_id');
            $table->index('worker_id');
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
};
