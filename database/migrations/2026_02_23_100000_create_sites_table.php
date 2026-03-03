<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('location')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->enum('payout_window_start', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->default('Saturday');
            $table->enum('payout_window_end', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])->default('Sunday');
            $table->time('payout_opens_at')->default('09:00:00');
            $table->time('payout_closes_at')->default('18:00:00');
            $table->enum('payout_method', ['platform_managed', 'owner_managed'])->default('platform_managed');
            $table->string('owner_mpesa_account')->nullable();
            $table->json('billing_plan')->nullable();
            $table->timestamps();
            
            $table->index('owner_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sites');
    }
};
