<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_store_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_available', 12, 2)->default(0);
            $table->decimal('quantity_reserved', 12, 2)->default(0);
            $table->decimal('average_cost', 10, 4)->default(0);
            $table->date('last_stock_take_date')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('production_stores')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->unique(['store_id', 'item_id'], 'uq_production_store_stocks_store_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_store_stocks');
    }
};