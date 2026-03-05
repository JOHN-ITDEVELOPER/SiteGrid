<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Modify the source column enum to include 'owner_web'
        DB::statement("ALTER TABLE attendance MODIFY source ENUM('foreman_web', 'owner_web', 'ussd', 'qr') NOT NULL DEFAULT 'foreman_web'");
    }

    public function down()
    {
        // Rollback to original enum values
        DB::statement("ALTER TABLE attendance MODIFY source ENUM('foreman_web', 'ussd', 'qr') NOT NULL DEFAULT 'foreman_web'");
    }
};
