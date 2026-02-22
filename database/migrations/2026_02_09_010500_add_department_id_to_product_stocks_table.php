<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('product_stocks', 'department_id')) {
                // Clean up any partially-created column from a failed migration
                $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', 'product_stocks')
                    ->where('COLUMN_NAME', 'department_id')
                    ->whereNotNull('CONSTRAINT_NAME')
                    ->where('CONSTRAINT_NAME', 'product_stocks_department_id_foreign')
                    ->exists();

                if ($fkExists) {
                    $table->dropForeign('product_stocks_department_id_foreign');
                }

                if (Schema::hasColumn('product_stocks', 'department_id')) {
                    $idxExists = DB::table('information_schema.STATISTICS')
                        ->where('TABLE_SCHEMA', DB::getDatabaseName())
                        ->where('TABLE_NAME', 'product_stocks')
                        ->where('INDEX_NAME', 'product_stocks_dept_idx')
                        ->exists();
                    if ($idxExists) {
                        $table->dropIndex('product_stocks_dept_idx');
                    }
                    $table->dropColumn('department_id');
                }
            }

            $table->unsignedBigInteger('department_id')->nullable()->after('sales_shift_id');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->index(['department_id', 'product_id', 'stock_date', 'shift_type'], 'product_stocks_dept_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('product_stocks', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropIndex('product_stocks_dept_idx');
                $table->dropColumn('department_id');
            }
        });
    }
};
