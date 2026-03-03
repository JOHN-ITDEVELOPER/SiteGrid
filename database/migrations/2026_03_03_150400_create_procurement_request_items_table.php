<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained('procurement_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('requested_quantity', 14, 3);
            $table->decimal('approved_quantity', 14, 3)->nullable();
            $table->decimal('delivered_quantity', 14, 3)->nullable();
            $table->decimal('estimated_unit_cost', 14, 2)->nullable();
            $table->decimal('final_unit_cost', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['procurement_request_id', 'item_id'], 'proc_req_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_request_items');
    }
};
