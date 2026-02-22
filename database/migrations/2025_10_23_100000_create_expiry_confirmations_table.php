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
        Schema::create('expiry_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_stock_id');
            $table->foreign('product_stock_id')->references('id')->on('product_stocks')->onDelete('cascade');

            $table->unsignedBigInteger('sales_shift_id');
            $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('cascade');

            $table->uuid('confirmed_by_id');
            $table->string('confirmed_by_type');

            $table->enum('action', ['confirmed_good', 'marked_callback'])->comment('Action taken: confirmed still good or marked as callback');
            $table->text('notes')->nullable()->comment('Reason for confirmation or callback');
            $table->timestamp('confirmed_at')->useCurrent();

            $table->timestamps();

            // Ensure one confirmation per product stock per shift
            $table->unique(['product_stock_id', 'sales_shift_id'], 'unique_expiry_confirmation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expiry_confirmations');
    }
};
