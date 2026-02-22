<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\Item;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductionRecipes extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-recipes
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign recipes to}
        {--department-map= : JSON map or file of location => department_id}
        {--product-type-map= : JSON map or file of location => product_type_id}
        {--create-missing-departments : Create departments when missing}
        {--department-category-id= : Category UUID for auto-created departments}
        {--create-missing-product-types : Create product types when missing}
        {--create-missing-products : Create products when missing}
        {--create-missing-items : Create items when missing}
        {--created-by-id= : User UUID to set as created_by}
        {--mode=merge : merge or replace}
        {--force : Required when using mode=replace}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import recipes and recipe ingredients from production JSON files';

    public function handle(): int
    {
        $path = $this->argument('path') ?: base_path('real_data/receips-sweetooth-main');
        $mode = strtolower($this->option('mode') ?: 'merge');
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id.');
            return 1;
        }

        if ($mode === 'replace') {
            if (! $this->option('force')) {
                $this->error('Replace mode requires --force.');
                return 1;
            }

            if (! $dryRun) {
                $this->warn('Replace mode: truncating production_records, daily_produces, recipe_ingredients, recipes.');
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('production_records')->truncate();
                DB::table('daily_produces')->truncate();
                RecipeIngredient::truncate();
                Recipe::truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        $departmentMap = $this->loadMapOption($this->option('department-map'));
        $productTypeMap = $this->loadMapOption($this->option('product-type-map'));

        $createdById = $this->option('created-by-id');
        if (! $createdById) {
            $createdById = User::query()->value('id');
        }
        if (! $createdById) {
            $this->error('No users found. Provide --created-by-id.');
            return 1;
        }

        $rows = $this->loadJsonRows($path);
        if (empty($rows)) {
            $this->warn('No rows to import.');
            return 0;
        }

        $groups = [];
        foreach ($rows as $row) {
            [$productName, $productExternalId] = $this->parseNameAndExternalId($row['produced_product'] ?? '');
            if ($productName === '') {
                continue;
            }

            [$producedQty, $productUomToken] = $this->parseQuantityAndUom($row['quantity_produced'] ?? '');
            if ($producedQty <= 0) {
                continue;
            }

            $location = $row['table_info']['location'] ?? null;
            $groupKey = $productExternalId ?: $this->normalizeKey($productName);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'product_name' => $productName,
                    'product_external_id' => $productExternalId,
                    'product_uom_tokens' => [],
                    'locations' => [],
                    'ingredients' => [],
                ];
            }

            if ($productUomToken) {
                $groups[$groupKey]['product_uom_tokens'][$productUomToken] = ($groups[$groupKey]['product_uom_tokens'][$productUomToken] ?? 0) + 1;
            }

            if ($location) {
                $locKey = $this->normalizeKey($location);
                $groups[$groupKey]['locations'][$locKey] = ($groups[$groupKey]['locations'][$locKey] ?? 0) + 1;
            }

            foreach ($row['ingredients'] ?? [] as $ingredient) {
                [$ingredientName, $ingredientExternalId] = $this->parseNameAndExternalId($ingredient['ingredient'] ?? '');
                if ($ingredientName === '') {
                    continue;
                }

                [$inputQty, $ingredientUomToken] = $this->parseQuantityAndUom($ingredient['input_qty'] ?? '');
                if ($inputQty <= 0) {
                    continue;
                }

                $ratio = $inputQty / $producedQty;
                $wastePercent = $this->parsePercent($ingredient['wastage_percent'] ?? '');
                $totalPrice = $this->parseMoney($ingredient['total_price'] ?? '');
                $costPerUnit = $totalPrice > 0 ? ($totalPrice / $inputQty) : 0.0;

                $ingredientKey = $ingredientExternalId ?: $this->normalizeKey($ingredientName);
                if (! isset($groups[$groupKey]['ingredients'][$ingredientKey])) {
                    $groups[$groupKey]['ingredients'][$ingredientKey] = [
                        'name' => $ingredientName,
                        'external_id' => $ingredientExternalId,
                        'uom_tokens' => [],
                        'ratio_sum' => 0.0,
                        'ratio_count' => 0,
                        'waste_sum' => 0.0,
                        'waste_count' => 0,
                        'cost_sum' => 0.0,
                        'cost_count' => 0,
                    ];
                }

                $ing = &$groups[$groupKey]['ingredients'][$ingredientKey];
                if ($ingredientUomToken) {
                    $ing['uom_tokens'][$ingredientUomToken] = ($ing['uom_tokens'][$ingredientUomToken] ?? 0) + 1;
                }
                $ing['ratio_sum'] += $ratio;
                $ing['ratio_count']++;
                $ing['waste_sum'] += $wastePercent;
                $ing['waste_count']++;
                if ($costPerUnit > 0) {
                    $ing['cost_sum'] += $costPerUnit;
                    $ing['cost_count']++;
                }
                unset($ing);
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $location = '';
            if (! empty($group['locations'])) {
                arsort($group['locations']);
                $location = array_key_first($group['locations']);
            }

            $departmentId = $this->resolveDepartmentId(
                $location,
                $branchId,
                $departmentMap,
                (bool) $this->option('create-missing-departments'),
                $this->option('department-category-id')
            );

            if (! $departmentId) {
                $this->warn("Skipping recipe {$group['product_name']}: no department for location {$location}.");
                $skipped++;
                continue;
            }

            $productTypeId = $this->resolveProductTypeId(
                $location,
                $departmentId,
                $productTypeMap,
                (bool) $this->option('create-missing-product-types')
            );

            if (! $productTypeId) {
                $this->warn("Skipping recipe {$group['product_name']}: no product type for location {$location}.");
                $skipped++;
                continue;
            }

            $productQuery = Product::query();
            if ($group['product_external_id']) {
                $productQuery->where('sku', $group['product_external_id']);
            } else {
                $productQuery->whereRaw('LOWER(name) = ?', [strtolower($group['product_name'])]);
            }
            $product = $productQuery->first();

            if (! $product && $this->option('create-missing-products')) {
                $uomToken = '';
                if (! empty($group['product_uom_tokens'])) {
                    arsort($group['product_uom_tokens']);
                    $uomToken = array_key_first($group['product_uom_tokens']);
                }
                $uomId = $this->resolveUomId($uomToken);

                if (! $dryRun) {
                    $product = Product::create([
                        'name' => $group['product_name'],
                        'sku' => $group['product_external_id'] ?: $this->generateSku($group['product_name'], Product::class),
                        'branch_id' => $branchId,
                        'product_type_id' => $productTypeId,
                        'sales_department_id' => $departmentId,
                        'price' => 0,
                        'cost' => 0,
                        'shelf_life_days' => 0,
                        'uom_id' => $uomId,
                        'is_active' => true,
                        'is_available' => true,
                    ]);
                }
            }

            if (! $product) {
                $this->warn("Skipping recipe {$group['product_name']}: product not found.");
                $skipped++;
                continue;
            }

            $uomToken = '';
            if (! empty($group['product_uom_tokens'])) {
                arsort($group['product_uom_tokens']);
                $uomToken = array_key_first($group['product_uom_tokens']);
            }
            $uomId = $this->resolveUomId($uomToken) ?: $product->uom_id;

            $recipeSku = 'R-' . ($product->sku ?: $this->generateSku($product->name, Recipe::class));
            $recipe = Recipe::query()->where('product_id', $product->id)->first();

            if (! $dryRun) {
                if ($recipe) {
                    $recipe->update([
                        'branch_id' => $branchId,
                        'department_id' => $departmentId,
                        'product_name' => $product->name,
                        'sku' => $recipeSku,
                        'product_type_id' => $productTypeId,
                        'uom_id' => $uomId,
                        'yield_quantity' => 1,
                        'status' => 'active',
                        'created_by_id' => $createdById,
                        'created_by_type' => User::class,
                    ]);
                    $updated++;
                } else {
                    $recipe = Recipe::create([
                        'branch_id' => $branchId,
                        'department_id' => $departmentId,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'sku' => $recipeSku,
                        'product_type_id' => $productTypeId,
                        'uom_id' => $uomId,
                        'yield_quantity' => 1,
                        'status' => 'active',
                        'created_by_id' => $createdById,
                        'created_by_type' => User::class,
                    ]);
                    $created++;
                }

                RecipeIngredient::where('recipe_id', $recipe->id)->delete();
            } else {
                $recipe = $recipe ?: new Recipe();
            }

            foreach ($group['ingredients'] as $ingredientData) {
                $ingredientQuery = Item::query()->where('branch_id', $branchId);
                if ($ingredientData['external_id']) {
                    $ingredientQuery->where('sku', $ingredientData['external_id']);
                } else {
                    $ingredientQuery->whereRaw('LOWER(name) = ?', [strtolower($ingredientData['name'])]);
                }
                $item = $ingredientQuery->first();

                if (! $item && $this->option('create-missing-items')) {
                    $ingredientUomToken = '';
                    if (! empty($ingredientData['uom_tokens'])) {
                        arsort($ingredientData['uom_tokens']);
                        $ingredientUomToken = array_key_first($ingredientData['uom_tokens']);
                    }
                    $ingredientUomId = $this->resolveUomId($ingredientUomToken);

                    if (! $dryRun) {
                        $item = Item::create([
                            'branch_id' => $branchId,
                            'name' => $ingredientData['name'],
                            'sku' => $ingredientData['external_id'] ?: $this->generateSku($ingredientData['name'], Item::class),
                            'category' => $this->classifyCategory($ingredientData['name']),
                            'uom_id' => $ingredientUomId,
                            'status' => 'active',
                        ]);
                    }
                }

                if (! $item) {
                    $this->warn("Skipping ingredient {$ingredientData['name']} for {$group['product_name']}: item not found.");
                    continue;
                }

                $ingredientUomToken = '';
                if (! empty($ingredientData['uom_tokens'])) {
                    arsort($ingredientData['uom_tokens']);
                    $ingredientUomToken = array_key_first($ingredientData['uom_tokens']);
                }
                $ingredientUomId = $this->resolveUomId($ingredientUomToken) ?: $item->uom_id;

                $ratio = $ingredientData['ratio_count'] > 0
                    ? $ingredientData['ratio_sum'] / $ingredientData['ratio_count']
                    : 0.0;
                $waste = $ingredientData['waste_count'] > 0
                    ? $ingredientData['waste_sum'] / $ingredientData['waste_count']
                    : 0.0;
                $costPerUnit = $ingredientData['cost_count'] > 0
                    ? $ingredientData['cost_sum'] / $ingredientData['cost_count']
                    : 0.0;

                if (! $dryRun) {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'item_id' => $item->id,
                        'quantity' => $ratio,
                        'uom_id' => $ingredientUomId,
                        'cost_per_unit' => $costPerUnit,
                        'waste_percentage' => $waste,
                        'sort_order' => 0,
                    ]);
                }
            }
        }

        $this->info("Recipes processed: " . count($groups));
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");

        return 0;
    }
}
