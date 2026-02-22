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
        Schema::create('global_branch_management', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_add', 50)->default('enabled');
            $table->string('branch_edit', 50)->default('enabled');
            $table->string('branch_delete', 50)->default('enabled');
            $table->string('branch_admin_assign', 50)->default('enabled');
            $table->string('inter_branch_transfer', 50)->default('enabled');
            $table->string('central_warehouse', 50)->default('enabled');
            $table->string('branch_hours', 50)->default('set');
            $table->string('saas_tenant', 50)->default('enabled');
            $table->timestamps();
        });

        Schema::create('branch_managements', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('branch_details', 50)->nullable(); // 'edit,approval'
            $table->string('operating_hours', 50)->nullable();
            $table->string('tax_override', 50)->nullable(); // 'view_only'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_branch_management');
        Schema::dropIfExists('branch_managements');
    }
};
