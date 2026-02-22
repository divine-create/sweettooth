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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unsignedBigInteger('department_id');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->string('product_name');
            $table->string('sku')->unique();
            // $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('product_type', ['gelato_base', 'gelato_flavor', 'pastry', 'hot_kitchen', 'beverage']);
            $table->decimal('cost_per_unit', 10, 4)->default(0);
            $table->enum('uom', ['grams', 'kg', 'liters', 'ml', 'pcs', 'units'])->default('pcs');
            $table->decimal('yield_quantity', 10, 2)->default(1); // How many units this recipe produces
            $table->integer('preparation_time')->nullable(); // in minutes
            $table->text('instructions')->nullable();
            $table->enum('status', ['active', 'inactive', 'testing'])->default('active');
            $table->uuid('created_by_id');
            $table->string('created_by_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
