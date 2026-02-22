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
        // Add progress tracking fields to daily_produces table
        Schema::table('daily_produces', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_produces', 'progress_percentage')) {
                $table->decimal('progress_percentage', 5, 2)->default(0.00)->after('variance');
            }
            if (! Schema::hasColumn('daily_produces', 'estimated_completion_time')) {
                $table->timestamp('estimated_completion_time')->nullable()->after('progress_percentage');
            }
            if (! Schema::hasColumn('daily_produces', 'actual_completion_time')) {
                $table->timestamp('actual_completion_time')->nullable()->after('estimated_completion_time');
            }
            if (! Schema::hasColumn('daily_produces', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('actual_completion_time');
            }
            if (! Schema::hasColumn('daily_produces', 'assigned_to')) {
                $table->uuid('assigned_to')->nullable()->after('priority');
                $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            }
            // Skip index creation for now to avoid conflicts
        });

        // Add progress tracking fields to production_records table
        Schema::table('production_records', function (Blueprint $table) {
            if (! Schema::hasColumn('production_records', 'progress_stage')) {
                $table->enum('progress_stage', [
                    'started',
                    'ingredients_collected',
                    'processing',
                    'quality_check',
                    'packaging',
                    'ready_for_dispatch',
                ])->default('started')->after('dispatch_status');
            }

            if (! Schema::hasColumn('production_records', 'stage_start_time')) {
                $table->timestamp('stage_start_time')->nullable()->after('progress_stage');
            }
            if (! Schema::hasColumn('production_records', 'stage_end_time')) {
                $table->timestamp('stage_end_time')->nullable()->after('stage_start_time');
            }
            if (! Schema::hasColumn('production_records', 'expected_duration_minutes')) {
                $table->integer('expected_duration_minutes')->nullable()->after('stage_end_time');
            }
            if (! Schema::hasColumn('production_records', 'progress_percentage')) {
                $table->decimal('progress_percentage', 5, 2)->default(0.00)->after('expected_duration_minutes');
            }

            // Skip index creation for now to avoid conflicts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_produces', function (Blueprint $table) {
            if (Schema::hasColumn('daily_produces', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
            }
            $columns = ['progress_percentage', 'estimated_completion_time', 'actual_completion_time', 'priority', 'assigned_to'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('daily_produces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('production_records', function (Blueprint $table) {
            $columns = ['progress_stage', 'stage_start_time', 'stage_end_time', 'expected_duration_minutes', 'progress_percentage'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('production_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
