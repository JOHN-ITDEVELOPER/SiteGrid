<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('low_stock_threshold', 14, 3)->default(0);
            $table->decimal('avg_unit_cost', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'item_id']);
            $table->index(['site_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
