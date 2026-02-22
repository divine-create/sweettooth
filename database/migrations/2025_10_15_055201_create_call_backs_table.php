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
        Schema::create('call_backs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->string('callback_type'); // 'inventory_item', 'produced_item'
            $table->unsignedBigInteger('reference_id'); // item_id or recipe_id
            $table->decimal('quantity', 12, 2);
            $table->enum('uom', ['grams', 'kg', 'liters', 'ml', 'pcs', 'units']);
            $table->enum('reason', ['expired', 'damaged', 'quality_issue', 'contaminated', 'other']);
            $table->text('description')->nullable();
            $table->uuid('reported_by_id');
            $table->string('reported_by_type');
            $table->timestamp('callback_time');
            $table->enum('action_taken', ['disposed', 'returned_to_supplier', 'reprocessed', 'pending'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_backs');
    }
};
