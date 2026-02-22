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
        Schema::table('gl_entries', function (Blueprint $table) {
            // Add polymorphic columns for entered_by relationship
            if (!Schema::hasColumn('gl_entries', 'entered_by_type')) {
                $table->string('entered_by_type')->nullable()->after('entered_by_id');
            }

            // Add polymorphic columns for posted_by relationship
            if (!Schema::hasColumn('gl_entries', 'posted_by_type')) {
                $table->string('posted_by_type')->nullable()->after('posted_by_id');
            }

            // Add polymorphic columns for reversed_by relationship
            if (!Schema::hasColumn('gl_entries', 'reversed_by_type')) {
                $table->string('reversed_by_type')->nullable()->after('reversed_by_id');
            }

            // Add indexes for better performance
            $table->index(['entered_by_id', 'entered_by_type'], 'gl_entries_entered_by_index');
            $table->index(['posted_by_id', 'posted_by_type'], 'gl_entries_posted_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gl_entries', function (Blueprint $table) {
            $table->dropIndex('gl_entries_entered_by_index');
            $table->dropIndex('gl_entries_posted_by_index');

            $table->dropColumn(['entered_by_type', 'posted_by_type', 'reversed_by_type']);
        });
    }
};
