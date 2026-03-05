<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            
            // Core locked settings (cannot be changed by owner)
            $table->boolean('lock_payout_method')->default(true);
            $table->boolean('lock_payout_window')->default(false);
            $table->boolean('lock_invoice_payment_method')->default(true);
            $table->boolean('lock_compliance_settings')->default(true);
            $table->boolean('lock_auto_payout')->default(false);
            $table->boolean('lock_approval_workflow')->default(false);
            
            // Constraint settings (owner can customize within bounds)
            $table->string('allowed_payout_methods')->nullable(); // comma-separated: platform_managed,owner_managed
            $table->json('payout_window_constraints')->nullable(); // min/max days allowed
            $table->json('sms_provider_whitelist')->nullable(); // approved providers
            $table->integer('max_team_members')->default(10);
            $table->integer('max_foremen')->default(5);
            
            // Temporary lock down
            $table->boolean('is_locked_down')->default(false);
            $table->text('lockdown_reason')->nullable();
            $table->timestamp('lockdown_until')->nullable();
            
            // Admin audit
            $table->timestamp('last_policy_changed_at')->nullable();
            $table->unsignedBigInteger('last_policy_changed_by')->nullable();
            
            $table->timestamps();
            
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->foreign('last_policy_changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_policies');
    }
};
