<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Use raw SQL to modify the phone column to be nullable
        DB::statement('ALTER TABLE users MODIFY phone VARCHAR(20) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to NOT nullable (only if needed)
        DB::statement('ALTER TABLE users MODIFY phone VARCHAR(20) NOT NULL');
    }
};
