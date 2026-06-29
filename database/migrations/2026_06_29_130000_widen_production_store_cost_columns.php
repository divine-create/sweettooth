<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen the production-store cost columns from decimal(10,4) (max 999,999.9999)
 * to decimal(12,4), matching `stocks.average_cost`. Producing a finished good
 * copies the product's unit cost into both columns; some products carry costs
 * above the old ceiling, which threw SQLSTATE[22003] "Out of range value for
 * column 'average_cost'" and blocked production. (The underlying inflated cost
 * data is a separate cleanup.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_store_stocks', function (Blueprint $table) {
            $table->decimal('average_cost', 12, 4)->default(0)->change();
        });

        Schema::table('production_store_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_store_stocks', function (Blueprint $table) {
            $table->decimal('average_cost', 10, 4)->default(0)->change();
        });

        Schema::table('production_store_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 4)->nullable()->change();
        });
    }
};
