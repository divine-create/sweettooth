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
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'adjustment_reason')) {
                $table->string('adjustment_reason')->nullable()->after('type');
            }

            if (! Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 15, 2)->nullable()->after('quantity_after');
            }

            if (! Schema::hasColumn('stock_movements', 'cost_impact')) {
                $table->decimal('cost_impact', 15, 2)->nullable()->after('unit_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $columns = ['adjustment_reason', 'unit_cost', 'cost_impact'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('stock_movements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

