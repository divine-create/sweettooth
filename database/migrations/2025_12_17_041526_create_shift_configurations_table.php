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
        Schema::create('shift_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->enum('shift_type', ['morning', 'afternoon', 'night', 'full_time']);
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->time('clock_in_start');
            $table->time('clock_in_end');
            $table->integer('auto_clock_out_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->decimal('max_overtime_hours', 4, 2)->default(2.00);
            $table->integer('break_duration_minutes')->default(60);
            $table->string('timezone', 50)->default('UTC');
            $table->timestamps();

            // Constraints - removed check constraints for compatibility
            // Note: Validation will be handled in application layer

            // Indexes
            $table->unique(['branch_id', 'shift_type', 'is_active']);
            $table->index(['branch_id', 'is_active']);
            $table->index('shift_type');
            $table->index('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_configurations');
    }
};
