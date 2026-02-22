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
        Schema::create('compiled_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->uuid('compiled_by_id')->nullable();
            $table->string('compiled_by_type')->nullable();

            $table->string('compilation_title');
            $table->text('compilation_description')->nullable();
            $table->date('compilation_date');
            $table->date('period_from');
            $table->date('period_to');

            $table->json('included_reports'); // Array of department_report IDs
            $table->json('executive_summary'); // High-level insights
            $table->json('key_metrics'); // Combined metrics across departments
            $table->json('recommendations')->nullable(); // Recommendations to MD

            $table->enum('status', ['draft', 'pending_approval', 'approved', 'sent_to_md', 'reviewed_by_md'])->default('draft');
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_to_md_at')->nullable();

            $table->foreignUuid('md_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('md_reviewed_at')->nullable();
            $table->text('md_feedback')->nullable();

            $table->string('file_path')->nullable(); // Path to compiled PDF/Excel

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['branch_id', 'compilation_date']);
            $table->index(['status', 'compilation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compiled_reports');
    }
};
