<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            // Add site_id as the first column after id
            $table->foreignId('site_id')->after('id')->constrained('sites')->onDelete('cascade');
            
            // Drop old unique constraint
            $table->dropUnique(['name', 'type']);
            
            // Add new unique constraint scoped to site
            $table->unique(['site_id', 'name', 'type']);
            
            // Add index on site_id for fast filtering
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'name', 'type']);
            $table->dropIndex(['site_id']);
            $table->dropForeignKey(['site_id']);
            $table->dropColumn('site_id');
            
            // Restore old unique constraint
            $table->unique(['name', 'type']);
        });
    }
};
