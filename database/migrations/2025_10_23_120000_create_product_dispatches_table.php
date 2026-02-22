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
        Schema::create('product_dispatches', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');

            $table->unsignedBigInteger('daily_produce_id')->nullable();
            $table->foreign('daily_produce_id')->references('id')->on('daily_produces')->onDelete('set null');

            $table->unsignedBigInteger('production_shift_id')->comment('Kitchen/Production shift');
            $table->foreign('production_shift_id')->references('id')->on('shifts')->onDelete('cascade');

            $table->uuid('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->uuid('dispatched_by_id');
            $table->string('dispatched_by_type');

            $table->decimal('quantity', 12, 2);
            $table->string('uom', 50);

            $table->timestamp('dispatch_time');
            $table->enum('shift_type', ['morning', 'afternoon', 'night']);
            $table->date('dispatch_date');

            $table->uuid('received_by_id')->nullable()->comment('Sales employee who received');
            $table->string('received_by_type')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->enum('status', ['dispatched', 'received', 'rejected'])->default('dispatched');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index for fast lookups
            $table->index(['product_id', 'dispatch_date', 'shift_type']);
            $table->index(['branch_id', 'dispatch_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_dispatches');
    }
};
