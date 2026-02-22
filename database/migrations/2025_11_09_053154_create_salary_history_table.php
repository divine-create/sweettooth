<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->decimal('previous_salary', 12, 2)->nullable();
            $table->decimal('new_salary', 12, 2);
            $table->decimal('change_amount', 12, 2);
            $table->decimal('change_percentage', 8, 2);
            $table->date('effective_date');
            $table->enum('change_type', ['initial', 'increment', 'promotion', 'adjustment', 'bonus', 'deduction'])->default('increment');
            $table->text('reason');
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_history');
    }
};
