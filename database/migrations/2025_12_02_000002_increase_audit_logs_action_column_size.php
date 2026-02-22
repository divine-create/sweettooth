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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Increase action column size from 50 to 255 to accommodate approval action names
            // e.g., "approve_update_roles_37b32b0e-e9fa-3436-924b-9b59eca605d6"
            $table->string('action', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Revert to original size
            $table->string('action', 50)->change();
        });
    }
};
