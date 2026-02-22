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
        Schema::dropIfExists('approval_requests');

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();

            // Who requested the action (polymorphic)
            $table->uuid('requested_by_id');
            $table->string('requested_by_type');

            // What action is being requested
            $table->string('action');

            // What model is affected (polymorphic)
            $table->uuid('auditable_id');
            $table->string('auditable_type');

            // Status tracking
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('reason')->nullable();

            // Metadata storage
            $table->json('metadata')->nullable();

            // Approval tracking
            $table->dateTime('approved_at')->nullable();
            $table->uuid('approved_by_id')->nullable();
            $table->string('approved_by_type')->nullable();

            // Execution tracking
            $table->dateTime('executed_at')->nullable();
            $table->uuid('executed_by_id')->nullable();
            $table->string('executed_by_type')->nullable();

            // Rejection tracking
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('action');
            $table->index('requested_by_id');
            $table->index('auditable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
