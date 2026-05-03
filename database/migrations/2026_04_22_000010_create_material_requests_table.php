<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->unsignedBigInteger('department_id');
            $table->string('request_number')->unique();
            $table->uuid('requested_by_id')->nullable();
            $table->string('requested_by_type')->nullable();
            $table->date('request_date');
            $table->enum('shift', ['morning', 'afternoon'])->default('morning');
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->index(['branch_id', 'status'], 'idx_mr_branch_status');
            $table->index('request_date', 'idx_mr_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};