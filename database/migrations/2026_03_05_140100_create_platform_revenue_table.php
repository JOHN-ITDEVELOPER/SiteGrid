<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tracks all invoice payments (platform revenue).
     * Separate from payouts to provide clear revenue accounting.
     */
    public function up(): void
    {
        Schema::create('platform_revenue', function (Blueprint $table) {
            $table->id();
            
            // Source transaction
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('mpesa_transaction_id')->nullable()->constrained('mpesa_transactions')->nullOnDelete();
            
            // Payment details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('mpesa_receipt', 50)->nullable()->unique();
            
            // Account routing
            $table->foreignId('platform_account_id')->nullable()->constrained('platform_accounts')->nullOnDelete();
            $table->string('destination_shortcode')->nullable()->comment('Which M-Pesa shortcode received the deposit');
            
            // Status
            $table->enum('status', ['pending', 'received', 'reconciled', 'failed'])->default('pending')->index();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable()->comment('Extra data: phone, payment_ref, timeout_errors, etc');
            $table->text('notes')->nullable();
            
            // Audit
            $table->timestamps();
            
            $table->index(['status', 'received_at']);
            $table->index('platform_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_revenue');
    }
};
