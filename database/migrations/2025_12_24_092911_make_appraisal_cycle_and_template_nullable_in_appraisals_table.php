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
        if (Schema::hasTable('appraisals')) {
            Schema::table('appraisals', function (Blueprint $table) {
                if (Schema::hasColumn('appraisals', 'appraisal_cycle_id')) {
                    $table->foreignId('appraisal_cycle_id')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appraisals')) {
            Schema::table('appraisals', function (Blueprint $table) {
                if (Schema::hasColumn('appraisals', 'appraisal_cycle_id')) {
                    $table->foreignId('appraisal_cycle_id')->nullable(false)->change();
                }
            });
        }
    }
};
