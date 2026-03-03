<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('otp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('otp_code', 6);
            $table->integer('attempts')->default(0);
            $table->boolean('verified')->default(false);
            $table->datetime('expires_at');
            $table->timestamps();
            
            $table->unique('phone');
            $table->index('phone');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_sessions');
    }
};
