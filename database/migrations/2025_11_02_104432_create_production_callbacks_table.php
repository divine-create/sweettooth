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
        Schema::create('production_callbacks', function (Blueprint $table) {
            $table->id();

            // Production shift initiating the callback
            $table->unsignedBigInteger('shift_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');

            // Source type: raw material or finished product
            $table->enum('source_type', [
                'raw_material_from_stock',
                'finished_product_reject',
            ]);

            // Raw material item (if applicable)
            $table->unsignedBigInteger('item_id')->nullable();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');

            // Finished product (if applicable)
            $table->uuid('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Employee recording the callback
            $table->uuid('recorded_by_id');
            $table->string('recorded_by_type');

            // Quantity being returned
            $table->decimal('quantity', 12, 2);
            $table->string('uom', 50);

            // Reason for callback
            $table->enum('reason', [
                'damaged',
                'expired',
                'quality_issue',
                'wrong_batch',
                'contamination',
                'other',
            ]);

            // Status of the callback
            $table->enum('status', [
                'pending',
                'approved_by_inventory',
                'completed',
                'rejected',
            ])->default('pending');

            // Inventory employee who approved
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->timestamp('callback_time')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['shift_id', 'callback_time']);
            $table->index(['status', 'callback_time']);
            $table->index(['source_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_callbacks');
    }
};
