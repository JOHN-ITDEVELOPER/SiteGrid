<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worker_claims', function (Blueprint $table) {
            $table->text('override_reason')->nullable()->after('reason')->comment('Reason for owner override of withdrawal window');
            $table->timestamp('overridden_at')->nullable()->after('paid_at')->comment('When owner overrode withdrawal window');
        });
    }

    public function down(): void
    {
        Schema::table('worker_claims', function (Blueprint $table) {
            $table->dropColumn('override_reason');
            $table->dropColumn('overridden_at');
        });
    }
};
