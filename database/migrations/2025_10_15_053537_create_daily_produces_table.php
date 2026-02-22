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
        Schema::create('daily_produces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->unsignedBigInteger('recipe_id');
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->date('produce_date');
            $table->enum('shift_type', ['morning', 'afternoon']);

            // Opening - what they met from previous shift/day
            $table->decimal('opening_quantity', 12, 2)->default(0);

            // Requested - items requested from inventory for this production
            $table->decimal('requested_quantity', 12, 2)->default(0);

            // Produced - quantity produced in this shift
            $table->decimal('produced_quantity', 12, 2)->default(0);

            // Sent Out - quantity sent to sales departments
            $table->decimal('sent_out_quantity', 12, 2)->default(0);

            // Order - what was ordered but not yet sent
            $table->decimal('order_quantity', 12, 2)->default(0);

            // Call Back - items that were bad/rejected
            $table->decimal('callback_quantity', 12, 2)->default(0);

            // Closing - what's left at end of shift (auto-calculated)
            $table->decimal('closing_quantity', 12, 2)->default(0);

            // Expected Closing - system calculated based on formula
            $table->decimal('expected_closing', 12, 2)->default(0);

            // Variance
            $table->decimal('variance', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['shift_id', 'recipe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_produces');
    }
};
