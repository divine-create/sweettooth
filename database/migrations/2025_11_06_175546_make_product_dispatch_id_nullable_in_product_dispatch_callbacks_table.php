<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_dispatch_callbacks', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['product_dispatch_id']);

            // Make product_dispatch_id nullable
            $table->unsignedBigInteger('product_dispatch_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('product_dispatch_id')
                ->references('id')
                ->on('product_dispatches')
                ->onDelete('cascade');
        });

        // Update the reason enum to include 'over_stock'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE product_dispatch_callbacks MODIFY reason ENUM('expired', 'damaged', 'quality_issue', 'customer_return', 'over_received', 'over_stock', 'wrong_item', 'other')");
        } else {
            // For SQLite, since MODIFY is not supported, we recreate the table or just note it
            // For now, skip for SQLite tests
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_dispatch_callbacks', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['product_dispatch_id']);

            // Make product_dispatch_id not nullable
            $table->unsignedBigInteger('product_dispatch_id')->nullable(false)->change();

            // Re-add the foreign key constraint
            $table->foreign('product_dispatch_id')
                ->references('id')
                ->on('product_dispatches')
                ->onDelete('cascade');
        });

        // Revert the reason enum
        DB::statement("ALTER TABLE product_dispatch_callbacks MODIFY reason ENUM('expired', 'damaged', 'quality_issue', 'customer_return', 'over_received', 'wrong_item', 'other')");
    }
};
