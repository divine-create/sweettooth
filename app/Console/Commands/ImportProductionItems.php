<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\Item;
use App\Models\RecipeIngredient;
use App\Models\RawMaterialUtilization;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductionItems extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-items
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign items to}
        {--mode=merge : merge or replace}
        {--force : Required when using mode=replace}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import ingredient items from production JSON files';

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
                $this->warn('Replace mode: truncating recipe_ingredients, raw_material_utilizations, stock_movements, stocks, items.');
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                RecipeIngredient::truncate();
                RawMaterialUtilization::truncate();
                StockMovement::truncate();
                Stock::truncate();
                Item::truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        $rows = $this->loadJsonRows($path);
        if (empty($rows)) {
            $this->warn('No rows to import.');
            return 0;
        }

        $items = [];
        foreach ($rows as $row) {
            foreach ($row['ingredients'] ?? [] as $ingredient) {
                [$name, $externalId] = $this->parseNameAndExternalId($ingredient['ingredient'] ?? '');
                if ($name === '') {
                    continue;
                }

                [$qty, $uomToken] = $this->parseQuantityAndUom($ingredient['input_qty'] ?? '');

                $key = $externalId ?: $this->normalizeKey($name);
                if (! isset($items[$key])) {
                    $items[$key] = [
                        'name' => $name,
                        'external_id' => $externalId,
                        'uom_token' => $uomToken,
                        'category' => $this->classifyCategory($name),
                    ];
                } elseif (! $items[$key]['uom_token'] && $uomToken) {
                    $items[$key]['uom_token'] = $uomToken;
                }
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $itemData) {
            $uomId = $this->resolveUomId($itemData['uom_token']);

            $query = Item::query()->where('branch_id', $branchId);
            if ($itemData['external_id']) {
                $query->where('sku', $itemData['external_id']);
            } else {
                $query->whereRaw('LOWER(name) = ?', [strtolower($itemData['name'])]);
            }
            $item = $query->first();

            if ($item) {
                $needsUpdate = false;
                $update = [];

                if ($uomId && ! $item->uom_id) {
                    $update['uom_id'] = $uomId;
                    $needsUpdate = true;
                }
                if ($item->category !== $itemData['category']) {
                    $update['category'] = $itemData['category'];
                    $needsUpdate = true;
                }

                if ($needsUpdate && ! $dryRun) {
                    $item->update($update);
                }

                $updated += $needsUpdate ? 1 : 0;
                $skipped += $needsUpdate ? 0 : 1;
            } else {
                if (! $dryRun) {
                    $item = Item::create([
                        'branch_id' => $branchId,
                        'name' => $itemData['name'],
                        'sku' => $itemData['external_id'] ?: $this->generateSku($itemData['name'], Item::class),
                        'category' => $itemData['category'],
                        'uom_id' => $uomId,
                        'status' => 'active',
                    ]);

                    Stock::firstOrCreate([
                        'branch_id' => $branchId,
                        'item_id' => $item->id,
                    ], [
                        'quantity_available' => 0,
                        'quantity_reserved' => 0,
                        'quantity_damaged' => 0,
                        'average_cost' => 0,
                        'health_status' => 'good',
                    ]);
                }

                $created++;
            }
        }

        $this->info("Items processed: " . count($items));
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");

        return 0;
    }
}
