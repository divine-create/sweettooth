<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_recipes_branch_status');
            $table->index(['department_id', 'product_type_id'], 'idx_recipes_dept_type');
            $table->index('product_type_id', 'idx_recipes_product_type');
            $table->index('created_at', 'idx_recipes_created_at');
        });

        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->index('recipe_id', 'idx_recipe_ingredients_recipe');
            $table->index('item_id', 'idx_recipe_ingredients_item');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->index('name', 'idx_items_name');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex('idx_recipes_branch_status');
            $table->dropIndex('idx_recipes_dept_type');
            $table->dropIndex('idx_recipes_product_type');
            $table->dropIndex('idx_recipes_created_at');
        });

        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropIndex('idx_recipe_ingredients_recipe');
            $table->dropIndex('idx_recipe_ingredients_item');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_name');
        });
    }
};
