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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->unique();
            $table->enum('category', ['raw_material', 'packaging', 'consumable', 'equipment']);
            $table->enum('uom', ['grams', 'kg', 'liters', 'ml', 'pcs', 'units', 'bags', 'cartons']);
            $table->text('description')->nullable();
            $table->decimal('reorder_level', 10, 2)->nullable();
            $table->decimal('max_stock_level', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
