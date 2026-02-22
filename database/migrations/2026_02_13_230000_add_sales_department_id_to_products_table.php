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
        if (Schema::hasColumn('products', 'sales_department_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_department_id')
                ->nullable()
                ->after('product_type_id');
            $table->foreign('sales_department_id', 'products_sales_department_id_fk')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
            $table->index('sales_department_id', 'products_sales_department_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('products', 'sales_department_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_sales_department_id_fk');
            $table->dropIndex('products_sales_department_id_idx');
            $table->dropColumn('sales_department_id');
        });
    }
};

