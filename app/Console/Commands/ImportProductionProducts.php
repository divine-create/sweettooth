<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductionProducts extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-products
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign products to}
        {--department-map= : JSON map or file of location => department_id}
        {--product-type-map= : JSON map or file of location => product_type_id}
        {--create-missing-departments : Create departments when missing}
        {--department-category-id= : Category UUID for auto-created departments}
        {--create-missing-product-types : Create product types when missing}
        {--mode=merge : merge or replace}
        {--force : Required when using mode=replace}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import products from production JSON files';

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
                $this->warn('Replace mode: truncating production_records, daily_produces, recipes, products.');
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('production_records')->truncate();
                DB::table('daily_produces')->truncate();
                DB::table('recipe_ingredients')->truncate();
                DB::table('recipes')->truncate();
                DB::table('products')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        $departmentMap = $this->loadMapOption($this->option('department-map'));
        $productTypeMap = $this->loadMapOption($this->option('product-type-map'));

        $rows = $this->loadJsonRows($path);
        if (empty($rows)) {
            $this->warn('No rows to import.');
            return 0;
        }

        $groups = [];
        foreach ($rows as $row) {
            [$name, $externalId] = $this->parseNameAndExternalId($row['produced_product'] ?? '');
            if ($name === '') {
                continue;
            }

            [$producedQty, $uomToken] = $this->parseQuantityAndUom($row['quantity_produced'] ?? '');
            $totalCost = $this->parseMoney($row['total_cost'] ?? '');
            $location = $row['table_info']['location'] ?? null;

            $key = $externalId ?: $this->normalizeKey($name);
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $name,
                    'external_id' => $externalId,
                    'uom_tokens' => [],
                    'locations' => [],
                    'unit_costs' => [],
                ];
            }

            if ($uomToken) {
                $groups[$key]['uom_tokens'][$uomToken] = ($groups[$key]['uom_tokens'][$uomToken] ?? 0) + 1;
            }

            if ($location) {
                $locKey = $this->normalizeKey($location);
                $groups[$key]['locations'][$locKey] = ($groups[$key]['locations'][$locKey] ?? 0) + 1;
            }

            if ($producedQty > 0 && $totalCost > 0) {
                $groups[$key]['unit_costs'][] = $totalCost / $producedQty;
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
                $this->warn("Skipping product {$group['name']}: no department for location {$location}.");
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
                $this->warn("Skipping product {$group['name']}: no product type for location {$location}.");
                $skipped++;
                continue;
            }

            $uomToken = '';
            if (! empty($group['uom_tokens'])) {
                arsort($group['uom_tokens']);
                $uomToken = array_key_first($group['uom_tokens']);
            }
            $uomId = $this->resolveUomId($uomToken);

            $unitCost = 0.0;
            if (! empty($group['unit_costs'])) {
                $unitCost = array_sum($group['unit_costs']) / count($group['unit_costs']);
            }

            $query = Product::query();
            if ($group['external_id']) {
                $query->where('sku', $group['external_id']);
            } else {
                $query->whereRaw('LOWER(name) = ?', [strtolower($group['name'])]);
            }
            $product = $query->first();

            if ($product) {
                $update = [];
                if (! $product->product_type_id) {
                    $update['product_type_id'] = $productTypeId;
                }
                if (! $product->sales_department_id) {
                    $update['sales_department_id'] = $departmentId;
                }
                if (! $product->uom_id && $uomId) {
                    $update['uom_id'] = $uomId;
                }
                if ($unitCost > 0 && (! $product->cost || $product->cost == 0)) {
                    $update['cost'] = $unitCost;
                }
                if ($product->branch_id !== $branchId) {
                    $update['branch_id'] = $branchId;
                }

                if (! empty($update)) {
                    if (! $dryRun) {
                        $product->update($update);
                    }
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                if (! $dryRun) {
                    Product::create([
                        'name' => $group['name'],
                        'sku' => $group['external_id'] ?: $this->generateSku($group['name'], Product::class),
                        'branch_id' => $branchId,
                        'product_type_id' => $productTypeId,
                        'sales_department_id' => $departmentId,
                        'price' => 0,
                        'cost' => $unitCost,
                        'shelf_life_days' => 0,
                        'uom_id' => $uomId,
                        'is_active' => true,
                        'is_available' => true,
                    ]);
                }
                $created++;
            }
        }

        $this->info("Products processed: " . count($groups));
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");

        return 0;
    }
}
