<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->enum('escrow_status', ['none', 'held', 'released', 'disputed'])->default('none')->after('status');
            $table->timestamp('escrow_held_at')->nullable()->after('escrow_status');
            $table->timestamp('escrow_released_at')->nullable()->after('escrow_held_at');
            $table->foreignId('escrow_held_by')->nullable()->constrained('users')->nullOnDelete()->after('escrow_released_at');
            $table->text('escrow_reason')->nullable()->after('escrow_held_by');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['escrow_status', 'escrow_held_at', 'escrow_released_at', 'escrow_held_by', 'escrow_reason']);
        });
    }
};
