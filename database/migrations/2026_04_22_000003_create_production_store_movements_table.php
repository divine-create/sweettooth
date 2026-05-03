<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_store_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('item_id');
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'damaged', 'return']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('quantity_before', 12, 2)->default(0);
            $table->decimal('quantity_after', 12, 2)->default(0);
            $table->decimal('unit_cost', 10, 4)->nullable();
            $table->string('reference_type')->nullable();
            $table->bigInteger('reference_id')->nullable();
            $table->uuid('moved_by_id')->nullable();
            $table->string('moved_by_type')->nullable();
            $table->timestamp('movement_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('production_stores')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->index('store_id', 'idx_psm_store');
            $table->index('item_id', 'idx_psm_item');
            $table->index(['reference_type', 'reference_id'], 'idx_psm_reference');
            $table->index('movement_date', 'idx_psm_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_store_movements');
    }
};