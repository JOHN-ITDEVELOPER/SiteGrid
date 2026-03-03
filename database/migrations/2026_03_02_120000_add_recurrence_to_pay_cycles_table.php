<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pay_cycles', function (Blueprint $table) {
            $table->enum('recurrence_pattern', ['weekly', 'bi-weekly', 'monthly'])->nullable()->after('status');
            $table->date('next_cycle_date')->nullable()->after('recurrence_pattern');
            $table->boolean('is_auto_generated')->default(false)->after('next_cycle_date');
        });
    }

    public function down()
    {
        Schema::table('pay_cycles', function (Blueprint $table) {
            $table->dropColumn(['recurrence_pattern', 'next_cycle_date', 'is_auto_generated']);
        });
    }
};
