<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix UUID-related issues in accounting_periods table.
     * The closed_by_id field should be a proper foreign key to users table.
     */
    public function up(): void
    {
        if (Schema::hasTable('accounting_periods')) {
            Schema::table('accounting_periods', function (Blueprint $table) {
                // Check if closed_by_type column exists - if it does, we need to replace it with a proper UUID foreign key
                if (Schema::hasColumn('accounting_periods', 'closed_by_type')) {
                    // We can't directly drop due to the polymorphic setup, so we'll alter the column
                    $table->dropColumn('closed_by_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_periods')) {
            Schema::table('accounting_periods', function (Blueprint $table) {
                if (!Schema::hasColumn('accounting_periods', 'closed_by_type')) {
                    $table->string('closed_by_type')->nullable();
                }
            });
        }
    }
};
