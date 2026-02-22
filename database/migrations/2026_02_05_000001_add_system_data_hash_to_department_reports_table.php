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
        Schema::table('department_reports', function (Blueprint $table) {
            $table->string('system_data_hash', 64)->nullable()->after('charts_data');
            $table->unsignedInteger('system_data_version')->default(1)->after('system_data_hash');
            $table->timestamp('system_data_locked_at')->nullable()->after('system_data_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_reports', function (Blueprint $table) {
            $table->dropColumn(['system_data_hash', 'system_data_version', 'system_data_locked_at']);
        });
    }
};
