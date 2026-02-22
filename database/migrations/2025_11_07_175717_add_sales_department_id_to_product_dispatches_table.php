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
        Schema::table('product_dispatches', function (Blueprint $table) {
            // Add sales_department_id to track which sales department receives the dispatch
            $table->unsignedBigInteger('sales_department_id')->nullable()->after('sales_shift_id');

            // Foreign key constraint
            $table->foreign('sales_department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('set null');

            // Index for faster queries
            $table->index('sales_department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['sales_department_id']);
            $table->dropIndex(['sales_department_id']);
            $table->dropColumn('sales_department_id');
        });
    }
};
