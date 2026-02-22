<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     // Skip if tables don't have the indexes yet (they may be created in table creation migrations)
    //     // This migration is optional - indexes may already exist
    //     try {
    //         Schema::table('product_dispatch_callbacks', function (Blueprint $table) {
    //             // Branch filtering optimization (via sales_shift)
    //             $table->index(['sales_shift_id', 'callback_time']);

    //             // Quick status reports
    //             $table->index(['status', 'callback_time']);

    //             // Product-specific queries
    //             $table->index(['product_id', 'callback_time']);
    //         });
    //     } catch (\Exception $e) {
    //         // Indexes may already exist, skip
    //     }

    //     try {
    //         Schema::table('production_callbacks', function (Blueprint $table) {
    //             // Branch filtering (via shift)
    //             $table->index(['shift_id', 'callback_time']);

    //             // Status reports
    //             $table->index(['status', 'callback_time']);

    //             // Type-specific queries
    //             $table->index(['source_type', 'callback_time']);
    //         });
    //     } catch (\Exception $e) {
    //         // Indexes may already exist, skip
    //     }
    // }

    // /**
    //  * Reverse the migrations.
    //  */
    // public function down(): void
    // {
    //     Schema::table('product_dispatch_callbacks', function (Blueprint $table) {
    //         $table->dropIndex(['sales_shift_id', 'callback_time']);
    //         $table->dropIndex(['status', 'callback_time']);
    //         $table->dropIndex(['product_id', 'callback_time']);
    //     });

    //     Schema::table('production_callbacks', function (Blueprint $table) {
    //         $table->dropIndex(['shift_id', 'callback_time']);
    //         $table->dropIndex(['status', 'callback_time']);
    //         $table->dropIndex(['source_type', 'callback_time']);
    //     });
    // }
};
