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
        Schema::create('employee_leave_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->decimal('allocated_days', 8, 2); // Custom allocation for this employee
            $table->text('notes')->nullable(); // Admin notes for this allocation
            // $table->uuid('allocated_by')->nullable(); // Who allocated this
            $table->uuid('allocated_by_id');
            $table->string('allocated_by_type');
            $table->timestamp('allocated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure one allocation per employee per leave type per year
            $table->unique(['employee_id', 'leave_type_id', 'year']);
            $table->index(['employee_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_allocations');
    }
};
