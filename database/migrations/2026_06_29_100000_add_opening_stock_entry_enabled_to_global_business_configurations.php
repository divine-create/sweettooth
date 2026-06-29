<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_business_configurations', function (Blueprint $table) {
            // Go-live opening-stock entry kill-switch. Enabled by default so the
            // tool is usable during go-live; an MD locks it (sets false) afterwards.
            $table->boolean('opening_stock_entry_enabled')->default(true)->after('auto_backup');
        });
    }

    public function down(): void
    {
        Schema::table('global_business_configurations', function (Blueprint $table) {
            $table->dropColumn('opening_stock_entry_enabled');
        });
    }
};
