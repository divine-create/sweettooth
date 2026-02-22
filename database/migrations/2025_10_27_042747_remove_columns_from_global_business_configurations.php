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
        Schema::table('global_business_configurations', function (Blueprint $table) {
            $table->dropColumn('storage_settings');
            $table->dropColumn('subscription_plan');
            $table->integer('backup_interval')->default(2);
            $table->string('backup_period')->default('months');
            $table->boolean('auto_backup')->default(true);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_business_configurations', function (Blueprint $table) {
            //
        });
    }
};
