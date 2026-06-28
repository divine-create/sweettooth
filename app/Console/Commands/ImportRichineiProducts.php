<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ReadsRichineiExport;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Console\Command;

class ImportRichineiProducts extends Command
{
    use ReadsRichineiExport;

    protected $signature = 'import:richinei-products
        {--branch-id= : Branch UUID (defaults to first branch)}
        {--wip-department= : Canonical department name to hold WIP intermediates whose department cannot be derived (e.g. "HOT KITCHEN")}
        {--dry-run : Report only; write nothing}';

    protected $description = 'Import sellable products from the Richinei export, filed by the reviewed category→department map';

    protected array $productTypeCache = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        $products = $this->loadExport('products');
        if (empty($products)) {
            $this->warn('No products to import.');

            return self::SUCCESS;
        }
        if (! $this->loadCategoryMap()) {
            $this->error('category_department_map.csv not found/empty. Generate & review it first.');

            return self::FAILURE;
        }

        // source product_id => [selling_price, unit token]; and variation_id => product_id
        $vmap = [];
        $varToProduct = [];
        foreach ($this->loadExport('variations') as $v) {
            $vmap[(string) ($v['product_id'] ?? '')] = [
                'price' => (float) ($v['selling_price'] ?? 0),
                'unit' => $v['unit'] ?? null,
            ];
            $varToProduct[(string) ($v['variation_id'] ?? '')] = (string) ($v['product_id'] ?? '');
        }

        // Source product ids that are produced by a recipe -> must exist as products
        // (WIP intermediates) so recipes can attach, even if flagged not-for-selling.
        $recipeOutputs = [];
        foreach ($this->loadExport('recipes') as $r) {
            $pid = $varToProduct[(string) ($r['variation_id'] ?? '')] ?? null;
            if ($pid) {
                $recipeOutputs[$pid] = true;
            }
        }

        $this->info(count($products).' source products.'.($dryRun ? '  [DRY RUN]' : ''));

        $created = $updated = $skippedItem = $skippedNoDept = $skippedExisting = 0;

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $p) {
            $bar->advance();

            $sku = (string) ($p['id'] ?? '');
            $name = $this->cleanName($p['product'] ?? null);
            if ($sku === '' || $name === '') {
                continue;
            }

            $class = $this->classify(
                (string) ($p['category'] ?? ''),
                $name,
                (int) ($p['not_for_selling'] ?? 0) === 1,
            );

            // A recipe output must be a (WIP) product so its recipe can attach,
            // even if the category map / not-for-selling flag would make it an item.
            $isRecipeOutput = isset($recipeOutputs[$sku]);
            if ($class['kind'] === 'item' && ! $isRecipeOutput) {
                $skippedItem++;

                continue; // pure raw materials are handled by the items/stock importers
            }

            $isWip = $class['kind'] === 'wip' || ($class['kind'] === 'item' && $isRecipeOutput);

            // Department: category map → by-name → configured WIP fallback.
            $deptName = $class['dept']
                ?? $this->classifyByName($name)
                ?? ($isWip ? $this->option('wip-department') : null);
            $deptId = $this->resolveDepartmentByName($deptName, $branchId);
            if (! $deptId) {
                $skippedNoDept++;

                continue;
            }
            $typeId = $this->getProductTypeId($deptId, $isWip, $dryRun);
            if (! $typeId && ! $dryRun) {
                $skippedNoDept++;

                continue;
            }

            $price = $vmap[$sku]['price'] ?? 0.0;
            $uomId = $this->richineiUomId($vmap[$sku]['unit'] ?? ($p['unit'] ?? null));

            // Match by SKU first, then by NAME within the branch. Live's earlier
            // migration created these products under generated SKUs (IMP-*/WIP-*),
            // not the source numeric id, so name-matching prevents duplicates.
            $existing = Product::where('branch_id', $branchId)->where('sku', $sku)->first()
                ?? Product::whereNull('branch_id')->where('sku', $sku)->first()
                ?? Product::where('branch_id', $branchId)->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first()
                ?? Product::whereNull('branch_id')->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first();

            if ($existing) {
                $fill = [];
                if (! $existing->product_type_id) {
                    $fill['product_type_id'] = $typeId;
                }
                if (! $existing->sales_department_id) {
                    $fill['sales_department_id'] = $deptId;
                }
                if (! $existing->department_id) {
                    $fill['department_id'] = $deptId;
                }
                if (! $existing->uom_id && $uomId) {
                    $fill['uom_id'] = $uomId;
                }
                if ((! $existing->price || (float) $existing->price == 0.0) && $price > 0) {
                    $fill['price'] = round($price, 2);
                }
                if (! $existing->branch_id) {
                    $fill['branch_id'] = $branchId;
                }

                if ($fill && ! $dryRun) {
                    $existing->update($fill);
                }
                $fill ? $updated++ : $skippedExisting++;

                continue;
            }

            if (! $dryRun) {
                Product::create([
                    'name' => $name,
                    'sku' => $sku,
                    'branch_id' => $branchId,
                    'product_type_id' => $typeId,
                    'department_id' => $deptId,
                    'sales_department_id' => $deptId,
                    'price' => $price > 0 ? round($price, 2) : 0,
                    'cost' => 0, // cost backfilled from recipes later; source list has no cost
                    'uom_id' => $uomId,
                    'is_active' => true,
                    'is_available' => ! $isWip,
                    'is_menu_item' => ! $isWip,
                ]);
            }
            $created++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Products — created: {$created}, updated: {$updated}, already-complete: {$skippedExisting}");
        $this->line("Skipped — raw items (not products): {$skippedItem}, no department resolved: {$skippedNoDept}");

        if ($dryRun) {
            $this->warn('DRY RUN: nothing was written.');
        }

        return self::SUCCESS;
    }

    /** Get/create the "Finished Goods" / "Work in Progress" product type for a department. */
    protected function getProductTypeId(int $departmentId, bool $isWip, bool $dryRun): ?int
    {
        $name = $isWip ? 'Work in Progress' : 'Finished Goods';
        $key = $departmentId.'|'.$name;

        if (array_key_exists($key, $this->productTypeCache)) {
            return $this->productTypeCache[$key];
        }

        $type = ProductType::withTrashed()
            ->where('department_id', $departmentId)
            ->where('name', $name)
            ->first();

        if ($type && $type->trashed()) {
            $type->restore();
        }

        if (! $type && ! $dryRun) {
            $type = ProductType::create([
                'department_id' => $departmentId,
                'name' => $name,
                'code' => $this->uniqueTypeCode($name, $departmentId),
                'description' => "{$name} (Richinei import)",
                'status' => 'active',
                'sort_order' => 0,
            ]);
        }

        return $this->productTypeCache[$key] = $type?->id;
    }

    protected function uniqueTypeCode(string $name, int $deptId): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 2)).$deptId;
        $code = $base;
        $i = 1;
        while (ProductType::where('code', $code)->exists()) {
            $code = $base.$i++;
        }

        return $code;
    }
}
