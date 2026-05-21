<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SyncRecipesFromCatalog extends Command
{
    protected $signature = 'catalog:sync-recipes
        {--dry-run       : Print changes without committing}
        {--generate-json : Parse product_catalog.xlsx → /tmp/catalog_items.json, then exit}';

    protected $description = 'Sync recipes from /tmp/catalog_items.json (or generate JSON with --generate-json)';

    private const BRANCH_ID = '019c850c-7294-72ea-9ec5-1b00d6b0bdd8';

    // Producing Location (uppercase first segment) → department.id
    private const DEPT_MAP = [
        'CORNER STORE' => 7,
        'CORNERSTONE'  => 4,
        'GELATO'       => 3,
        'PASTRY'       => 2,
        'HOT KITCHEN'  => 1,
        'CONCESSION'   => 6,
    ];

    // Catalog UOM symbol (uppercase) → units_of_measure.id
    private const UOM_MAP = [
        'G'       => 1,
        'ML'      => 4,
        'PCS'     => 7,
        'PORTION' => 10,
        'SCP'     => 12,
        'SC'      => 12,
        'RO'      => 27,
        'SPN'     => 28,
        'CP'      => 29,
        'CU'      => 30,
        'PK'      => 32,
    ];

    public function handle(): int
    {
        if ($this->option('generate-json')) {
            return $this->generateJson();
        }

        return $this->syncRecipes();
    }

    // ── JSON generator ────────────────────────────────────────────────────────

    private function generateJson(): int
    {
        $xlsxPath = base_path('product_catalog.xlsx');
        if (! file_exists($xlsxPath)) {
            $this->error("File not found: {$xlsxPath}");
            return 1;
        }

        $ws      = IOFactory::load($xlsxPath)->getActiveSheet();
        $maxRow  = $ws->getHighestRow();
        $entries = [];

        for ($i = 2; $i <= $maxRow; $i++) {
            $name = trim($ws->getCell("A{$i}")->getFormattedValue());
            if (! $name) {
                continue;
            }

            $entries[] = [
                'name'          => $name,
                'department'    => trim($ws->getCell("B{$i}")->getFormattedValue()),
                'selling_point' => trim($ws->getCell("C{$i}")->getFormattedValue()),
                'uom'           => strtoupper(trim($ws->getCell("D{$i}")->getFormattedValue())),
                'ingredients'   => $this->parseIngredients(
                    trim($ws->getCell("E{$i}")->getFormattedValue())
                ),
                'cost_per_unit' => (float) str_replace(',', '', $ws->getCell("K{$i}")->getFormattedValue()),
                'unit_price'    => (float) str_replace(',', '', $ws->getCell("I{$i}")->getFormattedValue()),
            ];
        }

        $jsonPath = '/tmp/catalog_items.json';
        file_put_contents($jsonPath, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Written: {$jsonPath} (" . count($entries) . ' products)');

        return 0;
    }

    private function parseIngredients(string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $summed = [];

        foreach (preg_split('/\s*\|\s*/', $raw) as $part) {
            $part = trim($part);
            if (! $part) {
                continue;
            }

            // Format: "NAME (QTY UOM)"
            if (! preg_match('/^(.+?)\s*\((\d+\.?\d*)\s+([A-Za-z]+)\)\s*$/', $part, $m)) {
                continue;
            }

            $name = rtrim(trim($m[1]), '.,;:');
            $qty  = (float) $m[2];
            $uom  = strtoupper(trim($m[3]));
            $key  = strtoupper($name);

            if (isset($summed[$key])) {
                $summed[$key]['quantity'] += $qty;
            } else {
                $summed[$key] = ['name' => $name, 'quantity' => $qty, 'uom' => $uom];
            }
        }

        return array_values($summed);
    }

    // ── Recipe sync ───────────────────────────────────────────────────────────

    private function syncRecipes(): int
    {
        $jsonPath = '/tmp/catalog_items.json';
        if (! file_exists($jsonPath)) {
            $this->error("File not found: {$jsonPath}. Run with --generate-json first.");
            return 1;
        }

        $catalog = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($catalog)) {
            $this->error('Failed to parse catalog JSON.');
            return 1;
        }

        $isDryRun = $this->option('dry-run');

        // Lookup tables built once
        $itemLookup    = $this->buildItemLookup();
        $productLookup = $this->buildProductLookup();
        $systemUserId  = DB::table('users')->value('id');

        $recipesCreated       = 0;
        $recipesUpdated       = 0;
        $ingredientsCreated   = 0;
        $priceUpdates         = 0;
        $skippedProducts      = [];
        $unresolvedIngredients = [];
        $now = now()->toDateTimeString();

        DB::transaction(function () use (
            $catalog, $itemLookup, $productLookup, $systemUserId,
            $isDryRun, $now,
            &$recipesCreated, &$recipesUpdated, &$ingredientsCreated,
            &$priceUpdates, &$skippedProducts, &$unresolvedIngredients
        ) {
            foreach ($catalog as $entry) {
                $key = strtoupper($entry['name']);

                if (! isset($productLookup[$key])) {
                    $skippedProducts[] = $entry['name'];
                    continue;
                }

                $product    = $productLookup[$key];
                $deptId     = $this->resolveDepartment($entry['department']);
                $uomId      = self::UOM_MAP[$entry['uom']] ?? 7;

                $existing = DB::table('recipes')
                    ->where('product_id', $product->id)
                    ->where('branch_id', self::BRANCH_ID)
                    ->first(['id']);

                $recipeData = [
                    'branch_id'       => self::BRANCH_ID,
                    'product_id'      => $product->id,
                    'product_name'    => $product->name,
                    'sku'             => $product->sku,
                    'product_type_id' => $product->product_type_id,
                    'department_id'   => $deptId,
                    'uom_id'          => $uomId,
                    'cost_per_unit'   => $entry['cost_per_unit'],
                    'yield_quantity'  => 1,
                    'status'          => 'active',
                    'is_wip'          => false,
                    'is_active'       => true,
                    'updated_at'      => $now,
                ];

                if ($existing) {
                    DB::table('recipes')->where('id', $existing->id)->update($recipeData);
                    $recipeId = $existing->id;
                    $recipesUpdated++;
                } else {
                    $recipeData['created_by_id']   = $systemUserId;
                    $recipeData['created_by_type'] = 'App\Models\User';
                    $recipeData['created_at']      = $now;
                    $recipeId = DB::table('recipes')->insertGetId($recipeData);
                    $recipesCreated++;
                }

                // Replace all ingredients for this recipe
                DB::table('recipe_ingredients')->where('recipe_id', $recipeId)->delete();

                foreach ($entry['ingredients'] as $ingr) {
                    $itemId = $this->resolveItem($ingr['name'], $itemLookup);

                    if (! $itemId) {
                        $unresolvedIngredients[$ingr['name']] = true;
                        continue;
                    }

                    $ingrUomId = self::UOM_MAP[$ingr['uom']] ?? 1;

                    DB::table('recipe_ingredients')->insert([
                        'recipe_id'        => $recipeId,
                        'item_id'          => $itemId,
                        'quantity'         => $ingr['quantity'],
                        'uom_id'           => $ingrUomId,
                        'cost_per_unit'    => 0,
                        'waste_percentage' => 0,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                    $ingredientsCreated++;
                }

                if ($entry['unit_price'] > 0 && (float) $product->price == 0) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['price' => $entry['unit_price']]);
                    $priceUpdates++;
                }
            }

            if ($isDryRun) {
                DB::rollBack();
            }
        });

        // ── Summary ───────────────────────────────────────────────────────────
        $mode = $isDryRun ? '[DRY RUN] ' : '';
        $this->info('');
        $this->info("{$mode}Sync complete:");
        $this->info("  Recipes created  : {$recipesCreated}");
        $this->info("  Recipes updated  : {$recipesUpdated}");
        $this->info("  Ingredients      : {$ingredientsCreated}");
        $this->info("  Price updates    : {$priceUpdates}");

        if (! empty($skippedProducts)) {
            $this->warn('');
            $this->warn('Skipped products (no DB match):');
            foreach ($skippedProducts as $name) {
                $this->warn("  - {$name}");
            }
        }

        if (! empty($unresolvedIngredients)) {
            $this->warn('');
            $this->warn('Unresolved ingredients (no item match):');
            foreach (array_keys($unresolvedIngredients) as $name) {
                $this->warn("  - {$name}");
            }
        }

        if ($isDryRun) {
            $this->warn('');
            $this->warn('Dry run — no changes were committed.');
        }

        return 0;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildItemLookup(): array
    {
        return DB::table('items')
            ->get(['id', 'name'])
            ->keyBy(fn ($r) => strtoupper($r->name))
            ->map(fn ($r) => $r->id)
            ->toArray();
    }

    private function buildProductLookup(): array
    {
        return DB::table('products')
            ->get(['id', 'name', 'sku', 'product_type_id', 'price'])
            ->keyBy(fn ($r) => strtoupper($r->name))
            ->toArray();
    }

    private function resolveDepartment(string $location): int
    {
        $first = strtoupper(trim(explode('|', $location)[0]));
        return self::DEPT_MAP[$first] ?? 1;
    }

    // Catalog ingredient name → canonical DB item name
    private const ALIASES = [
        'PINEAPPLE JUICE STORE'        => 'PINEAPPLE JUICE',
        'AVOCADO PEAR'                 => 'AVOCADO',
        'EGG PRODUCTION'               => 'EGG',
        'WILDBERRIES TOPPING (I27)'    => 'WILDBERRIES (E.72)',
        'CHOCOLATE TOPPING (I06) - NEW'=> 'CHOCOLATE TOPPING',
        'SEAMAN SCHNAPPS'              => 'SEAMAN SCHNAPPS',
    ];

    private function resolveItem(string $name, array $lookup): ?int
    {
        // Exact match
        $key = strtoupper($name);
        if (isset($lookup[$key])) {
            return $lookup[$key];
        }

        // Alias map
        $aliased = self::ALIASES[$key] ?? null;
        if ($aliased && isset($lookup[strtoupper($aliased)])) {
            return $lookup[strtoupper($aliased)];
        }

        // Remove trailing punctuation (e.g. "ALMOND MILK." → "ALMOND MILK")
        $cleaned = strtoupper(rtrim(trim($name), '.,;:'));
        if (isset($lookup[$cleaned])) {
            return $lookup[$cleaned];
        }

        return null;
    }
}
