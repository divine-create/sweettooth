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
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description');
            $table->uuid('manager_user_id')->nullable()->after('is_active');
            $table->foreign('manager_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('is_active');
            $table->index('manager_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_user_id']);
            $table->dropColumn(['is_active', 'manager_user_id']);
        });
    }
};