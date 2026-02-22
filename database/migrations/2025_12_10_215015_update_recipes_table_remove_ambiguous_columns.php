<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Add product_type_id foreign key column
            if (! Schema::hasColumn('recipes', 'product_type_id')) {
                $table->unsignedBigInteger('product_type_id')->nullable()->after('product_type');
                $table->foreign('product_type_id')
                    ->references('id')
                    ->on('product_types')
                    ->onDelete('restrict');
            }
        });

        // Migrate data from product_type enum to product_type_id foreign key
        // Create a mapping from enum values to product type IDs
        $this->migrateProductTypeData();

        Schema::table('recipes', function (Blueprint $table) {
            // Remove the ambiguous columns
            if (Schema::hasColumn('recipes', 'recipe_yield_weight')) {
                $table->dropColumn('recipe_yield_weight');
            }
            if (Schema::hasColumn('recipes', 'unit_weight')) {
                $table->dropColumn('unit_weight');
            }
            if (Schema::hasColumn('recipes', 'yield_percentage')) {
                $table->dropColumn('yield_percentage');
            }

            // Drop the old product_type enum column
            if (Schema::hasColumn('recipes', 'product_type')) {
                $table->dropColumn('product_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // Re-add the ambiguous columns
            if (! Schema::hasColumn('recipes', 'recipe_yield_weight')) {
                $table->decimal('recipe_yield_weight', 10, 2)->nullable()->after('product_type_id');
            }
            if (! Schema::hasColumn('recipes', 'unit_weight')) {
                $table->decimal('unit_weight', 10, 2)->nullable()->after('recipe_yield_weight');
            }
            if (! Schema::hasColumn('recipes', 'yield_percentage')) {
                $table->decimal('yield_percentage', 10, 2)->default(100)->after('unit_weight');
            }

            // Re-add the product_type enum column
            if (! Schema::hasColumn('recipes', 'product_type')) {
                $table->enum('product_type', ['gelato_base', 'gelato_flavor', 'pastry', 'hot_kitchen', 'beverage'])->after('sku');
            }

            // Drop the product_type_id column
            if (Schema::hasColumn('recipes', 'product_type_id')) {
                $table->dropForeign(['product_type_id']);
                $table->dropColumn('product_type_id');
            }
        });
    }

    /**
     * Migrate data from product_type enum to product_type_id foreign key
     */
    private function migrateProductTypeData()
    {
        // Map enum values to department-based product type IDs
        // For each recipe, find the matching ProductType in the same department
        $recipes = DB::table('recipes')->whereNotNull('product_type')->get();

        foreach ($recipes as $recipe) {
            // Get the department for this recipe
            $productType = DB::table('product_types')
                ->where('department_id', $recipe->department_id)
                // Match by various fields - this is flexible depending on your naming convention
                ->where(function ($query) use ($recipe) {
                    $query->where('code', $recipe->product_type)
                        ->orWhere('name', str_replace('_', ' ', ucwords(str_replace('_', ' ', $recipe->product_type), '_')));
                })
                ->first();

            if ($productType) {
                DB::table('recipes')
                    ->where('id', $recipe->id)
                    ->update(['product_type_id' => $productType->id]);
            }
        }
    }
};
