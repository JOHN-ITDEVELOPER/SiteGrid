<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['provider', 'payload', 'signature', 'is_valid', 'processed', 'related_model', 'related_id']);
            
            // Add new columns
            $table->string('integration')->after('id');
            $table->string('event_type')->after('integration');
            $table->text('url')->after('event_type');
            $table->string('method')->after('url');
            $table->longText('request_headers')->nullable()->after('method');
            $table->longText('request_body')->nullable()->after('request_headers');
            $table->integer('response_status')->nullable()->after('request_body');
            $table->longText('response_headers')->nullable()->after('response_status');
            $table->longText('response_body')->nullable()->after('response_headers');
            $table->string('status')->after('response_body');
            $table->integer('retry_count')->default(0)->after('error_message');
            $table->timestamp('last_retry_at')->nullable()->after('retry_count');
            $table->string('reference')->nullable()->after('last_retry_at');
            
            // Add indexes (skip created_at as it already exists)
            $table->index(['integration', 'status']);
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            // Drop new columns
            $table->dropIndex(['integration', 'status']);
            $table->dropIndex(['reference']);
            $table->dropColumn(['integration', 'event_type', 'url', 'method', 'request_headers', 'request_body', 'response_status', 'response_headers', 'response_body', 'status', 'retry_count', 'last_retry_at', 'reference']);
            
            // Restore old columns
            $table->string('provider');
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->boolean('processed')->default(false);
            $table->string('related_model')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
        });
    }
};
