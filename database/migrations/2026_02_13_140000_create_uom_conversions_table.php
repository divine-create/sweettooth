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
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->decimal('factor', 24, 12)->comment('Multiply quantity in from_uom by factor to get to_uom quantity');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('from_uom_id')->references('id')->on('units_of_measure')->restrictOnDelete();
            $table->foreign('to_uom_id')->references('id')->on('units_of_measure')->restrictOnDelete();

            $table->index(['from_uom_id', 'to_uom_id', 'is_active'], 'uom_conversions_lookup_idx');
            $table->index(['branch_id', 'item_id', 'product_id'], 'uom_conversions_scope_idx');
            $table->index(['item_id', 'product_id', 'is_active'], 'uom_conversions_context_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
