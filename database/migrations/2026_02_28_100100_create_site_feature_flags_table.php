<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('flag_name', 100);
            $table->boolean('value')->default(false);
            $table->unsignedTinyInteger('rollout_percent')->default(100);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['site_id', 'flag_name']);
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_feature_flags');
    }
};
