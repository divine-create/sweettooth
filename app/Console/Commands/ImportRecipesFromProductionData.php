<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Console\Command;

class ImportRecipesFromProductionData extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-recipes-new
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign recipes to}
        {--created-by-id= : User UUID to set as created_by}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import recipes and recipe ingredients from production JSON files';

    protected array $uomCache = [];
    protected array $productCache = [];
    protected array $itemCache = [];
    protected array $recipeCache = [];
    protected array $departmentCache = [];

    public function handle(): int
    {
        $path = $this->argument('path') ?: base_path('real_data/receips-sweetooth-main');
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id or create a branch first.');
            return 1;
        }

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

        // Group production records by product to aggregate recipe data
        $recipes = $this->extractUniqueRecipes($rows);
        
        $this->info("Found " . count($recipes) . " unique recipes to import.");

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $ingredientCreated = 0;
        $ingredientSkipped = 0;

        $bar = $this->output->createProgressBar(count($recipes));
        $bar->start();

        foreach ($recipes as $recipeData) {
            $result = $this->findOrCreateRecipe($recipeData, $branchId, $createdById, $dryRun);
            
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Recipe import completed:");
        $this->info("  Recipes created: {$created}");
        $this->info("  Recipes updated: {$updated}");
        $this->info("  Recipes skipped: {$skipped}");

        return 0;
    }

    protected function extractUniqueRecipes(array $rows): array
    {
        $recipes = [];

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
            $key = $productExternalId ?: $this->normalizeKey($productName);

            if (! isset($recipes[$key])) {
                $recipes[$key] = [
                    'product_name' => $productName,
                    'product_external_id' => $productExternalId,
                    'product_uom_tokens' => [],
                    'locations' => [],
                    'yield_quantities' => [],
                    'ingredients' => [],
                ];
            }

            if ($productUomToken) {
                $recipes[$key]['product_uom_tokens'][$productUomToken] = 
                    ($recipes[$key]['product_uom_tokens'][$productUomToken] ?? 0) + 1;
            }

            if ($location) {
                $locKey = $this->normalizeKey($location);
                $recipes[$key]['locations'][$locKey] = 
                    ($recipes[$key]['locations'][$locKey] ?? 0) + 1;
            }

            $recipes[$key]['yield_quantities'][] = $producedQty;

            // Aggregate ingredients
            foreach ($row['ingredients'] ?? [] as $ingredient) {
                [$ingredientName, $ingredientExternalId] = $this->parseNameAndExternalId($ingredient['ingredient'] ?? '');
                if ($ingredientName === '') {
                    continue;
                }

                [$inputQty, $ingredientUomToken] = $this->parseQuantityAndUom($ingredient['input_qty'] ?? '');
                if ($inputQty <= 0) {
                    continue;
                }

                $wastePercent = $this->parsePercent($ingredient['wastage_percent'] ?? '');
                $totalPrice = $this->parseMoney($ingredient['total_price'] ?? '');
                $costPerUnit = $totalPrice > 0 ? ($totalPrice / $inputQty) : 0;

                // Calculate ratio (ingredient quantity per unit of product)
                $ratio = $inputQty / $producedQty;

                $ingredientKey = $ingredientExternalId ?: $this->normalizeKey($ingredientName);
                
                if (! isset($recipes[$key]['ingredients'][$ingredientKey])) {
                    $recipes[$key]['ingredients'][$ingredientKey] = [
                        'name' => $ingredientName,
                        'external_id' => $ingredientExternalId,
                        'uom_tokens' => [],
                        'ratios' => [],
                        'waste_percents' => [],
                        'costs_per_unit' => [],
                    ];
                }

                $ing = &$recipes[$key]['ingredients'][$ingredientKey];
                
                if ($ingredientUomToken) {
                    $ing['uom_tokens'][$ingredientUomToken] = 
                        ($ing['uom_tokens'][$ingredientUomToken] ?? 0) + 1;
                }
                
                $ing['ratios'][] = $ratio;
                $ing['waste_percents'][] = $wastePercent;
                
                if ($costPerUnit > 0) {
                    $ing['costs_per_unit'][] = $costPerUnit;
                }
                
                unset($ing);
            }
        }

        // Calculate averages for each recipe
        foreach ($recipes as &$recipe) {
            // Most common UOM
            if (! empty($recipe['product_uom_tokens'])) {
                arsort($recipe['product_uom_tokens']);
                $recipe['most_common_uom'] = array_key_first($recipe['product_uom_tokens']);
            }

            // Most common location
            if (! empty($recipe['locations'])) {
                arsort($recipe['locations']);
                $recipe['most_common_location'] = array_key_first($recipe['locations']);
            }

            // Average yield quantity
            if (! empty($recipe['yield_quantities'])) {
                $recipe['avg_yield'] = array_sum($recipe['yield_quantities']) / count($recipe['yield_quantities']);
            }

            // Calculate ingredient averages
            foreach ($recipe['ingredients'] as &$ingredient) {
                if (! empty($ingredient['uom_tokens'])) {
                    arsort($ingredient['uom_tokens']);
                    $ingredient['most_common_uom'] = array_key_first($ingredient['uom_tokens']);
                }

                if (! empty($ingredient['ratios'])) {
                    $ingredient['avg_ratio'] = array_sum($ingredient['ratios']) / count($ingredient['ratios']);
                }

                if (! empty($ingredient['waste_percents'])) {
                    $ingredient['avg_waste'] = array_sum($ingredient['waste_percents']) / count($ingredient['waste_percents']);
                }

                if (! empty($ingredient['costs_per_unit'])) {
                    $ingredient['avg_cost'] = array_sum($ingredient['costs_per_unit']) / count($ingredient['costs_per_unit']);
                }
            }
        }

        return $recipes;
    }

    protected function findOrCreateRecipe(array $recipeData, string $branchId, string $createdById, bool $dryRun): string
    {
        $productName = $recipeData['product_name'];
        $productExternalId = $recipeData['product_external_id'];
        $key = $productExternalId ?: $this->normalizeKey($productName);

        // Check cache
        if (isset($this->recipeCache[$key])) {
            return 'skipped';
        }

        // Find or create product
        $product = $this->findProduct($productName, $productExternalId);
        
        if (! $product) {
            $this->warn("Skipping recipe {$productName}: product not found. Run import:production-products first.");
            return 'skipped';
        }

        // Get department from location
        $location = $recipeData['most_common_location'] ?? null;
        $departmentId = $this->getOrCreateDepartment($location, $branchId);

        if (! $departmentId) {
            $this->warn("Skipping recipe {$productName}: no department for location '{$location}'.");
            return 'skipped';
        }

        // Get UOM
        $uomToken = $recipeData['most_common_uom'] ?? null;
        $uomId = $uomToken ? $this->getUomIdByToken($uomToken) : null;
        if (! $uomId) {
            $uomId = $product->uom_id;
        }

        // Find existing recipe
        $recipe = Recipe::where('product_id', $product->id)->first();

        $yieldQuantity = $recipeData['avg_yield'] ?? 1;

        if ($recipe) {
            // Update existing recipe
            $updateData = [
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'product_name' => $productName,
                'uom_id' => $uomId ?: $recipe->uom_id,
                'yield_quantity' => $yieldQuantity,
                'status' => 'active',
            ];

            if (! $dryRun) {
                $recipe->update($updateData);
            }
        } else {
            // Create new recipe
            if (! $dryRun) {
                $recipeSku = 'R-' . ($product->sku ?: $this->generateRecipeSku($productName));
                
                $recipe = Recipe::create([
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'sku' => $recipeSku,
                    'product_type_id' => $product->product_type_id,
                    'uom_id' => $uomId ?: $product->uom_id,
                    'yield_quantity' => $yieldQuantity,
                    'status' => 'active',
                    'created_by_id' => $createdById,
                    'created_by_type' => User::class,
                ]);
            }
        }

        if (! $dryRun && $recipe) {
            // Delete existing ingredients and recreate
            RecipeIngredient::where('recipe_id', $recipe->id)->delete();

            // Add ingredients
            foreach ($recipeData['ingredients'] as $ingredientData) {
                $this->createRecipeIngredient($recipe, $ingredientData, $branchId);
            }

            $this->recipeCache[$key] = $recipe->id;
            return $recipe->wasRecentlyCreated ? 'created' : 'updated';
        }

        return 'created';
    }

    protected function findProduct(string $name, ?string $externalId): ?Product
    {
        $key = $externalId ?: $this->normalizeKey($name);
        
        if (isset($this->productCache[$key])) {
            return Product::find($this->productCache[$key]);
        }

        $query = Product::query();
        if ($externalId) {
            $query->where('sku', $externalId);
        } else {
            $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        }

        $product = $query->first();
        
        if ($product) {
            $this->productCache[$key] = $product->id;
        }

        return $product;
    }

    protected function createRecipeIngredient(Recipe $recipe, array $ingredientData, string $branchId): void
    {
        $ingredientName = $ingredientData['name'];
        $ingredientExternalId = $ingredientData['external_id'];
        $key = $ingredientExternalId ?: $this->normalizeKey($ingredientName);

        // Find item
        $item = $this->findItem($ingredientName, $ingredientExternalId, $branchId);
        
        if (! $item) {
            $this->warn("  Skipping ingredient {$ingredientName}: item not found. Run import:production-items first.");
            return;
        }

        // Get UOM
        $uomToken = $ingredientData['most_common_uom'] ?? null;
        $uomId = $uomToken ? $this->getUomIdByToken($uomToken) : null;
        if (! $uomId) {
            $uomId = $item->uom_id;
        }

        $quantity = $ingredientData['avg_ratio'] ?? 0;
        $wastePercent = $ingredientData['avg_waste'] ?? 0;
        // Cap waste percentage to 0-100 range
        $wastePercent = max(0, min(100, $wastePercent));
        $costPerUnit = $ingredientData['avg_cost'] ?? $item->unit_price ?? 0;

        RecipeIngredient::create([
            'recipe_id' => $recipe->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
            'uom_id' => $uomId,
            'cost_per_unit' => round($costPerUnit, 4),
            'waste_percentage' => round($wastePercent, 2),
            'sort_order' => 0,
        ]);
    }

    protected function findItem(string $name, ?string $externalId, string $branchId): ?Item
    {
        $key = $externalId ?: $this->normalizeKey($name);
        
        if (isset($this->itemCache[$key])) {
            return Item::find($this->itemCache[$key]);
        }

        $query = Item::query()->where('branch_id', $branchId);
        if ($externalId) {
            $query->where('sku', $externalId);
        } else {
            $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        }

        $item = $query->first();
        
        if ($item) {
            $this->itemCache[$key] = $item->id;
        }

        return $item;
    }

    protected function getUomIdByToken(string $token): ?int
    {
        if (isset($this->uomCache[$token])) {
            return $this->uomCache[$token];
        }

        $code = $this->uomCodeFromToken($token);
        if (! $code) {
            $code = 'pcs';
        }

        $uomId = UnitOfMeasure::where('code', $code)->value('id');
        $this->uomCache[$token] = $uomId;

        return $uomId;
    }

    protected function getOrCreateDepartment(?string $location, string $branchId): ?int
    {
        if (! $location) {
            return null;
        }

        // Map location names from production data to actual department names
        $locationMap = [
            'HOT KITCHEN' => 'Hot Kitchen Production',
            'PASTRY' => 'Pastry Production',
            'GELATO' => 'Gelato Production',
            'CORNER STORE' => 'Corner Store',
            'TILL' => 'Till Sales',
            'CONCESSION' => 'Concession',
            'CORNERSTORE' => 'Corner Store',
        ];
        
        $locUpper = strtoupper($location);
        $mappedLocation = $locationMap[$locUpper] ?? $location;
        $locKey = $this->normalizeKey($mappedLocation);
        
        if (array_key_exists($locKey, $this->departmentCache)) {
            return $this->departmentCache[$locKey];
        }

        $department = Department::whereRaw('UPPER(name) = ?', [$mappedLocation])->first();
        $this->departmentCache[$locKey] = $department?->id;
        
        return $department?->id;
    }

    protected function generateRecipeSku(string $productName): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 8));
        if ($base === '') {
            $base = 'RECIPE';
        }

        $sku = 'R-' . $base;
        $counter = 1;

        while (Recipe::where('sku', $sku)->exists()) {
            $sku = 'R-' . $base . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }
}
