<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add reconciliation tracking fields to bank-related tables
     */
    public function up(): void
    {
        // Add reconciliation fields to gl_entries table
        Schema::table('gl_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('gl_entries', 'reconciled')) {
                $table->boolean('reconciled')->default(false)->after('status');
                $table->timestamp('reconciled_at')->nullable()->after('reconciled');
                $table->uuid('reconciled_by_id')->nullable()->after('reconciled_at');
            }
        });

        // Add reconciliation fields to daily_bank_transactions table
        Schema::table('daily_bank_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_bank_transactions', 'reconciled')) {
                $table->boolean('reconciled')->default(false)->after('status');
                $table->timestamp('reconciled_at')->nullable()->after('reconciled');
                $table->uuid('reconciled_by_id')->nullable()->after('reconciled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gl_entries', function (Blueprint $table) {
            if (Schema::hasColumn('gl_entries', 'reconciled')) {
                $table->dropColumn(['reconciled', 'reconciled_at', 'reconciled_by_id']);
            }
        });

        Schema::table('daily_bank_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('daily_bank_transactions', 'reconciled')) {
                $table->dropColumn(['reconciled', 'reconciled_at', 'reconciled_by_id']);
            }
        });
    }
};
