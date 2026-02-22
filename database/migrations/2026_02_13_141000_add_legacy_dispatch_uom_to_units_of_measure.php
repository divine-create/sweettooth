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
        Schema::table('units_of_measure', function (Blueprint $table) {
            if (! Schema::hasColumn('units_of_measure', 'legacy_dispatch_uom')) {
                $table->string('legacy_dispatch_uom')->nullable()->after('symbol');
                $table->index('legacy_dispatch_uom', 'idx_uom_legacy_dispatch');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units_of_measure', function (Blueprint $table) {
            if (Schema::hasColumn('units_of_measure', 'legacy_dispatch_uom')) {
                $table->dropIndex('idx_uom_legacy_dispatch');
                $table->dropColumn('legacy_dispatch_uom');
            }
        });
    }
};
