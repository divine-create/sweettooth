<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Item;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionJsonImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataDir = base_path('real_data/receips-sweetooth-main');
        $jsonFiles = $this->discoverJsonFiles($dataDir);

        $this->command->info('📦 Importing production data from JSON files...');

        // First, create UOMs from the JSON data
        $uomMap = $this->createUomsFromJson($jsonFiles);
        $this->command->info('✅ Created/found '.count($uomMap).' UOMs from JSON data.');

        // Get branch and departments
        $branch = Branch::where('code', 'PHC-002')->first();
        if (!$branch) {
            $this->command->error('❌ Branch not found. Please run PortHarcourtRealDataSeeder first.');
            return;
        }

        $departments = $this->getDepartments($branch);
        $this->command->info('✅ Found '.count($departments).' departments.');

        // Get or create users
        $users = User::where('branch_id', $branch->id)->get()->keyBy('id');
        $creator = $users->first();
        if (!$creator) {
            $this->command->error('❌ No users found for branch. Please run PortHarcourtRealDataSeeder first.');
            return;
        }

        // Parse all JSON data
        $this->command->info('📄 Parsing JSON files...');
        $productions = $this->parseJsonFiles($jsonFiles);
        $this->command->info('✅ Found '.count($productions).' production records.');

        // Create items from ingredients
        $this->command->info('📦 Creating items from ingredients...');
        $items = $this->createItems($productions, $branch, $uomMap, $creator);
        $this->command->info('✅ Created/found '.count($items).' items.');

        // Create products and recipes
        $this->command->info('🍳 Creating products and recipes...');
        $recipes = $this->createRecipes($productions, $branch, $departments, $items, $uomMap, $creator);
        $this->command->info('✅ Created/found '.count($recipes).' recipes.');

        // Create production records
        $this->command->info('📝 Creating production records...');
        $this->createProductionRecords($productions, $branch, $departments, $recipes, $items, $users, $creator, $uomMap);
        $this->command->info('✅ Production records created successfully!');

        $this->command->info('🎉 JSON import completed!');
    }

    /**
     * Discover JSON files in the data directory
     *
     * @return array<int, string>
     */
    private function discoverJsonFiles(string $dataDir): array
    {
        return array_values(array_unique(
            glob($dataDir.'/*.json') ?: []
        ));
    }

    /**
     * Create UOMs from JSON data
     *
     * @param  array<int, string>  $jsonFiles
     * @return array<string, int> Map of UOM code to ID
     */
    private function createUomsFromJson(array $jsonFiles): array
    {
        $uoms = [];

        foreach ($jsonFiles as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $entry) {
                // Extract produced product UOM
                $qty = $entry['quantity_produced'] ?? '';
                $this->extractUomFromQuantity($qty, $uoms);

                // Extract ingredient UOMs
                foreach ($entry['ingredients'] ?? [] as $ing) {
                    $iqty = $ing['input_qty'] ?? '';
                    $this->extractUomFromQuantity($iqty, $uoms);
                }
            }
        }

        // Create UOMs that don't exist
        $uomMap = [];
        foreach ($uoms as $code => $info) {
            $uom = UnitOfMeasure::where('code', strtolower($code))->first();
            if (!$uom) {
                $uom = UnitOfMeasure::create([
                    'code' => strtolower($code),
                    'name' => $info['name'] ?? ucfirst(strtolower($code)),
                    'symbol' => $code,
                    'category' => $this->guessUomCategory($code),
                    'description' => $info['name'] ?? ucfirst(strtolower($code)),
                    'sort_order' => 100,
                    'is_active' => true,
                ]);
                $this->command->info("  ✓ Created UOM: {$code} ({$uom->name})");
            }
            $uomMap[strtoupper($code)] = $uom->id;
        }

        return $uomMap;
    }

    /**
     * Extract UOM from quantity string
     */
    private function extractUomFromQuantity(string $quantity, array &$uoms): void
    {
        $quantity = trim($quantity);
        if (preg_match('/([0-9.,\s]+)\s*([A-Z]+)$/', $quantity, $matches)) {
            $code = strtoupper($matches[2]);
            if (!isset($uoms[$code])) {
                $uoms[$code] = [
                    'name' => $this->getUomName($code),
                ];
            }
        }
    }

    /**
     * Get human-readable name for UOM code
     */
    private function getUomName(string $code): string
    {
        $names = [
            'PCS' => 'Pieces',
            'G' => 'Grams',
            'KG' => 'Kilograms',
            'ML' => 'Milliliters',
            'L' => 'Liters',
            'CU' => 'Cups',
            'RO' => 'Rounds',
            'PORTION' => 'Portions',
            'PK' => 'Packs',
            'CP' => 'Cups',
            'SC' => 'Scoops',
            'CL' => 'Centiliters',
            'SPN' => 'Spoons',
            'UNIT' => 'Units',
            'DZ' => 'Dozens',
        ];

        return $names[$code] ?? ucfirst(strtolower($code));
    }

    /**
     * Guess UOM category from code
     */
    private function guessUomCategory(string $code): string
    {
        $weight = ['G', 'KG', 'MG', 'OZ', 'LB'];
        $volume = ['ML', 'L', 'CL', 'FL_OZ', 'CUP', 'TBSP', 'TSP'];
        $count = ['PCS', 'UNIT', 'DZ', 'PK', 'CU', 'RO', 'PORTION', 'CP', 'SC', 'SPN'];

        if (in_array(strtoupper($code), $weight)) {
            return 'weight';
        }
        if (in_array(strtoupper($code), $volume)) {
            return 'volume';
        }
        return 'count';
    }

    /**
     * Get departments keyed by their key
     *
     * @return array<string, Department>
     */
    private function getDepartments(Branch $branch): array
    {
        $departments = [];
        foreach (Department::where('branch_id', $branch->id)->get() as $dept) {
            $key = strtolower(str_replace(' ', '_', $dept->name));
            $departments[$key] = $dept;
        }
        return $departments;
    }

    /**
     * Parse all JSON files
     *
     * @param  array<int, string>  $jsonFiles
     * @return array<int, array<string, mixed>>
     */
    private function parseJsonFiles(array $jsonFiles): array
    {
        $productions = [];

        foreach ($jsonFiles as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $entry) {
                $productions[] = $entry;
            }
        }

        return $productions;
    }

    /**
     * Create items from ingredients
     *
     * @param  array<int, array<string, mixed>>  $productions
     * @param  array<string, int>  $uomMap
     * @return array<string, Item>
     */
    private function createItems(array $productions, Branch $branch, array $uomMap, User $creator): array
    {
        $items = [];

        foreach ($productions as $entry) {
            foreach ($entry['ingredients'] ?? [] as $ing) {
                $ingredientName = $this->cleanName($ing['ingredient'] ?? '');
                if ($ingredientName === '') {
                    continue;
                }

                $itemKey = $this->normalizeNameKey($ingredientName);
                if (isset($items[$itemKey])) {
                    continue;
                }

                // Get UOM from ingredient
                $uomCode = $this->extractUomCode($ing['input_qty'] ?? '');
                $uomId = $uomMap[strtoupper($uomCode)] ?? null;

                $item = Item::where('branch_id', $branch->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($ingredientName)])
                    ->first();

                if (!$item) {
                    $item = Item::create([
                        'branch_id' => $branch->id,
                        'name' => $ingredientName,
                        'sku' => $this->generateSku($ingredientName),
                        'category' => 'raw_material',
                        'uom_id' => $uomId,
                        'reorder_level' => 100,
                        'max_stock_level' => 1000,
                        'unit_price' => 0,
                        'status' => 'active',
                        'requires_request' => true,
                    ]);
                }

                $items[$itemKey] = $item;
            }
        }

        return $items;
    }

    /**
     * Create recipes from production data
     *
     * @param  array<int, array<string, mixed>>  $productions
     * @param  array<string, Department>  $departments
     * @param  array<string, Item>  $items
     * @param  array<string, int>  $uomMap
     * @return array<string, Recipe>
     */
    private function createRecipes(
        array $productions,
        Branch $branch,
        array $departments,
        array $items,
        array $uomMap,
        User $creator
    ): array {
        $recipes = [];

        foreach ($productions as $entry) {
            $productName = $this->cleanName($entry['produced_product'] ?? '');
            if ($productName === '') {
                continue;
            }

            $recipeKey = $this->normalizeNameKey($productName);
            if (isset($recipes[$recipeKey])) {
                continue;
            }

            // Determine department from location
            $location = $entry['table_info']['location'] ?? '';
            $department = $this->getDepartmentForLocation($location, $departments);

            // Get UOM from quantity produced
            $uomCode = $this->extractUomCode($entry['quantity_produced'] ?? '');
            $uomId = $uomMap[strtoupper($uomCode)] ?? null;

            // Parse yield quantity
            [$yieldQty] = $this->extractQuantityAndUom($entry['quantity_produced'] ?? '');
            $yieldQty = $yieldQty ?? 1;

            // Get total cost
            $totalCost = $this->toDecimal($entry['total_cost'] ?? '0');
            
            // Cap cost values to reasonable range (avoid out-of-range errors)
            if ($totalCost !== null && $totalCost > 1000000) {
                $totalCost = $totalCost / 100; // Scale down unrealistic values
            }

            $recipe = Recipe::where('branch_id', $branch->id)
                ->where('product_name', $productName)
                ->first();

            if (!$recipe) {
                $costPerUnit = $totalCost > 0 && $yieldQty > 0 ? $totalCost / $yieldQty : 0;
                // Cap cost_per_unit to avoid out-of-range errors
                if ($costPerUnit > 100000) {
                    $costPerUnit = $costPerUnit / 100;
                }
                
                $recipe = Recipe::create([
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'product_name' => $productName,
                    'sku' => $this->generateSku($productName),
                    'cost_per_unit' => $costPerUnit,
                    'uom_id' => $uomId,
                    'yield_quantity' => $yieldQty,
                    'status' => 'active',
                    'created_by_id' => $creator->id,
                    'created_by_type' => User::class,
                ]);
            }

            $recipes[$recipeKey] = $recipe;
        }

        return $recipes;
    }

    /**
     * Create production records
     *
     * @param  array<int, array<string, mixed>>  $productions
     * @param  array<string, Department>  $departments
     * @param  array<string, Recipe>  $recipes
     * @param  array<string, Item>  $items
     * @param  array<string, int>  $uomMap
     */
    private function createProductionRecords(
        array $productions,
        Branch $branch,
        array $departments,
        array $recipes,
        array $items,
        $users,
        User $creator,
        array $uomMap
    ): void {
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($productions, $branch, $departments, $recipes, $items, $users, $creator, $uomMap, &$created, &$skipped) {
            foreach ($productions as $entry) {
                $productName = $this->cleanName($entry['produced_product'] ?? '');
                if ($productName === '') {
                    continue;
                }

                $recipeKey = $this->normalizeNameKey($productName);
                $recipe = $recipes[$recipeKey] ?? null;

                if (!$recipe) {
                    $skipped++;
                    continue;
                }

                // Parse date and time
                $dateTime = $this->parseDateTime($entry);
                if (!$dateTime) {
                    $skipped++;
                    continue;
                }

                // Get department
                $location = $entry['table_info']['location'] ?? '';
                $department = $this->getDepartmentForLocation($location, $departments);

                // Parse quantities
                [$producedQty] = $this->extractQuantityAndUom($entry['quantity_produced'] ?? '');
                [$wastedQty] = $this->extractQuantityAndUom($entry['wasted_quantity'] ?? '');
                $producedQty = $producedQty ?? 0;
                $wastedQty = $wastedQty ?? 0;

                // Get reference number
                $referenceNo = trim($entry['reference_no'] ?? '');
                if ($referenceNo === '') {
                    $referenceNo = 'PROD-'.Str::upper(Str::random(6));
                }

                // Create or get DailyProduce record
                $dailyProduce = $this->getOrCreateDailyProduce(
                    $recipe,
                    $department,
                    $creator,
                    $dateTime,
                    $producedQty
                );

                // Create production record
                $productionRecord = ProductionRecord::create([
                    'daily_produce_id' => $dailyProduce->id,
                    'recipe_id' => $recipe->id,
                    'batch_number' => $referenceNo,
                    'produced_by_id' => $creator->id,
                    'produced_by_type' => User::class,
                    'quantity_produced' => $producedQty,
                    'quantity_approved' => $producedQty - $wastedQty,
                    'quantity_rejected' => $wastedQty,
                    'production_time' => $dateTime,
                    'notes' => 'Imported from JSON - Location: '.$location,
                ]);

                // Create recipe ingredients if not exists
                $this->createRecipeIngredients($recipe, $entry['ingredients'] ?? [], $items, $uomMap, $creator);

                $created++;
            }
        });

        $this->command->info("  ✓ Created {$created} production records, skipped {$skipped}");
    }

    /**
     * Get or create DailyProduce record
     */
    private function getOrCreateDailyProduce(
        Recipe $recipe,
        Department $department,
        User $creator,
        Carbon $dateTime,
        float $producedQty
    ): \App\Models\DailyProduce {
        // Try to find existing DailyProduce for this recipe and date
        $dailyProduce = \App\Models\DailyProduce::where('recipe_id', $recipe->id)
            ->where('produce_date', $dateTime->toDateString())
            ->first();

        if (!$dailyProduce) {
            // Get or create shift
            $shift = $this->getOrCreateShift($department, $creator, $dateTime, $this->getShiftType($dateTime));

            $dailyProduce = \App\Models\DailyProduce::create([
                'shift_id' => $shift->id,
                'recipe_id' => $recipe->id,
                'produce_date' => $dateTime->toDateString(),
                'produced_quantity' => $producedQty,
                'status' => 'completed',
                'notes' => 'Imported from JSON production data',
            ]);
        } else {
            // Update produced quantity
            $dailyProduce->produced_quantity = ($dailyProduce->produced_quantity ?? 0) + $producedQty;
            $dailyProduce->save();
        }

        return $dailyProduce;
    }

    /**
     * Get or create shift
     */
    private function getOrCreateShift(
        Department $department,
        User $creator,
        Carbon $dateTime,
        string $shiftType
    ): \App\Models\Shift {
        // Try to find existing shift
        $shift = \App\Models\Shift::where('department_id', $department->id)
            ->where('shift_date', $dateTime->toDateString())
            ->where('shift_type', $shiftType)
            ->first();

        if (!$shift) {
            $shiftNumber = 'SHIFT-'.$department->id.'-'.strtoupper($shiftType).'-'.$dateTime->format('Ymd');
            $shift = \App\Models\Shift::create([
                'branch_id' => $department->branch_id,
                'department_id' => $department->id,
                'employee_id' => $creator->id,
                'shift_number' => $shiftNumber,
                'shift_date' => $dateTime->toDateString(),
                'shift_type' => $shiftType,
                'clock_in' => $dateTime,
                'clock_out' => $dateTime->copy()->addHours(8),
            ]);
        }

        return $shift;
    }

    /**
     * Get shift type from datetime
     */
    private function getShiftType(Carbon $dateTime): string
    {
        $hour = $dateTime->hour;
        if ($hour >= 6 && $hour < 14) {
            return 'morning';
        }
        if ($hour >= 14 && $hour < 22) {
            return 'afternoon';
        }
        return 'night';
    }

    /**
     * Create recipe ingredients
     *
     * @param  array<int, array<string, mixed>>  $ingredients
     * @param  array<string, Item>  $items
     * @param  array<string, int>  $uomMap
     */
    private function createRecipeIngredients(
        Recipe $recipe,
        array $ingredients,
        array $items,
        array $uomMap,
        User $creator
    ): void {
        // Check if ingredients already exist
        if ($recipe->ingredients()->count() > 0) {
            return;
        }

        foreach ($ingredients as $ing) {
            $ingredientName = $this->cleanName($ing['ingredient'] ?? '');
            if ($ingredientName === '') {
                continue;
            }

            $itemKey = $this->normalizeNameKey($ingredientName);
            $item = $items[$itemKey] ?? null;

            if (!$item) {
                continue;
            }

            [$qty] = $this->extractQuantityAndUom($ing['input_qty'] ?? '');
            $qty = $qty ?? 0;

            $wastage = $this->toPercentage($ing['wastage_percent'] ?? '0') ?? 0;
            
            // Cap waste_percentage to valid range (0-100)
            if ($wastage > 100) {
                $wastage = 0;
            }

            // Get UOM
            $uomCode = $this->extractUomCode($ing['input_qty'] ?? '');
            $uomId = $uomMap[strtoupper($uomCode)] ?? $item->uom_id;

            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'item_id' => $item->id,
                'quantity' => $qty,
                'uom_id' => $uomId,
                'waste_percentage' => $wastage,
                'sort_order' => 1,
            ]);
        }
    }

    /**
     * Get department for location
     *
     * @param  array<string, Department>  $departments
     */
    private function getDepartmentForLocation(string $location, array $departments): Department
    {
        $locationKey = strtolower(str_replace(' ', '_', $location));

        // Map location to department key
        $mapping = [
            'hot_kitchen' => 'hot_kitchen',
            'till' => 'till_concession',
            'till_concession' => 'till_concession',
            'concession' => 'concession',
            'corner_store' => 'corner_store',
            'cornerstore' => 'corner_store',
            'pastry' => 'pastry',
            'gelato' => 'gelato',
        ];

        $deptKey = $mapping[$locationKey] ?? null;
        if ($deptKey && isset($departments[$deptKey])) {
            return $departments[$deptKey];
        }

        // Try to find by name
        foreach ($departments as $key => $dept) {
            if (stripos($location, $dept->name) !== false) {
                return $dept;
            }
        }

        // Default to hot kitchen
        return $departments['hot_kitchen'] ?? reset($departments);
    }

    /**
     * Clean name
     */
    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * Normalize name key
     */
    private function normalizeNameKey(string $name): string
    {
        return strtolower(str_replace(' ', '_', $this->cleanName($name)));
    }

    /**
     * Extract UOM code from quantity string
     */
    private function extractUomCode(string $quantity): string
    {
        $quantity = trim($quantity);
        if (preg_match('/([0-9.,\s]+)\s*([A-Z]+)$/', $quantity, $matches)) {
            return strtoupper($matches[2]);
        }
        return 'PCS';
    }

    /**
     * Extract quantity and UOM from string
     * @return array{0: float|null, 1: string|null}
     */
    private function extractQuantityAndUom(string $quantity): array
    {
        $quantity = trim($quantity);
        if (preg_match('/([0-9.,\s]+)\s*([A-Z]+)$/', $quantity, $matches)) {
            $qty = (float) str_replace(',', '', $matches[1]);
            $uom = strtoupper($matches[2]);
            return [$qty, $uom];
        }
        return [null, null];
    }

    /**
     * Convert to decimal
     */
    private function toDecimal(string $value): ?float
    {
        $value = trim(str_replace(',', '', $value));
        if ($value === '' || $value === '0') {
            return null;
        }
        return (float) $value;
    }

    /**
     * Convert to percentage
     */
    private function toPercentage(string $value): ?float
    {
        $value = trim(str_replace(['%', ','], '', $value));
        if ($value === '') {
            return null;
        }
        return (float) $value;
    }

    /**
     * Parse date time from entry
     */
    private function parseDateTime(array $entry): ?Carbon
    {
        $dateTimeStr = $entry['table_info']['date_time'] ?? $entry['production_date'] ?? null;
        if (!$dateTimeStr) {
            return null;
        }

        try {
            return Carbon::createFromFormat('m/d/Y h:i A', $dateTimeStr);
        } catch (\Exception $e) {
            try {
                return Carbon::createFromFormat('m/d/Y', $dateTimeStr);
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    /**
     * Generate SKU from name
     */
    private function generateSku(string $name): string
    {
        $sku = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($name, 0, 10)));
        return $sku . '-' . Str::random(4);
    }
}
