<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pay_cycle_id')->nullable()->constrained('pay_cycles')->nullOnDelete();
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('computed_amount', 12, 2)->nullable();
            $table->enum('status', ['pending_foreman', 'pending_owner', 'approved', 'paid', 'rejected'])->default('pending_foreman');
            $table->text('reason')->nullable();
            $table->enum('source', ['web', 'api', 'ussd'])->default('web');
            $table->foreignId('approved_by_foreman')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_owner')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['worker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_claims');
    }
};
