<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the column if it exists with wrong type (char(36) from a failed migration attempt)
        if (Schema::hasColumn('product_dispatches', 'customer_order_id')) {
            Schema::table('product_dispatches', function (Blueprint $table) {
                $table->dropColumn('customer_order_id');
            });
        }

        Schema::table('product_dispatches', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_order_id')->nullable()->after('sales_department_id');
            $table->foreign('customer_order_id', 'pd_customer_order_fk')
                ->references('id')->on('customer_orders')->onDelete('set null');
            $table->index('customer_order_id', 'pd_customer_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            $table->dropForeign('pd_customer_order_fk');
            $table->dropIndex('pd_customer_order_idx');
            $table->dropColumn('customer_order_id');
        });
    }
};
