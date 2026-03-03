<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->enum('channel', ['sms', 'ussd', 'email']);
            $table->string('name', 120);
            $table->text('body');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'channel']);
            $table->unique(['site_id', 'channel', 'name', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_templates');
    }
};
