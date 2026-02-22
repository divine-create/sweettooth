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
        // Add branch_id to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'branch_id')) {
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->index('branch_id');
            }
        });

        // Add branch_id to payments (if not already present)
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'branch_id')) {
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->index('branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'branch_id')) {
                $table->dropForeignKeyIfExists(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'branch_id')) {
                $table->dropForeignKeyIfExists(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
