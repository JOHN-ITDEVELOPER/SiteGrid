<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // 'mpesa', 'ussd', 'africastalking'
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->boolean('processed')->default(false);
            $table->string('related_model')->nullable();
            $table->bigInteger('related_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('provider');
            $table->index('processed');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_logs');
    }
};
