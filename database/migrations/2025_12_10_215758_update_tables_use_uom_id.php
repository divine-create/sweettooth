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
        // Map old enum values to new UOM codes
        $uomMap = [
            'grams' => 'g',
            'kg' => 'kg',
            'liters' => 'l',
            'ml' => 'ml',
            'pcs' => 'pcs',
            'units' => 'unit',
        ];

        // Update items table
        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                if (! Schema::hasColumn('items', 'uom_id')) {
                    $table->unsignedBigInteger('uom_id')->nullable()->after('uom');
                    $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
                }
            });

            // Migrate data from uom enum to uom_id
            $this->migrateItemsUOM($uomMap);

            // Drop old column
            Schema::table('items', function (Blueprint $table) {
                if (Schema::hasColumn('items', 'uom')) {
                    $table->dropColumn('uom');
                }
            });
        }

        // Update products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'uom_id')) {
                    $table->unsignedBigInteger('uom_id')->nullable()->after('uom');
                    $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
                }
            });

            // Migrate data from uom enum to uom_id
            $this->migrateProductsUOM($uomMap);

            // Drop old column
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'uom')) {
                    $table->dropColumn('uom');
                }
            });
        }

        // Update recipes table
        if (Schema::hasTable('recipes')) {
            Schema::table('recipes', function (Blueprint $table) {
                if (! Schema::hasColumn('recipes', 'uom_id')) {
                    $table->unsignedBigInteger('uom_id')->nullable()->after('uom');
                    $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
                }
            });

            // Migrate data from uom enum to uom_id
            $this->migrateRecipesUOM($uomMap);

            // Drop old column
            Schema::table('recipes', function (Blueprint $table) {
                if (Schema::hasColumn('recipes', 'uom')) {
                    $table->dropColumn('uom');
                }
            });
        }

        // Update recipe_ingredients table
        if (Schema::hasTable('recipe_ingredients')) {
            Schema::table('recipe_ingredients', function (Blueprint $table) {
                if (! Schema::hasColumn('recipe_ingredients', 'uom_id')) {
                    $table->unsignedBigInteger('uom_id')->nullable()->after('uom');
                    $table->foreign('uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
                }
            });

            // Migrate data from uom enum to uom_id
            $this->migrateRecipeIngredientsUOM($uomMap);

            // Drop old column
            Schema::table('recipe_ingredients', function (Blueprint $table) {
                if (Schema::hasColumn('recipe_ingredients', 'uom')) {
                    $table->dropColumn('uom');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $uomReverseMap = [
            'g' => 'grams',
            'kg' => 'kg',
            'l' => 'liters',
            'ml' => 'ml',
            'pcs' => 'pcs',
            'unit' => 'units',
        ];

        // Re-add enum columns and migrate back
        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                if (! Schema::hasColumn('items', 'uom')) {
                    $table->enum('uom', ['grams', 'kg', 'liters', 'ml', 'pcs', 'units'])->default('grams')->after('uom_id');
                }
            });

            // Migrate back
            foreach (DB::table('items')->get() as $item) {
                if ($item->uom_id) {
                    $uomCode = DB::table('units_of_measure')->find($item->uom_id)->code ?? 'grams';
                    $enumValue = $uomReverseMap[$uomCode] ?? 'grams';
                    DB::table('items')->where('id', $item->id)->update(['uom' => $enumValue]);
                }
            }

            Schema::table('items', function (Blueprint $table) {
                if (Schema::hasColumn('items', 'uom_id')) {
                    $table->dropForeign(['uom_id']);
                    $table->dropColumn('uom_id');
                }
            });
        }

        // Similar for other tables...
    }

    private function migrateItemsUOM($uomMap)
    {
        $items = DB::table('items')->whereNotNull('uom')->get();
        foreach ($items as $item) {
            $uomCode = $uomMap[$item->uom] ?? 'unit';
            $uomId = DB::table('units_of_measure')->where('code', $uomCode)->value('id');
            if ($uomId) {
                DB::table('items')->where('id', $item->id)->update(['uom_id' => $uomId]);
            }
        }
    }

    private function migrateProductsUOM($uomMap)
    {
        $products = DB::table('products')->whereNotNull('uom')->get();
        foreach ($products as $product) {
            $uomCode = $uomMap[$product->uom] ?? 'unit';
            $uomId = DB::table('units_of_measure')->where('code', $uomCode)->value('id');
            if ($uomId) {
                DB::table('products')->where('id', $product->id)->update(['uom_id' => $uomId]);
            }
        }
    }

    private function migrateRecipesUOM($uomMap)
    {
        $recipes = DB::table('recipes')->whereNotNull('uom')->get();
        foreach ($recipes as $recipe) {
            $uomCode = $uomMap[$recipe->uom] ?? 'unit';
            $uomId = DB::table('units_of_measure')->where('code', $uomCode)->value('id');
            if ($uomId) {
                DB::table('recipes')->where('id', $recipe->id)->update(['uom_id' => $uomId]);
            }
        }
    }

    private function migrateRecipeIngredientsUOM($uomMap)
    {
        $ingredients = DB::table('recipe_ingredients')->whereNotNull('uom')->get();
        foreach ($ingredients as $ingredient) {
            $uomCode = $uomMap[$ingredient->uom] ?? 'unit';
            $uomId = DB::table('units_of_measure')->where('code', $uomCode)->value('id');
            if ($uomId) {
                DB::table('recipe_ingredients')->where('id', $ingredient->id)->update(['uom_id' => $uomId]);
            }
        }
    }
};
