<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_produces', function (Blueprint $table) {
            $table->unsignedBigInteger('production_request_id')->nullable()->after('shift_id');
            $table->index('production_request_id');
        });

        // Backfill where possible (first matching request by shift+recipe)
        DB::statement("
            UPDATE daily_produces dp
            JOIN production_requests pr
              ON pr.shift_id = dp.shift_id
             AND pr.recipe_id = dp.recipe_id
            SET dp.production_request_id = pr.id
            WHERE dp.production_request_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('daily_produces', function (Blueprint $table) {
            $table->dropIndex(['production_request_id']);
            $table->dropColumn('production_request_id');
        });
    }
};
