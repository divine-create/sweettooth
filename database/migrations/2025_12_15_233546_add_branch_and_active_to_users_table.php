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
        Schema::table('users', function (Blueprint $table) {
            // Add branch_id for role-based branch assignment
            if (! Schema::hasColumn('users', 'branch_id')) {
                $table->string('branch_id')->nullable()->after('email');
                $table->index('branch_id');
            }

            // Add is_active for account status management
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('branch_id');
                $table->index('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['branch_id', 'is_active']);
        });
    }
};
