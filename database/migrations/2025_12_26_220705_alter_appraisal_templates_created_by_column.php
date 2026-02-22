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
        if (Schema::hasTable('appraisal_templates') && Schema::hasColumn('appraisal_templates', 'created_by')) {
            Schema::table('appraisal_templates', function (Blueprint $table) {
                $table->string('created_by')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appraisal_templates') && Schema::hasColumn('appraisal_templates', 'created_by')) {
            Schema::table('appraisal_templates', function (Blueprint $table) {
                $table->string('created_by', 36)->nullable()->change();
            });
        }
    }
};
