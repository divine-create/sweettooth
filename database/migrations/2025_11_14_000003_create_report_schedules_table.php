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
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('schedule_name');
            $table->string('report_type'); // production_efficiency, sales_summary, etc.
            $table->string('report_category'); // production, sales, inventory, compiled

            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly'])->default('daily');
            $table->string('frequency_config')->nullable(); // e.g., "monday,friday" for weekly
            $table->time('generation_time')->default('23:59:59'); // When to generate

            $table->boolean('auto_compile')->default(false); // Auto-add to compiled report
            $table->boolean('auto_send_md')->default(false); // Auto-send to MD

            $table->json('recipients')->nullable(); // Employee/User IDs to receive report
            $table->json('notification_channels')->nullable(); // email, database, etc.

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'next_generation_at']);
            $table->index(['branch_id', 'department_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
