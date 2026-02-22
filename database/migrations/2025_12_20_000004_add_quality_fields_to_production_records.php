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
        // Add quality_score field to production_records if not exists
        Schema::table('production_records', function (Blueprint $table) {
            if (! Schema::hasColumn('production_records', 'quality_score')) {
                $table->decimal('quality_score', 5, 2)->nullable()->after('quality_status');
            }
            if (! Schema::hasColumn('production_records', 'weight_deviation')) {
                $table->decimal('weight_deviation', 8, 3)->nullable()->after('quality_score');
            }
            if (! Schema::hasColumn('production_records', 'decoration_grade')) {
                $table->enum('decoration_grade', ['A', 'B', 'C', 'D'])->nullable()->after('weight_deviation');
            }
            if (! Schema::hasColumn('production_records', 'shelf_life_days')) {
                $table->integer('shelf_life_days')->nullable()->after('decoration_grade');
            }
            if (! Schema::hasColumn('production_records', 'overrun_percentage')) {
                $table->decimal('overrun_percentage', 5, 2)->nullable()->after('shelf_life_days');
            }
        });

        // Add temperature_alert field to production_records for kitchen monitoring
        Schema::table('production_records', function (Blueprint $table) {
            if (! Schema::hasColumn('production_records', 'temperature_alert')) {
                $table->boolean('temperature_alert')->default(false)->after('overrun_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_records', function (Blueprint $table) {
            $table->dropColumn([
                'quality_score',
                'weight_deviation',
                'decoration_grade',
                'shelf_life_days',
                'overrun_percentage',
                'temperature_alert',
            ]);
        });
    }
};
