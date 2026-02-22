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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'damaged', 'return']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('quantity_before', 12, 2);
            $table->decimal('quantity_after', 12, 2);
            $table->string('reference_type')->nullable(); // 'purchase', 'dispatch', 'adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();
            // $table->uuid('moved_by')->nullable();
            // $table->foreign('moved_by')->references('id')->on('employees')->onDelete('restrict');
            // $table->morphs('moved_by');
            // Custom UUID polymorphic columns
            $table->string('moved_by_type')->nullable();
            $table->uuid('moved_by_id')->nullable();

            // Index for performance
            $table->index(['moved_by_type', 'moved_by_id']);
            $table->text('notes')->nullable();
            $table->timestamp('movement_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
