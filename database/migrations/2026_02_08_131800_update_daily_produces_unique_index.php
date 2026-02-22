<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_produces', function (Blueprint $table) {
            // Keep FK constraints happy by adding separate indexes first
            $table->index('shift_id');
            $table->index('recipe_id');

            $table->dropUnique(['shift_id', 'recipe_id']);
            $table->unique('production_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('daily_produces', function (Blueprint $table) {
            $table->dropUnique(['production_request_id']);
            $table->dropIndex(['shift_id']);
            $table->dropIndex(['recipe_id']);
            $table->unique(['shift_id', 'recipe_id']);
        });
    }
};
