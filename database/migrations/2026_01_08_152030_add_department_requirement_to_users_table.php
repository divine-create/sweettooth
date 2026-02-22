<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing foreign key if it exists using try-catch
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
            });
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue
        }

        // Add index for faster lookups by department if not exists
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('department_id', 'users_department_id_index');
            });
        } catch (\Exception $e) {
            // Index already exists, continue
        }

        // Re-add the foreign key with ON DELETE SET NULL for flexibility
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key already exists, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
            });
        } catch (\Exception $e) {
            // Foreign key doesn't exist
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_department_id_index');
            });
        } catch (\Exception $e) {
            // Index doesn't exist
        }
    }
};
