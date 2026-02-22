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
            if (! Schema::hasColumn('sale_items', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->nullable()->after('unit_price');
            }

            if (! Schema::hasColumn('sale_items', 'line_cost')) {
                $table->decimal('line_cost', 10, 2)->nullable()->after('unit_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $columns = ['unit_cost', 'line_cost'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('sale_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

