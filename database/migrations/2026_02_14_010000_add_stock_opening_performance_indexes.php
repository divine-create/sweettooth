<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexIfMissing(
            'product_dispatches',
            'product_dispatches_sales_status_received_product_idx',
            ['sales_department_id', 'status', 'received_at', 'product_id']
        );

        $this->addIndexIfMissing(
            'product_stocks',
            'product_stocks_dept_date_shift_product_idx',
            ['department_id', 'stock_date', 'shift_type', 'product_id']
        );

        $this->addIndexIfMissing(
            'products',
            'products_sales_active_type_idx',
            ['sales_department_id', 'is_active', 'product_type_id']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('product_dispatches', 'product_dispatches_sales_status_received_product_idx');
        $this->dropIndexIfExists('product_stocks', 'product_stocks_dept_date_shift_product_idx');
        $this->dropIndexIfExists('products', 'products_sales_active_type_idx');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndexIfMissing(string $tableName, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($tableName) || ! $this->tableHasAllColumns($tableName, $columns) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function tableHasAllColumns(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }
};
