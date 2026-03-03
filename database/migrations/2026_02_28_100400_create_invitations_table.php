<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('phone', 20);
            $table->enum('role', ['owner_admin', 'attendance_approver', 'payroll_clerk', 'viewer', 'foreman', 'worker']);
            $table->string('token', 120)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->ipAddress('accepted_ip')->nullable();
            $table->text('accepted_user_agent')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
