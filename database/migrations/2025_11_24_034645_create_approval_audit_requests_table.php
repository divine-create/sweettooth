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
        Schema::create('approval_audit_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('requester_id')->nullable();
            $table->string('requester_type')->nullable();
            $table->uuid('approver_id')->nullable();
            $table->string('approver_type')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comment')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('denied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_audit_requests');
    }
};
