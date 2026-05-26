<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 36)->nullable();
            $table->string('branch_id', 36)->nullable();
            $table->string('session_id', 100)->nullable();

            // login | logout | failed_login | page_view | component_action
            $table->string('event_type', 50);

            // sales | inventory | accounting | hr | production | organization | analytics | admin | dashboard
            $table->string('module', 50)->nullable();

            $table->string('route_name', 200)->nullable();
            $table->string('url', 2000)->nullable();
            $table->string('component_class', 300)->nullable();
            $table->string('action_name', 200)->nullable();

            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Append-only table — no updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['branch_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
