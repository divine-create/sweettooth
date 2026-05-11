<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_order_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_order_id', 'coi_order_fk')
                ->references('id')->on('customer_orders')->onDelete('cascade');
            $table->foreign('product_id', 'coi_product_fk')
                ->references('id')->on('products')->onDelete('cascade');

            $table->index(['customer_order_id'], 'coi_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order_items');
    }
};
