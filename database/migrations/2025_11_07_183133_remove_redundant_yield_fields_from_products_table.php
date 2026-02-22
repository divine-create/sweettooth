<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove redundant yield fields from products table.
     * All yield information should come from Recipe model via recipes relationship.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove redundant yield fields - use Recipe.yield_quantity instead
            $table->dropColumn([
                'recipe_yield',
                'recipe_yield_weight',
                'yield_percentage',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore fields if migration is rolled back
            $table->decimal('recipe_yield', 10, 2)->nullable()->after('uom');
            $table->decimal('recipe_yield_weight', 10, 2)->nullable()->after('recipe_yield');
            $table->decimal('yield_percentage', 5, 2)->default(100)->after('unit_weight');
        });
    }
};
