<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            $table->decimal('requested_units', 12, 2)->nullable()->after('planned_production_quantity');
            $table->integer('eta_override_minutes')->nullable()->after('requested_units');
        });

        // Backfill requested_units for existing records
        DB::table('production_requests')
            ->whereNull('requested_units')
            ->update(['requested_units' => DB::raw('planned_production_quantity')]);
    }

    public function down(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            $table->dropColumn(['requested_units', 'eta_override_minutes']);
        });
    }
};
