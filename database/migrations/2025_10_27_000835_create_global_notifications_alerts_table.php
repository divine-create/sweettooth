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
        Schema::create('global_notifications_alerts', function (Blueprint $table) {
            $table->id();
            $table->json('alerts')->default(json_encode(['email', 'sms']));
            $table->string('task_todo', 50)->default('enabled');
            $table->timestamps();
        });

        Schema::create('branch_notifications_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->json('alerts')->nullable();
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_notifications_alerts');
        Schema::dropIfExists('global_notifications_alerts');
    }
};
