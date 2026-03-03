<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->enum('account_type', ['platform', 'owner'])->default('platform');
            $table->string('provider', 50)->nullable();
            $table->json('credentials')->nullable();
            $table->enum('status', ['valid', 'invalid', 'pending'])->default('pending');
            $table->timestamp('last_tested_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('site_id');
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_payout_accounts');
    }
};
