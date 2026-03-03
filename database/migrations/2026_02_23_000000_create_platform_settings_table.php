<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('platform_fee_per_worker', 10, 2)->default(50.00);
            $table->unsignedTinyInteger('payout_window_start_day')->default(1);
            $table->unsignedTinyInteger('payout_window_end_day')->default(7);
            $table->string('default_currency', 8)->default('KES');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
