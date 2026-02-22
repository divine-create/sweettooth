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
        Schema::table('production_records', function (Blueprint $table) {
            if (! Schema::hasColumn('production_records', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->nullable()->default(0)
                    ->after('quantity_remaining')
                    ->comment('Calculated standard cost per unit');
            }

            if (! Schema::hasColumn('production_records', 'total_production_cost')) {
                $table->decimal('total_production_cost', 15, 2)->nullable()->default(0)
                    ->after('unit_cost')
                    ->comment('Total cost of production batch');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            if (Schema::hasColumn('production_records', 'unit_cost')) {
                $table->dropColumn('unit_cost');
            }

            if (Schema::hasColumn('production_records', 'total_production_cost')) {
                $table->dropColumn('total_production_cost');
            }
        });
    }
};
