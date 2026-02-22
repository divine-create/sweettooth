<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->integer('year'); // Leave balance year
            $table->decimal('total_days', 8, 2)->default(0); // Total allocated
            $table->decimal('used_days', 8, 2)->default(0); // Days used
            $table->decimal('pending_days', 8, 2)->default(0); // Days in pending applications
            $table->decimal('remaining_days', 8, 2)->default(0); // Available balance
            $table->decimal('carried_forward', 8, 2)->default(0); // From previous year
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};
