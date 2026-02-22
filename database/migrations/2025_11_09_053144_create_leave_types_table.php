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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Annual Leave, Sick Leave, Emergency Leave, etc.
            $table->string('code')->unique(); // ANNUAL, SICK, EMERGENCY, etc.
            $table->text('description')->nullable();
            $table->integer('default_days_per_year')->default(0); // Default allocation per year
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_document')->default(false); // e.g., sick leave may require medical certificate
            $table->integer('max_consecutive_days')->nullable(); // Max consecutive days allowed
            $table->integer('min_notice_days')->default(0); // Minimum notice period required
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('color')->default('#3b82f6'); // For UI display
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
