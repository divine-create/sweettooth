<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-department ticket support (Phase 1).
 *
 * A cross-dept ticket carries lines added at different counters. Each line must
 * remember WHICH counter (shift) and cashier added it, so that on settlement the
 * sold-stock is deducted from that line's own department/shift ProductStock and
 * the cash can be attributed/redistributed to the right department at shift close.
 *
 * For ordinary single-counter sales these simply mirror the sale header, so
 * existing behaviour is unchanged when the columns are NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('department_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');

            $table->uuid('sold_by_id')->nullable()->after('shift_id');
            $table->string('sold_by_type')->nullable()->after('sold_by_id');

            $table->index(['shift_id', 'department_id'], 'sale_items_shift_dept_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex('sale_items_shift_dept_idx');
            $table->dropColumn(['shift_id', 'sold_by_id', 'sold_by_type']);
        });
    }
};
