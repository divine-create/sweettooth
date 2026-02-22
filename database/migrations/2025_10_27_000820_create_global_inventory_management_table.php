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
        Schema::create('global_inventory_managements', function (Blueprint $table) {
            $table->id();
            $table->json('categories')->default(json_encode(['add', 'edit', 'delete']));
            $table->json('brands')->default(json_encode(['add', 'edit', 'delete']));
            $table->json('products')->default(json_encode(['add', 'edit', 'sku_auto']));
            $table->string('multi_variant', 50)->default('enabled');
            $table->string('stock_adjustment', 50)->default('enabled');
            $table->string('purchase_returns', 50)->default('enabled');
            $table->json('supplier_management')->default(json_encode(['add', 'edit', 'link']));
            $table->string('low_stock_alert', 50)->default('threshold:10%');
            $table->string('expiry_tracking', 50)->default('enabled');
            $table->string('import_csv', 50)->default('enabled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_inventory_managements');
    }
};
