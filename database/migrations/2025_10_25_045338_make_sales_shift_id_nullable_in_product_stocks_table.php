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
        Schema::table('product_stocks', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['sales_shift_id']);

            // Change the column to nullable
            $table->unsignedBigInteger('sales_shift_id')->nullable()->change();

            // Re-add the foreign key
            $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['sales_shift_id']);

            // Change back to NOT NULL
            $table->unsignedBigInteger('sales_shift_id')->nullable(false)->change();

            // Re-add the foreign key
            $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('cascade');
        });
    }
};
