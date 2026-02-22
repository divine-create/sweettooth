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
        Schema::table('recipes', function (Blueprint $table) {
            // Add yield weight and percentage fields to recipes
            $table->decimal('recipe_yield_weight', 10, 2)->nullable()->comment('Total weight/volume of batch yield');
            $table->decimal('yield_percentage', 5, 2)->default(100)->comment('Yield percentage relative to input');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['recipe_yield_weight', 'yield_percentage']);
        });
    }
};
