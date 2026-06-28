<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ReadsRichineiExport;
use App\Models\Item;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRichineiRecipes extends Command
{
    use ReadsRichineiExport;

    protected $signature = 'import:richinei-recipes
        {--branch-id= : Branch UUID (defaults to first branch)}
        {--created-by-id= : User UUID to attribute imported recipes to (defaults to a Super Admin)}
        {--only-complete : Only create a recipe when EVERY ingredient already resolves to a live item/product; skip any recipe that would have missing ingredient lines}
        {--dry-run : Report only; write nothing}';

    protected $description = 'Import recipes (+ ingredients) from the Richinei export. Fill-only: creates missing recipes, leaves existing ones untouched.';

    /** source product_id => clean name, for matching live products by name. */
    protected array $nameBySku = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyComplete = (bool) $this->option('only-complete');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        $recipes = $this->loadExport('recipes');
        if (empty($recipes)) {
            $this->warn('No recipes to import.');

            return self::SUCCESS;
        }

        $creator = $this->option('created-by-id')
            ?: User::where('user_type', 'admin')->value('id')
            ?: User::query()->value('id');
        if (! $creator) {
            $this->error('No user found to attribute recipes to. Pass --created-by-id.');

            return self::FAILURE;
        }

        // variation_id => source product_id  (links recipe output & ingredients to skus)
        $varToProduct = [];
        foreach ($this->loadExport('variations') as $v) {
            $varToProduct[(string) ($v['variation_id'] ?? '')] = (string) ($v['product_id'] ?? '');
        }

        // source product_id => clean name (so ingredients/outputs can match live
        // products by NAME when live stored them under a different SKU).
        $nameBySku = [];
        foreach ($this->loadExport('products') as $p) {
            $nameBySku[(string) ($p['id'] ?? '')] = $this->cleanName($p['product'] ?? null);
        }
        $this->nameBySku = $nameBySku;

        $this->info(count($recipes).' source recipes.'.($dryRun ? '  [DRY RUN]' : ''));

        $created = $skippedExisting = $skippedNoProduct = $noDept = $failed = 0;
        $skippedIncomplete = 0;
        $ingLinked = $ingMissing = 0;

        $bar = $this->output->createProgressBar(count($recipes));
        $bar->start();

        foreach ($recipes as $r) {
            $bar->advance();

            $name = $this->cleanName($r['recipe_name'] ?? null);
            $outProductSku = $varToProduct[(string) ($r['variation_id'] ?? '')] ?? null;
            if (! $outProductSku) {
                $skippedNoProduct++;

                continue;
            }

            // The finished product must already exist (run products importer first).
            // Match by SKU, then by NAME (live may hold it under a different SKU).
            $outName = $this->nameBySku[$outProductSku] ?? $name;
            $product = Product::where('branch_id', $branchId)->where('sku', $outProductSku)->first()
                ?? Product::whereNull('branch_id')->where('sku', $outProductSku)->first()
                ?? Product::where('branch_id', $branchId)->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($outName)])->first()
                ?? Product::whereNull('branch_id')->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($outName)])->first();
            if (! $product) {
                $skippedNoProduct++;

                continue;
            }

            // Fill-only: skip recipes that already exist for this product.
            $exists = Recipe::where('branch_id', $branchId)
                ->where(fn ($q) => $q->where('product_id', $product->id)->orWhere('sku', $outProductSku))
                ->exists();
            if ($exists) {
                $skippedExisting++;

                continue;
            }

            // --only-complete: never create a recipe that would be missing ingredient
            // lines. Skip the whole recipe unless every ingredient resolves in live.
            if ($onlyComplete && ! $this->allIngredientsResolvable($r['ingredients'] ?? [], $branchId, $varToProduct)) {
                $skippedIncomplete++;

                continue;
            }

            // Department: recipe category -> map; fall back to the product's department.
            $class = $this->classify((string) ($r['category'] ?? ''), $name, false);
            $deptId = $this->resolveDepartmentByName($class['dept'], $branchId)
                ?? $product->department_id
                ?? $product->sales_department_id;
            if (! $deptId) {
                $noDept++;

                continue;
            }

            $yield = $this->firstNumber($r['total_quantity'] ?? '') ?: 1.0;
            $uomId = $this->richineiUomId($r['unit_name'] ?? null);
            $isWip = $class['kind'] === 'wip';

            if (! $dryRun) {
                try {
                    DB::transaction(function () use ($r, $product, $branchId, $deptId, $creator, $yield, $uomId, $isWip, $outProductSku, $varToProduct, &$ingLinked, &$ingMissing) {
                        $recipe = Recipe::create([
                            'branch_id' => $branchId,
                            'department_id' => $deptId,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'sku' => $outProductSku,
                            'product_type_id' => $product->product_type_id,
                            'cost_per_unit' => $this->firstNumber($r['unit_cost'] ?? null),
                            'uom_id' => $uomId,
                            'yield_quantity' => $yield,
                            'status' => 'active',
                            'is_wip' => $isWip,
                            'is_active' => true,
                            'created_by_id' => $creator,
                            'created_by_type' => User::class,
                        ]);

                        [$l, $m] = $this->importIngredients($recipe, $r['ingredients'] ?? [], $branchId, $varToProduct);
                        $ingLinked += $l;
                        $ingMissing += $m;
                    });
                } catch (\Throwable $e) {
                    // One malformed source recipe must not abort the whole import.
                    $this->newLine();
                    $this->warn("Skipped recipe '{$name}' (sku {$outProductSku}): ".$e->getMessage());
                    $failed++;

                    continue;
                }
            } else {
                // dry-run: count resolvable ingredients for reporting
                [$l, $m] = $this->countIngredients($r['ingredients'] ?? [], $branchId, $varToProduct);
                $ingLinked += $l;
                $ingMissing += $m;
            }

            $created++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Recipes — created: {$created}, already existed (untouched): {$skippedExisting}, errored (skipped): {$failed}");
        $this->line("Skipped — finished product not found: {$skippedNoProduct}, no department: {$noDept}".($onlyComplete ? ", incomplete ingredients (only-complete): {$skippedIncomplete}" : ''));
        $this->line("Ingredients — linked: {$ingLinked}, unresolved (deleted source items): {$ingMissing}");

        if ($dryRun) {
            $this->warn('DRY RUN: nothing was written.');
        }

        return self::SUCCESS;
    }

    protected function importIngredients(Recipe $recipe, array $ingredients, string $branchId, array $varToProduct): array
    {
        $missing = 0;
        $agg = []; // ref-key => aggregated row (dedup within a recipe, summing qty)

        foreach ($ingredients as $ing) {
            $resolved = $this->resolveIngredient($ing, $branchId, $varToProduct);
            if (! $resolved) {
                $missing++;

                continue;
            }
            [$key, $ref] = [$resolved['key'], $resolved['ref']];

            $qty = (float) ($ing['quantity'] ?? 0);
            $waste = min(max(round((float) ($ing['waste_percent'] ?? 0), 2), 0), 100);

            // Same component referenced twice in one source recipe (often because two
            // source ids name-deduped to one item) -> merge into a single row.
            if (isset($agg[$key])) {
                $agg[$key]['quantity'] += $qty;
                $agg[$key]['waste_percentage'] = max($agg[$key]['waste_percentage'], $waste);
            } else {
                $agg[$key] = $ref + ['quantity' => $qty, 'waste_percentage' => $waste];
            }
        }

        $sort = 0;
        foreach ($agg as $row) {
            RecipeIngredient::create($row + [
                'recipe_id' => $recipe->id,
                'uom_id' => $recipe->uom_id,
                'sort_order' => $sort++,
            ]);
        }

        return [count($agg), $missing];
    }

    /**
     * Resolve one source ingredient to a live Item or Product, mirroring the import
     * matching: Item by SKU then NAME, else Product by SKU then NAME. Returns a
     * ['key'=>..,'ref'=>..] pair, or null when nothing in live matches.
     */
    protected function resolveIngredient(array $ing, string $branchId, array $varToProduct): ?array
    {
        $sku = $varToProduct[(string) ($ing['variation_id'] ?? '')] ?? null;
        if (! $sku) {
            return null;
        }

        $name = $this->nameBySku[$sku] ?? null;
        $item = Item::where('branch_id', $branchId)->where('sku', $sku)->first()
            ?? Item::where('sku', $sku)->first()
            ?? ($name ? Item::where('branch_id', $branchId)->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first() : null)
            ?? ($name ? Item::whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first() : null);

        if ($item) {
            return ['key' => 'I:'.$item->id, 'ref' => ['item_id' => (string) $item->id]];
        }

        $product = Product::where('branch_id', $branchId)->where('sku', $sku)->first()
            ?? Product::whereNull('branch_id')->where('sku', $sku)->first()
            ?? ($name ? Product::where('branch_id', $branchId)->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first() : null)
            ?? ($name ? Product::whereNull('branch_id')->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first() : null);

        if ($product) {
            return ['key' => 'P:'.$product->id, 'ref' => ['component_type' => Product::class, 'component_id' => (string) $product->id]];
        }

        return null;
    }

    /** True only when every ingredient of the recipe resolves to a live item/product. */
    protected function allIngredientsResolvable(array $ingredients, string $branchId, array $varToProduct): bool
    {
        foreach ($ingredients as $ing) {
            if ($this->resolveIngredient($ing, $branchId, $varToProduct) === null) {
                return false;
            }
        }

        return true;
    }

    protected function countIngredients(array $ingredients, string $branchId, array $varToProduct): array
    {
        $linked = $missing = 0;
        foreach ($ingredients as $ing) {
            $sku = $varToProduct[(string) ($ing['variation_id'] ?? '')] ?? null;
            $found = $sku && (Item::where('sku', $sku)->exists() || Product::where('sku', $sku)->exists());
            $found ? $linked++ : $missing++;
        }

        return [$linked, $missing];
    }

    /** Extract the first numeric value from a string that may contain HTML/units. */
    protected function firstNumber(?string $value): float
    {
        $value = strip_tags((string) $value);
        if (preg_match('/([0-9][0-9,]*\.?[0-9]*)/', $value, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }

        return 0.0;
    }
}
