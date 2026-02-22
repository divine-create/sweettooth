<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds employee-related columns to users table to consolidate
     * dual authentication system (users + employees) into single table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Link user to a branch (nullable for super admins)
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->uuid('branch_id')->nullable()->after('id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            }

            // Track if user is active
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('branch_id');
            }

            // Employee-specific fields (moved from employees table)
            if (! Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->nullable()->unique()->after('is_active');
            }

            if (! Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('employee_id');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }

            if (! Schema::hasColumn('users', 'manager_id')) {
                $table->uuid('manager_id')->nullable()->after('department_id');
                $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('manager_id');
            }

            if (! Schema::hasColumn('users', 'hire_date')) {
                $table->timestamp('hire_date')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'employment_status')) {
                $table->enum('employment_status', ['active', 'suspended', 'terminated', 'on_leave'])
                    ->default('active')
                    ->after('hire_date');
            }

            // Track user type for backwards compatibility during transition
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', ['admin', 'employee'])->default('admin')->after('employment_status');
            }

            // Soft deletes for safety
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes()->after('user_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['branch_id']);
            $table->dropForeignKeyIfExists(['department_id']);
            $table->dropForeignKeyIfExists(['manager_id']);

            $table->dropColumn([
                'branch_id',
                'is_active',
                'employee_id',
                'department_id',
                'manager_id',
                'phone',
                'hire_date',
                'employment_status',
                'user_type',
                'deleted_at',
            ]);
        });
    }
};
