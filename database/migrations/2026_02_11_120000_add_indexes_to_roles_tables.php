<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->index(['name', 'guard_name'], 'idx_roles_name_guard');
            $table->index('created_at', 'idx_roles_created_at');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->index(['role_id', 'permission_id'], 'idx_role_perm_role_perm');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index(['role_id', 'model_type', 'model_id'], 'idx_model_has_roles_composite');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('idx_roles_name_guard');
            $table->dropIndex('idx_roles_created_at');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_role_perm_role_perm');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('idx_model_has_roles_composite');
        });
    }
};
