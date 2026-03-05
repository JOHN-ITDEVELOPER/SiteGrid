<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Stores platform-managed M-Pesa accounts for different payment types:
     * - deposit: Owner wallet top-ups (STK Push → credits wallet)
     * - invoice: Invoice payments (STK Push → platform revenue)
     * - payout: Worker disbursements (B2C → from owner wallet)
     */
    public function up(): void
    {
        Schema::create('platform_accounts', function (Blueprint $table) {
            $table->id();
            
            // Account identification
            $table->enum('account_type', ['deposit', 'invoice', 'payout'])->index();
            $table->string('name')->comment('e.g., "Platform Wallet (Deposits)", "Invoice Revenue"');
            $table->text('description')->nullable();
            
            // M-Pesa credentials (encrypted in transit)
            $table->string('provider', 50)->default('mpesa')->comment('mpesa, africastalking, etc');
            $table->string('shortcode')->unique()->comment('M-Pesa shortcode (stable identifier)');
            $table->json('credentials')->comment('consumer_key, consumer_secret, passkey, security_credential, etc (encrypted)');
            
            // Account status
            $table->enum('status', ['active', 'inactive', 'testing', 'failed'])->default('testing')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_test_error')->nullable();
            
            // Routing configuration
            $table->boolean('is_primary')->default(false)->comment('Default account for this type');
            $table->json('routing_rules')->nullable()->comment('Advanced: override routing logic');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Composite index for account type + status
            $table->index(['account_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_accounts');
    }
};
