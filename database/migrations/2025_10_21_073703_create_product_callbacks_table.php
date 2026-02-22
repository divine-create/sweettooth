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
        Schema::create('product_callbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_stock_id');
            $table->foreign('product_stock_id')->references('id')->on('product_stocks')->onDelete('cascade');
            $table->uuid('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('sales_shift_id');
            $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('cascade');
            $table->uuid('recorded_by_id');
            $table->string('recorded_by_type');

            $table->decimal('quantity', 12, 2);
            $table->enum('reason', ['expired', 'damaged', 'quality_issue', 'customer_return', 'other']);
            $table->text('notes')->nullable();
            $table->timestamp('callback_time');
            $table->timestamps();

            $table->index(['product_id', 'callback_time']);
            $table->index(['sales_shift_id', 'callback_time']);
            $table->index('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_callbacks');
    }
};
