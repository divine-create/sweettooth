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
        Schema::create('global_security_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('authentication', 50)->default('2fa');
            $table->string('audit_logs', 50)->default('enabled');
            $table->string('data_isolation', 50)->default('saas_company');
            $table->timestamps();
        });

        Schema::create('branch_security_accesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->string('local_logs', 50)->nullable(); // 'view'
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_security_accesses');
        Schema::dropIfExists('global_security_accesses');
    }
};
