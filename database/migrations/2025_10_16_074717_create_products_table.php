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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sku')->unique();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreignId('product_type_id')->constrained('product_types')->onDelete('restrict');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('cost', 10, 2)->nullable()->comment('Cost price for margin calculation');
            $table->integer('shelf_life_days')->default(0);
            $table->enum('uom', ['grams', 'kg', 'liters', 'ml', 'pcs', 'units'])->default('pcs');

            // Recipe yield tracking
            $table->decimal('recipe_yield', 10, 2)->default(1)->comment('How many units one recipe batch produces');
            $table->decimal('recipe_yield_weight', 10, 2)->nullable()->comment('Total weight/volume of one recipe batch (in grams/ml)');
            $table->decimal('unit_weight', 10, 2)->nullable()->comment('Weight of one unit (in grams/ml)');
            $table->decimal('yield_percentage', 5, 2)->default(100)->comment('Expected yield percentage (accounts for waste/loss)');

            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true)->comment('Available for production/sale');
            $table->string('image_url')->nullable();
            $table->json('allergens')->nullable()->comment('List of allergens');
            $table->json('tags')->nullable()->comment('Product tags for filtering');
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_type_id');
            $table->index('category_id');
            $table->index('is_active');
            $table->index('is_available');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
