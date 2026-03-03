<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_payout_accounts', function (Blueprint $table) {
            // Change credentials from json to text to allow encrypted data
            $table->text('credentials')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('site_payout_accounts', function (Blueprint $table) {
            $table->json('credentials')->nullable()->change();
        });
    }
};
