<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            if (! Schema::hasColumn('product_stocks', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('sales_shift_id');
                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
            }
            // Add a plain index on sales_shift_id before dropping the unique index that
            // also covers it, so the FK constraint still has an index to use.
            $table->index('sales_shift_id', 'product_stocks_sales_shift_id_idx');
            $table->dropUnique('unique_product_stock');
        });

        if (! Schema::hasIndex('product_stocks', 'product_stocks_shift_product_date_idx')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->index(['shift_id', 'product_id', 'stock_date'], 'product_stocks_shift_product_date_idx');
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('sales_shift_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
            $table->index(['shift_id', 'sale_time'], 'sales_shift_id_sale_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex('sales_shift_id_sale_time_idx');
            $table->dropColumn('shift_id');
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex('product_stocks_shift_product_date_idx');
            $table->dropColumn('shift_id');
            $table->unique(['sales_shift_id', 'product_id', 'stock_date', 'shift_type'], 'unique_product_stock');
        });
    }
};
