<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['stk_push', 'b2c', 'b2b', 'c2b']); // Transaction type
            $table->string('merchant_request_id')->nullable(); // STK Push
            $table->string('checkout_request_id')->nullable(); // STK Push
            $table->string('conversation_id')->nullable(); // B2C
            $table->string('originator_conversation_id')->nullable(); // B2C
            $table->string('mpesa_receipt_number')->nullable(); // Confirmed transaction receipt
            $table->string('phone_number'); // Receiving/sending phone
            $table->decimal('amount', 15, 2);
            $table->string('result_code')->nullable();
            $table->text('result_description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'timeout'])->default('pending');
            $table->string('related_model')->nullable(); // Model class name (e.g., 'App\Models\OwnerWallet')
            $table->unsignedBigInteger('related_id')->nullable(); // ID of related model
            $table->json('raw_response')->nullable(); // Store full callback data
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['mpesa_receipt_number']);
            $table->index(['checkout_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
