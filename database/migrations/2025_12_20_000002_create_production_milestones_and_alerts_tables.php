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
        // Create production_milestones table
        Schema::create('production_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_record_id');
            $table->enum('milestone_type', [
                'ingredients_collected',
                'production_started',
                'quality_check_passed',
                'packaging_completed',
                'dispatch_ready',
            ]);
            $table->timestamp('achieved_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->uuid('achieved_by');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('production_record_id')->references('id')->on('production_records')->onDelete('cascade');
            $table->foreign('achieved_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['production_record_id', 'milestone_type']);
            $table->index('achieved_at');
        });

        // Create production_progress_alerts table
        Schema::create('production_progress_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_produce_id');
            $table->enum('alert_type', [
                'delay_warning',
                'quality_issue',
                'resource_shortage',
                'completion_milestone',
                'bottleneck_detected',
            ]);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('message');
            $table->boolean('is_acknowledged')->default(false);
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('auto_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->foreign('daily_produce_id')->references('id')->on('daily_produces')->onDelete('cascade');
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['alert_type', 'severity']);
            $table->index('is_acknowledged');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_progress_alerts');
        Schema::dropIfExists('production_milestones');
    }
};
