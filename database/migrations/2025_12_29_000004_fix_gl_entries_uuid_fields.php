<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix UUID-related polymorphic type fields in gl_entries table.
     * Remove polymorphic type columns as they're not needed with direct UUID foreign keys.
     */
    public function up(): void
    {
        if (Schema::hasTable('gl_entries')) {
            Schema::table('gl_entries', function (Blueprint $table) {
                // Drop polymorphic type columns as they're not needed
                if (Schema::hasColumn('gl_entries', 'entered_by_type')) {
                    $table->dropColumn('entered_by_type');
                }
                if (Schema::hasColumn('gl_entries', 'posted_by_type')) {
                    $table->dropColumn('posted_by_type');
                }
                if (Schema::hasColumn('gl_entries', 'reversed_by_type')) {
                    $table->dropColumn('reversed_by_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gl_entries')) {
            Schema::table('gl_entries', function (Blueprint $table) {
                if (!Schema::hasColumn('gl_entries', 'entered_by_type')) {
                    $table->string('entered_by_type')->nullable();
                }
                if (!Schema::hasColumn('gl_entries', 'posted_by_type')) {
                    $table->string('posted_by_type')->nullable();
                }
                if (!Schema::hasColumn('gl_entries', 'reversed_by_type')) {
                    $table->string('reversed_by_type')->nullable();
                }
            });
        }
    }
};
