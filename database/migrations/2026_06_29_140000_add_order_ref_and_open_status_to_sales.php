<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-department ticket support (Phase 1).
 *
 * `order_ref` is the customer-carried handle (printed ticket number) used to
 * look an order up from ANY sales counter, independent of the table/department
 * scoping the POS normally enforces.
 *
 * `status = 'open'` is a new lifecycle state for a running ticket that may be
 * appended to by several counters before being settled once. It is kept
 * distinct from 'hold' (a regular parked single-counter cart) so cross-dept
 * tickets can be queried and reconciled separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('order_ref')->nullable()->after('sale_number');
            $table->index(['branch_id', 'order_ref'], 'sales_branch_order_ref_idx');
        });

        // MySQL enum widening — add 'open' while preserving existing values.
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('pending','completed','cancelled','refunded','hold','open') NOT NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        // Park any lingering open tickets as 'hold' before narrowing the enum,
        // so the column change cannot fail on out-of-range values.
        DB::table('sales')->where('status', 'open')->update(['status' => 'hold']);

        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('pending','completed','cancelled','refunded','hold') NOT NULL DEFAULT 'completed'");

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_branch_order_ref_idx');
            $table->dropColumn('order_ref');
        });
    }
};
