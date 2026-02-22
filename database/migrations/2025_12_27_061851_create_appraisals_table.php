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
        if (Schema::hasTable('appraisals')) {
            return;
        }

        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->uuid('manager_id');
            $table->foreignId('appraisal_cycle_id')->constrained('appraisal_cycles')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('status', ['draft', 'self_review', 'manager_review', 'hr_review', 'completed'])->default('draft');
            $table->json('self_assessment_data')->nullable();
            $table->json('manager_assessment_data')->nullable();
            $table->json('hr_assessment_data')->nullable();
            $table->decimal('final_rating', 4, 2)->nullable();
            $table->text('final_comments')->nullable();
            $table->json('development_plan')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'appraisal_cycle_id']);
            $table->index(['manager_id', 'status']);
            $table->index(['status', 'appraisal_cycle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisals');
    }
};
