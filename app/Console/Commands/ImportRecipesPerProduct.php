<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Item;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRecipesPerProduct extends Command
{
    protected $signature = 'import:recipes-per-product
        {--branch-id= : Branch UUID}
        {--created-by-id= : User UUID for created_by}
        {--product= : Import specific product (optional)}
        {--dry-run : Parse only}';

    protected $description = 'Import recipes per product from production JSON files';

    protected array $uomCache = [];
    protected array $itemCache = [];
    protected array $departmentCache = [];

    public function handle(): int
    {
        $branchId = $this->option('branch-id') ?: Branch::where('code', 'PHC-002')->value('id');
        $createdById = $this->option('created-by-id') ?: User::where('email', 'admin@sweettooth.local')->value('id');
        $specificProduct = $this->option('product');
        $dryRun = (bool) $this->option('dry-run');

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id.');
            return 1;
        }

        $this->info("Importing recipes per product from JSON files...");
        $this->info("Branch: {$branchId}");
        $this->info("Created by: {$createdById}");

        $jsonFiles = glob(base_path('real_data/receips-sweetooth-main/*.json'));
        sort($jsonFiles);

        $this->info("Found " . count($jsonFiles) . " JSON files.");

        // Collect all production data grouped by product
        $products = [];
        
        foreach ($jsonFiles as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            if (! is_array($data)) {
                continue;
            }

            foreach ($data as $row) {
                $productName = $this->cleanProductName($row['produced_product'] ?? '');
                if (empty($productName)) continue;

                // Filter by specific product if provided
                if ($specificProduct && stripos($productName, $specificProduct) === false) {
                    continue;
                }

                $externalId = $this->extractExternalId($row['produced_product'] ?? '');
                $key = $externalId ?: $this->normalizeKey($productName);

                if (! isset($products[$key])) {
                    $products[$key] = [
                        'name' => $productName,
                        'external_id' => $externalId,
                        'location' => $row['table_info']['location'] ?? '',
                        'recipes' => [],
                        'yields' => [],
                    ];
                }

                // Parse quantity produced
                [$qty, $uom] = $this->parseQuantityAndUom($row['quantity_produced'] ?? '');
                if ($qty > 0) {
                    $products[$key]['yields'][] = $qty;
                }

                // Collect recipe ingredients
                $ingredients = [];
                foreach ($row['ingredients'] ?? [] as $ing) {
                    [$ingName, $ingId] = $this->parseNameAndExternalId($ing['ingredient'] ?? '');
                    [$inputQty, $inputUom] = $this->parseQuantityAndUom($ing['input_qty'] ?? '');
                    $wastePercent = $this->parsePercent($ing['wastage_percent'] ?? '');
                    $totalPrice = $this->parseMoney($ing['total_price'] ?? '');
                    $costPerUnit = $inputQty > 0 ? ($totalPrice / $inputQty) : 0;

                    if ($inputQty > 0 && $qty > 0) {
                        $ratio = $inputQty / $qty;
                        
                        $ingKey = $ingId ?: $this->normalizeKey($ingName);
                        if (! isset($ingredients[$ingKey])) {
                            $ingredients[$ingKey] = [
                                'name' => $ingName,
                                'external_id' => $ingId,
                                'ratios' => [],
                                'uoms' => [],
                                'waste_percents' => [],
                                'costs' => [],
                            ];
                        }
                        $ingredients[$ingKey]['ratios'][] = $ratio;
                        $ingredients[$ingKey]['uoms'][] = $inputUom;
                        $ingredients[$ingKey]['waste_percents'][] = $wastePercent;
                        if ($costPerUnit > 0) {
                            $ingredients[$ingKey]['costs'][] = $costPerUnit;
                        }
                    }
                }

                if (! empty($ingredients)) {
                    $products[$key]['recipes'][] = $ingredients;
                }
            }
        }

        $this->info("Found " . count($products) . " unique products.");

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($products as $key => $productData) {
            try {
                $result = $this->importProductRecipe($productData, $branchId, $createdById, $dryRun);
                if ($result === 'created') $created++;
                elseif ($result === 'updated') $updated++;
                else $skipped++;
            } catch (\Exception $e) {
                $errors++;
                $this->warn("Error importing {$productData['name']}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Import completed:");
        $this->info("  Created: {$created}");
        $this->info("  Updated: {$updated}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Errors: {$errors}");

        return 0;
    }

    protected function importProductRecipe(array $productData, string $branchId, string $createdById, bool $dryRun): string
    {
        // Find or create product
        $product = Product::query()
            ->where(function ($q) use ($productData) {
                if ($productData['external_id']) {
                    $q->where('sku', $productData['external_id']);
                } else {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($productData['name'])]);
                }
            })
            ->first();

        if (! $product) {
            $this->warn("Product not found: {$productData['name']}");
            return 'skipped';
        }

        // Get department
        $departmentId = $this->getDepartmentId($productData['location']);
        if (! $departmentId) {
            $this->warn("No department for location: {$productData['location']}");
            return 'skipped';
        }

        // Calculate average yield
        $avgYield = ! empty($productData['yields']) 
            ? array_sum($productData['yields']) / count($productData['yields']) 
            : 1;

        // Aggregate ingredients from all recipes
        $aggregatedIngredients = [];
        foreach ($productData['recipes'] as $recipe) {
            foreach ($recipe as $ingKey => $ingData) {
                if (! isset($aggregatedIngredients[$ingKey])) {
                    $aggregatedIngredients[$ingKey] = [
                        'ratios' => [],
                        'uoms' => [],
                        'waste_percents' => [],
                        'costs' => [],
                        'name' => $ingData['name'],
                        'external_id' => $ingData['external_id'],
                    ];
                }
                $aggregatedIngredients[$ingKey]['ratios'] = array_merge(
                    $aggregatedIngredients[$ingKey]['ratios'], 
                    $ingData['ratios']
                );
                $aggregatedIngredients[$ingKey]['uoms'] = array_merge(
                    $aggregatedIngredients[$ingKey]['uoms'], 
                    $ingData['uoms']
                );
                $aggregatedIngredients[$ingKey]['waste_percents'] = array_merge(
                    $aggregatedIngredients[$ingKey]['waste_percents'], 
                    $ingData['waste_percents']
                );
                $aggregatedIngredients[$ingKey]['costs'] = array_merge(
                    $aggregatedIngredients[$ingKey]['costs'], 
                    $ingData['costs']
                );
            }
        }

        // Find or create recipe
        $recipe = Recipe::where('product_id', $product->id)->first();
        
        if (! $dryRun) {
            if ($recipe) {
                $recipe->update([
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'yield_quantity' => round($avgYield, 2),
                    'status' => 'active',
                ]);
            } else {
                $recipe = Recipe::create([
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => 'R-' . ($product->sku ?? 'UNKNOWN'),
                    'product_type_id' => $product->product_type_id,
                    'uom_id' => $product->uom_id,
                    'yield_quantity' => round($avgYield, 2),
                    'status' => 'active',
                    'created_by_id' => $createdById,
                    'created_by_type' => User::class,
                ]);
            }

            // Delete existing ingredients and recreate
            RecipeIngredient::where('recipe_id', $recipe->id)->delete();

            // Add ingredients
            foreach ($aggregatedIngredients as $ingData) {
                $item = $this->findItem($ingData['name'], $ingData['external_id'], $branchId);
                if (! $item) {
                    continue;
                }

                $avgRatio = ! empty($ingData['ratios']) ? array_sum($ingData['ratios']) / count($ingData['ratios']) : 0;
                $avgWaste = ! empty($ingData['waste_percents']) ? array_sum($ingData['waste_percents']) / count($ingData['waste_percents']) : 0;
                $avgCost = ! empty($ingData['costs']) ? array_sum($ingData['costs']) / count($ingData['costs']) : ($item->unit_price ?? 0);

                // Get most common UOM
                $uomId = $this->getMostCommonUomId($ingData['uoms']) ?: $item->uom_id;

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'item_id' => $item->id,
                    'quantity' => round($avgRatio, 6),
                    'uom_id' => $uomId,
                    'cost_per_unit' => round($avgCost, 4),
                    'waste_percentage' => min(100, max(0, round($avgWaste, 2))),
                    'sort_order' => 0,
                ]);
            }
        }

        return $recipe->wasRecentlyCreated ? 'created' : 'updated';
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

    protected function getDepartmentId(string $location): ?int
    {
        if (empty($location)) return null;

        $locationMap = [
            'HOT KITCHEN' => 'Hot Kitchen Production',
            'PASTRY' => 'Pastry Production',
            'GELATO' => 'Gelato Production',
            'CORNER STORE' => 'Corner Store',
            'TILL' => 'Till Sales',
            'CONCESSION' => 'Concession',
        ];

        $locUpper = strtoupper($location);
        $mappedLocation = $locationMap[$locUpper] ?? $location;
        $locKey = $this->normalizeKey($mappedLocation);

        if (isset($this->departmentCache[$locKey])) {
            return $this->departmentCache[$locKey];
        }

        $department = Department::whereRaw('UPPER(name) = ?', [$mappedLocation])->first();
        $this->departmentCache[$locKey] = $department?->id;

        return $department?->id;
    }

    protected function getMostCommonUomId(array $uoms): ?int
    {
        if (empty($uoms)) return null;

        $counts = array_count_values($uoms);
        arsort($counts);
        $mostCommon = array_key_first($counts);

        return $this->getUomIdByToken($mostCommon);
    }

    protected function getUomIdByToken(?string $token): ?int
    {
        if (! $token) return null;

        if (isset($this->uomCache[$token])) {
            return $this->uomCache[$token];
        }

        $code = $this->uomCodeFromToken($token);
        if (! $code) $code = 'pcs';

        $uomId = UnitOfMeasure::where('code', $code)->value('id');
        $this->uomCache[$token] = $uomId;

        return $uomId;
    }

    protected function uomCodeFromToken(?string $token): ?string
    {
        if (! $token) return null;

        $token = strtoupper(trim($token));

        $map = [
            'G' => 'g', 'KG' => 'kg', 'MG' => 'mg',
            'ML' => 'ml', 'L' => 'l', 'CL' => 'cl',
            'PCS' => 'pcs', 'PC' => 'pcs', 'UNIT' => 'pcs',
            'PORTION' => 'pcs', 'PK' => 'pcs', 'CU' => 'pcs', 'CP' => 'pcs',
            'RO' => 'pcs', 'SPN' => 'pcs',
        ];

        return $map[$token] ?? null;
    }

    protected function cleanProductName(?string $value): string
    {
        $value = trim((string) $value);
        return preg_replace('/\s*\(\d+\)\s*$/', '', $value);
    }

    protected function extractExternalId(?string $value): ?string
    {
        if (preg_match('/\((\d+)\)\s*$/', trim((string) $value), $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function normalizeKey(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return strtoupper($value);
    }

    protected function parseNameAndExternalId(?string $value): array
    {
        $value = trim((string) $value);
        $externalId = null;

        if (preg_match('/\((\d+)\)\s*$/', $value, $matches)) {
            $externalId = $matches[1];
            $value = trim(preg_replace('/\s*\(\d+\)\s*$/', '', $value));
        }

        return [$value, $externalId];
    }

    protected function parseQuantityAndUom(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') return [0.0, null];

        if (preg_match('/([0-9.,]+)\s*([A-Za-z%]+)$/', $value, $matches)) {
            $quantity = str_replace(',', '', $matches[1]);
            $uom = strtoupper($matches[2]);
            return [is_numeric($quantity) ? (float) $quantity : 0.0, $uom];
        }

        return [0.0, null];
    }

    protected function parsePercent(?string $value): float
    {
        $value = trim((string) $value);
        if ($value === '') return 0.0;

        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    protected function parseMoney(?string $value): float
    {
        $value = trim((string) $value);
        if ($value === '') return 0.0;

        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }
}
