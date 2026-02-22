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
        // Add protection columns to roles table
        Schema::table('roles', function (Blueprint $table) {
            // Mark core system roles that cannot be deleted
            if (! Schema::hasColumn('roles', 'is_protected')) {
                $table->boolean('is_protected')->default(false)->after('guard_name');
                $table->index('is_protected');
            }
            if (! Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('is_protected');
            }
            if (! Schema::hasColumn('roles', 'display_order')) {
                $table->integer('display_order')->default(0)->after('description');
            }
        });

        // Add columns to permissions for better organization
        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'is_protected')) {
                $table->boolean('is_protected')->default(false)->after('guard_name');
                $table->index('is_protected');
            }
        });

        // Mark existing critical roles as protected
        \Spatie\Permission\Models\Role::query()
            ->whereIn('name', ['Super Admin', 'Managing Director', 'Admin'])
            ->update(['is_protected' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'is_protected')) {
                $table->dropIndex(['is_protected']);
                $table->dropColumn(['is_protected']);
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'is_protected')) {
                $table->dropIndex(['is_protected']);
            }
            $columnsToDrop = [];
            if (Schema::hasColumn('roles', 'is_protected')) {
                $columnsToDrop[] = 'is_protected';
            }
            if (Schema::hasColumn('roles', 'description')) {
                $columnsToDrop[] = 'description';
            }
            if (Schema::hasColumn('roles', 'display_order')) {
                $columnsToDrop[] = 'display_order';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
