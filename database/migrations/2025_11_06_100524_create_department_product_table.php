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
        Schema::create('department_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->uuid('product_id');
            $table->boolean('is_available')->default(true)->comment('Whether this product is available in this department');
            $table->decimal('department_price', 10, 2)->nullable()->comment('Department-specific price override');
            $table->integer('sort_order')->default(0)->comment('Display order in department');
            $table->timestamps();

            // Foreign keys
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Unique constraint - prevents duplicate entries of same product in same department
            $table->unique(['department_id', 'product_id']);

            // Indexes for faster queries
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_product');
    }
};
