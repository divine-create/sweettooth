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
        Schema::create('product_dispatch_callbacks', function (Blueprint $table) {
            $table->id();

            // Link to the original dispatch from production to sales
            $table->unsignedBigInteger('product_dispatch_id');
            $table->foreign('product_dispatch_id')->references('id')->on('product_dispatches')->onDelete('cascade');

            // Sales shift that is sending back
            $table->unsignedBigInteger('sales_shift_id');
            $table->foreign('sales_shift_id')->references('id')->on('sales_shifts')->onDelete('cascade');

            // Product being returned
            $table->uuid('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Employee recording the callback
            $table->uuid('recorded_by_id');
            $table->string('recorded_by_type');

            // Quantity being returned
            $table->decimal('quantity', 12, 2);
            $table->string('uom', 50);

            // Reason for callback
            $table->enum('reason', [
                'expired',
                'damaged',
                'quality_issue',
                'customer_return',
                'over_received',
                'wrong_item',
                'other',
            ]);

            // Status of the callback
            $table->enum('status', [
                'pending',
                'approved_by_production',
                'received_by_production',
                'completed',
            ])->default('pending');

            // Production employee who approved/received
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->uuid('received_by_id')->nullable();
            $table->string('received_by_type')->nullable();
            $table->timestamp('received_at')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->timestamp('callback_time')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['product_dispatch_id', 'status']);
            $table->index(['sales_shift_id', 'callback_time']);
            $table->index(['status', 'callback_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_dispatch_callbacks');
    }
};
