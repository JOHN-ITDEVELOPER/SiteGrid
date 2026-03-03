<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['owner_admin', 'attendance_approver', 'payroll_clerk', 'viewer', 'foreman', 'worker']);
            $table->json('notification_preferences')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'user_id', 'role']);
            $table->index(['site_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_members');
    }
};
