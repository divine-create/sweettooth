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
        Schema::create('role_permission_audit_logs', function (Blueprint $table) {
            $table->id();

            // Action being performed
            $table->string('action'); // role_created, role_updated, role_deleted, permission_created, etc.

            // Model being changed
            $table->string('model_type'); // Role, Permission, User
            $table->unsignedBigInteger('model_id');

            // User who made the change
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();

            // Request context
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Change details
            $table->longText('data'); // JSON encoded change data

            // Sensitivity flags
            $table->boolean('is_protected')->default(false); // Was a protected role/permission involved?

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast queries
            $table->index('model_type');
            $table->index('model_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('is_protected');
            $table->index('created_at');
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permission_audit_logs');
    }
};
