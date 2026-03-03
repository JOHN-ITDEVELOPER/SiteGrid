<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // Add site_id for direct site filtering and cascade management
            $table->foreignId('site_id')->after('id')->constrained('sites')->onDelete('cascade');
            
            // Modify sku constraint to be site-scoped (same SKU can exist in different sites)
            $table->dropUnique('inventory_items_sku_unique');
            $table->unique(['site_id', 'sku']);
            
            // Modify name constraint to be site-scoped
            $table->dropUnique('inventory_items_name_unit_unique');
            $table->unique(['site_id', 'name', 'unit']);
            
            // Add index on site_id
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'sku']);
            $table->dropUnique(['site_id', 'name', 'unit']);
            $table->dropIndex(['site_id']);
            $table->dropForeignKey(['site_id']);
            $table->dropColumn('site_id');
            
            // Restore old constraints
            $table->unique('sku');
            $table->unique(['name', 'unit']);
        });
    }
};
