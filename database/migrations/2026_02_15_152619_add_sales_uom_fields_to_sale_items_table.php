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
        Schema::table('sale_items', function (Blueprint $table) {
            // Sales quantity (what cashier enters, e.g., 1 scoop)
            $table->decimal('sales_quantity', 10, 4)
                ->nullable()
                ->after('quantity')
                ->comment('Quantity in sales UOM (e.g., 1 scoop)');
            
            // Sales UOM ID
            $table->foreignId('sales_uom_id')
                ->nullable()
                ->after('sales_quantity')
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->comment('UOM used for sale (e.g., scoop, cone)');
            
            // Base quantity converted from sales quantity (e.g., 100g)
            // The existing 'quantity' field stores the base quantity for stock deduction
            $table->decimal('conversion_factor', 10, 6)
                ->nullable()
                ->after('sales_uom_id')
                ->comment('Conversion factor used (e.g., 100 for 1 scoop = 100g)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['sales_uom_id']);
            $table->dropColumn(['sales_quantity', 'sales_uom_id', 'conversion_factor']);
        });
    }
};
