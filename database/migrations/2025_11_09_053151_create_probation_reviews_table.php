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
        Schema::create('probation_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->date('review_date');
            $table->date('probation_start_date');
            $table->date('probation_end_date');
            $table->enum('review_type', ['initial', 'midpoint', 'final', 'extension'])->default('initial');
            $table->uuid('reviewer_id'); // Manager/HR conducting review
            $table->integer('performance_score')->nullable(); // 1-10 or 1-100
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_achievements')->nullable();
            $table->text('training_recommendations')->nullable();
            $table->text('overall_comments');
            $table->enum('recommendation', ['pass', 'extend', 'terminate'])->default('pass');
            $table->date('extended_probation_end_date')->nullable();
            $table->integer('extension_days')->nullable();
            $table->text('extension_reason')->nullable();
            $table->enum('status', ['draft', 'submitted', 'acknowledged'])->default('draft');
            $table->uuid('acknowledged_by_id')->nullable();
            $table->string('acknowledged_by_type')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('probation_reviews');
    }
};
