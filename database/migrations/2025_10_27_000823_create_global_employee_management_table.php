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
        Schema::create('global_employee_managements', function (Blueprint $table) {
            $table->id();
            $table->json('roles')->default(json_encode(['create', 'edit']));
            $table->json('permissions')->default(json_encode(['pos', 'inventory', 'reports']));
            $table->json('staff_profiles')->default(json_encode(['add', 'edit', 'delete']));
            $table->json('departments')->default(json_encode(['sales', 'warehouse']));
            $table->string('shift_scheduling', 50)->default('enabled');
            $table->string('pin_login', 50)->default('enabled');
            $table->timestamps();
        });

        Schema::create('branch_employee_managements', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->json('branch_staff')->nullable(); // 'add,edit'
            $table->json('permissions_local')->nullable(); // 'pos,local_reports'
            $table->string('pin_assign', 50)->nullable(); // 'enabled'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_employee_managements');
        Schema::dropIfExists('global_employee_managements');
    }
};
