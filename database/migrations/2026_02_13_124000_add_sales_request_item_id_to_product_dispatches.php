<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            if (! Schema::hasColumn('product_dispatches', 'sales_production_request_item_id')) {
                $table->unsignedBigInteger('sales_production_request_item_id')
                    ->nullable()
                    ->after('production_request_id');

                $table->index('sales_production_request_item_id', 'pd_spri_idx');

                $table->foreign('sales_production_request_item_id', 'pd_spri_fk')
                    ->references('id')
                    ->on('sales_production_request_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_dispatches', function (Blueprint $table) {
            if (Schema::hasColumn('product_dispatches', 'sales_production_request_item_id')) {
                $table->dropForeign('pd_spri_fk');
                $table->dropIndex('pd_spri_idx');
                $table->dropColumn('sales_production_request_item_id');
            }
        });
    }
};

