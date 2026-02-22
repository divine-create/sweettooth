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
        Schema::create('report_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained()->cascadeOnDelete();
            $table->uuidMorphs('reportable'); // department_reports or compiled_reports

            $table->string('recipient_type'); // employee, user, external
            $table->uuid('recipient_id')->nullable(); // Employee or User ID
            $table->string('recipient_email')->nullable(); // For external recipients
            $table->string('recipient_name')->nullable();

            $table->enum('distribution_method', ['email', 'download', 'notification'])->default('email');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();

            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            // Indexes with custom short names
            $table->index(['branch_id', 'reportable_type', 'reportable_id'], 'rd_branch_reportable_idx');
            $table->index(['recipient_type', 'recipient_id'], 'rd_recipient_idx');
            $table->index(['status', 'sent_at'], 'rd_status_sent_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_distributions');
    }
};
