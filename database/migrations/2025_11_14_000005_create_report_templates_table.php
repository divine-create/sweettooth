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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('template_name');
            $table->string('report_type'); // Matches department_reports.report_type
            $table->string('report_category'); // production, sales, inventory
            $table->text('description')->nullable();

            $table->json('template_structure'); // Fields, sections, metrics to include
            $table->json('chart_configurations')->nullable(); // Chart types, colors, etc.
            $table->json('formatting_options')->nullable(); // Font, colors, layout

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->uuid('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['report_type', 'report_category']);
            $table->index(['is_default', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
