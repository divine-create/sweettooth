<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Widens cost_per_unit from decimal(10,4) to decimal(15,4) to support
     * high-value ingredients (e.g. imported spirits/alcohol) whose unit cost
     * exceeds the old 999,999.9999 ceiling and caused a 500 server error when
     * adding a recipe.
     */
    public function up(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 15, 4)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->decimal('cost_per_unit', 10, 4)->default(0)->change();
        });
    }
};

