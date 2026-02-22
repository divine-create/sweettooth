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
            // Sales shift that received the dispatch
            if (!Schema::hasColumn('product_dispatches', 'sales_shift_id')) {
                $table->unsignedBigInteger('sales_shift_id')->nullable()->after('production_shift_id');
                $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('set null');
            }

            // Received quantity (may differ from dispatched quantity)
            if (!Schema::hasColumn('product_dispatches', 'received_quantity')) {
                $table->decimal('received_quantity', 12, 2)->nullable()->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            $table->dropForeign(['sales_shift_id']);
            $table->dropColumn(['sales_shift_id', 'received_quantity']);
        });
    }
};
