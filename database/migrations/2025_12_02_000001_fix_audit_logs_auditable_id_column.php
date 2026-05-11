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
            if (Schema::hasColumn('audit_logs', 'auditable_id')) {
                // Drop the old unsignedBigInteger column and recreate as UUID
                $table->dropColumn('auditable_id');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'auditable_id')) {
                // Add as UUID to match Employee model's UUID primary key
                $table->uuid('auditable_id')->nullable()->after('auditable_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('auditable_id');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            // Restore to original type if rollback is needed
            $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');
        });
    }
};
