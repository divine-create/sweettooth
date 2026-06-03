<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Global switch (super-admin controlled) that toggles the approval workflow
     * on or off for the whole system. When disabled, sensitive actions execute
     * immediately instead of waiting for an approver, while still being audited.
     */
    public function up(): void
    {
        Schema::table('global_security_accesses', function (Blueprint $table) {
            $table->boolean('approval_required')->default(true)->after('audit_logs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_security_accesses', function (Blueprint $table) {
            $table->dropColumn('approval_required');
        });
    }
};
