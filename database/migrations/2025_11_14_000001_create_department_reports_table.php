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
        Schema::create('department_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('generated_by_id')->nullable();
            $table->string('generated_by_type')->nullable();

            $table->string('report_type'); // production_efficiency, quality_metrics, etc.
            $table->string('report_category'); // production, sales, inventory
            $table->string('report_name');
            $table->date('report_date');
            $table->date('period_from');
            $table->date('period_to');

            $table->json('report_data'); // Main report data
            $table->json('summary_metrics')->nullable(); // Key metrics for quick view
            $table->json('charts_data')->nullable(); // Data for charts/graphs

            $table->enum('status', ['draft', 'pending_review', 'reviewed', 'compiled', 'sent_to_md'])->default('draft');
            $table->uuid('reviewed_by_id')->nullable();
            $table->string('reviewed_by_type')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->string('export_format')->nullable(); // pdf, excel, csv
            $table->string('file_path')->nullable(); // Path to exported file

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['branch_id', 'report_date']);
            $table->index(['department_id', 'report_date']);
            $table->index(['report_type', 'report_category']);
            $table->index(['status', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_reports');
    }
};
