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
        Schema::create('role_category_constraints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role_name');
            $table->uuid('category_id');
            $table->enum('department_type', ['specific', 'category_wide', 'branch_wide'])->default('specific');
            $table->json('allowed_department_slugs')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('department_categories')->onDelete('cascade');
            $table->unique(['role_name', 'category_id'], 'unique_role_category');
            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_category_constraints');
    }
};