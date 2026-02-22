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
        if (! Schema::hasTable('workflow_audit_logs')) {
            Schema::create('workflow_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->timestamps();

                // Workflow context
                $table->uuid('employee_id');
                $table->unsignedBigInteger('shift_id');
                $table->uuid('branch_id');
                $table->unsignedBigInteger('department_id')->nullable();

                // Workflow event details
                $table->string('event_type'); // 'step_completed', 'step_failed', 'validation_error', etc.
                $table->string('workflow_step'); // 'clock_in', 'stock_opening', etc.
                $table->string('from_state')->nullable();
                $table->string('to_state')->nullable();

                // Additional context
                $table->json('event_data')->nullable(); // Store additional event-specific data
                $table->text('notes')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();

                // Indexes
                $table->index(['employee_id', 'shift_id'], 'workflow_audit_employee_shift_idx');
                $table->index(['event_type', 'created_at'], 'workflow_audit_event_time_idx');
                $table->index(['branch_id', 'department_id', 'created_at'], 'workflow_audit_branch_dept_idx');

                // Foreign keys - using users table after unification
                $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_audit_logs');
    }
};
