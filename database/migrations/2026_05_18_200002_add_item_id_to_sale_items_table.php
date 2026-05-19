<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Make product_id nullable so rows can represent direct inventory item sales
            $table->uuid('product_id')->nullable()->change();

            $table->unsignedBigInteger('item_id')->nullable()->after('product_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['item_id']);
            $table->dropColumn('item_id');
            $table->uuid('product_id')->nullable(false)->change();
        });
    }
};
