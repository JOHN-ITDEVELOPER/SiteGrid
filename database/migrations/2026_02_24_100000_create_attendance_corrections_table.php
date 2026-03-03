<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendance')->onDelete('cascade');
            $table->foreignId('corrected_by')->constrained('users')->onDelete('cascade');
            $table->time('original_check_in')->nullable();
            $table->time('original_check_out')->nullable();
            $table->decimal('original_hours', 5, 2)->nullable();
            $table->time('corrected_check_in')->nullable();
            $table->time('corrected_check_out')->nullable();
            $table->decimal('corrected_hours', 5, 2)->nullable();
            $table->text('reason');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
