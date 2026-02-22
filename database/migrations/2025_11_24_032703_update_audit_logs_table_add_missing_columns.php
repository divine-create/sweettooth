<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (! Schema::hasColumn('audit_logs', 'description')) {
                $table->string('description')->nullable()->after('action');
            }
            if (! Schema::hasColumn('audit_logs', 'old_values')) {
                $table->json('old_values')->nullable()->after('description');
            }
            if (! Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }
            if (! Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->ipAddress('ip_address')->nullable()->after('new_values');
            }
            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('audit_logs', 'status')) {
                $table->string('status', 50)->default('completed')->after('user_agent');
            }
            if (! Schema::hasColumn('audit_logs', 'approval_request_id')) {
                $table->uuid('approval_request_id')->nullable()->after('status');
            }
            if (! Schema::hasColumn('audit_logs', 'logged_at')) {
                $table->dateTime('logged_at')->nullable()->after('approval_request_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumnIfExists([
                'description',
                'old_values',
                'new_values',
                'ip_address',
                'user_agent',
                'status',
                'approval_request_id',
                'logged_at',
            ]);
        });
    }
};
