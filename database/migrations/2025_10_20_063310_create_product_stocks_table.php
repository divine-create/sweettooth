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
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_shift_id');
            $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('cascade');
            $table->uuid('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->date('stock_date');
            $table->enum('shift_type', ['morning', 'afternoon']);

            // Opening - from previous shift/day
            $table->decimal('opening_quantity', 12, 2)->default(0);

            // Addition - received from kitchen or other sources
            $table->decimal('addition_quantity', 12, 2)->default(0);

            // Production tracking
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Call Back - items rejected/returned
            $table->decimal('callback_quantity', 12, 2)->default(0);

            // Redress - items that needed to be fixed/adjusted
            $table->decimal('redress_quantity', 12, 2)->default(0);

            // Total Available
            $table->decimal('total_available', 12, 2)->default(0);

            // Transfer - sent to other departments
            $table->decimal('transfer_quantity', 12, 2)->default(0);

            // Glovo - sold through Glovo
            $table->decimal('glovo_quantity', 12, 2)->default(0);

            // Quantity Sold - regular sales
            $table->decimal('quantity_sold', 12, 2)->default(0);

            // Closing - what's left
            $table->decimal('closing_quantity', 12, 2)->default(0);

            // Amount - total sales amount for this product
            $table->decimal('amount', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['sales_shift_id', 'product_id', 'stock_date', 'shift_type'], 'unique_product_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
