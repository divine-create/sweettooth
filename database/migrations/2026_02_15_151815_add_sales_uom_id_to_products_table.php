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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('sales_uom_id')
                ->nullable()
                ->after('uom_id')
                ->constrained('units_of_measure')
                ->nullOnDelete()
                ->comment('UOM for selling (e.g., scoop, cone). If null, uses uom_id');
            
            $table->decimal('sales_unit_weight', 10, 4)
                ->nullable()
                ->after('sales_uom_id')
                ->comment('Weight per sales unit in base UOM units (e.g., 100g per scoop)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sales_uom_id']);
            $table->dropColumn(['sales_uom_id', 'sales_unit_weight']);
        });
    }
};
