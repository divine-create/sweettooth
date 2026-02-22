<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\DailyProduce;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\UomConversionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductionStockMovements extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-stock-movements
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign movements to}
        {--department-map= : JSON map or file of location => department_id}
        {--create-missing-departments : Create departments when missing}
        {--department-category-id= : Category UUID for auto-created departments}
        {--use-approved : Use approved quantity instead of produced quantity}
        {--mode=merge : merge or replace}
        {--force : Required when using mode=replace}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Create stock movements for ingredient usage based on production records';

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
                $this->warn('Replace mode: removing existing production stock movements and restoring stock quantities.');
                $movements = StockMovement::query()
                    ->where('reference_type', ProductionRecord::class)
                    ->orWhere('reference_type', 'production_record')
                    ->get();

                foreach ($movements as $movement) {
                    if ($movement->type === 'out' && $movement->stock_id) {
                        $stock = Stock::find($movement->stock_id);
                        if ($stock) {
                            $stock->quantity_available = $stock->quantity_available + (float) $movement->quantity;
                            $stock->save();
                        }
                    }
                }

                StockMovement::query()
                    ->where('reference_type', ProductionRecord::class)
                    ->orWhere('reference_type', 'production_record')
                    ->delete();
            }
        }

        $departmentMap = $this->loadMapOption($this->option('department-map'));
        $rows = $this->loadJsonRows($path);
        if (empty($rows)) {
            $this->warn('No rows to import.');
            return 0;
        }

        $conversionService = app(UomConversionService::class);
        $recordCache = [];
        $recipeCache = [];
        $created = 0;
        $skipped = 0;
        $warnings = 0;
        $useApproved = (bool) $this->option('use-approved');

        foreach ($rows as $row) {
            $referenceNo = trim((string) ($row['reference_no'] ?? ''));
            if ($referenceNo === '') {
                $skipped++;
                continue;
            }

            if (! isset($recordCache[$referenceNo])) {
                $recordCache[$referenceNo] = ProductionRecord::query()
                    ->with(['recipe.ingredients.item', 'dailyProduce.shift'])
                    ->where('batch_number', $referenceNo)
                    ->first();
            }

            $record = $recordCache[$referenceNo];
            if (! $record) {
                $this->warn("Skipping reference {$referenceNo}: production record not found.");
                $warnings++;
                continue;
            }

            $recipe = $record->recipe;
            if (! $recipe) {
                $this->warn("Skipping reference {$referenceNo}: recipe missing.");
                $warnings++;
                continue;
            }

            $recipeId = $recipe->id;
            if (! isset($recipeCache[$recipeId])) {
                $recipeCache[$recipeId] = $recipe->loadMissing(['ingredients.item']);
            }
            $recipe = $recipeCache[$recipeId];

            $unitsProduced = $useApproved
                ? (float) $record->quantity_approved
                : (float) $record->quantity_produced;

            if ($unitsProduced <= 0) {
                $skipped++;
                continue;
            }

            $yieldQty = (float) ($recipe->yield_quantity ?: 1);
            $batchFactor = $yieldQty > 0 ? ($unitsProduced / $yieldQty) : $unitsProduced;

            $productionTime = $record->production_time ?: Carbon::now();
            $tableInfo = $row['table_info'] ?? [];
            $location = $tableInfo['location'] ?? '';
            $departmentId = $this->resolveDepartmentId(
                $location,
                $branchId,
                $departmentMap,
                (bool) $this->option('create-missing-departments'),
                $this->option('department-category-id')
            );

            if (! $departmentId) {
                $this->warn("Skipping reference {$referenceNo}: no department for location {$location}.");
                $warnings++;
                continue;
            }

            $shiftType = $productionTime->hour < 14 ? 'morning' : 'afternoon';

            foreach ($recipe->ingredients as $ingredient) {
                $item = $ingredient->item;
                if (! $item) {
                    $this->warn("Skipping ingredient for reference {$referenceNo}: item missing.");
                    $warnings++;
                    continue;
                }

                $ingredientUomId = $ingredient->uom_id;
                $itemUomId = $item->uom_id;
                if (! $ingredientUomId || ! $itemUomId) {
                    $this->warn("Skipping ingredient {$item->name}: missing UOM.");
                    $warnings++;
                    continue;
                }

                $quantityInIngredientUom = $ingredient->getActualQuantityNeeded() * $batchFactor;

                $quantityInItemUom = $quantityInIngredientUom;
                if ((int) $ingredientUomId !== (int) $itemUomId) {
                    $converted = $conversionService->tryConvert(
                        $quantityInIngredientUom,
                        (int) $ingredientUomId,
                        (int) $itemUomId,
                        [
                            'branch_id' => $branchId,
                            'item_id' => (int) $item->id,
                        ]
                    );

                    if ($converted === null) {
                        $this->warn("Skipping ingredient {$item->name}: no UOM conversion.");
                        $warnings++;
                        continue;
                    }

                    $quantityInItemUom = $converted;
                }

                if ($quantityInItemUom <= 0) {
                    $skipped++;
                    continue;
                }

                $stock = Stock::firstOrCreate([
                    'branch_id' => $branchId,
                    'item_id' => $item->id,
                ], [
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                    'quantity_damaged' => 0,
                    'average_cost' => 0,
                    'health_status' => 'good',
                ]);

                $existing = StockMovement::query()
                    ->where('reference_type', ProductionRecord::class)
                    ->where('reference_id', $record->id)
                    ->where('stock_id', $stock->id)
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                $before = (float) $stock->quantity_available;
                $after = $before - $quantityInItemUom;

                if (! $dryRun) {
                    $stock->quantity_available = $after;
                    $stock->save();

                    $movement = StockMovement::create([
                        'stock_id' => $stock->id,
                        'type' => 'out',
                        'quantity' => $quantityInItemUom,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'reference_type' => ProductionRecord::class,
                        'reference_id' => $record->id,
                        'moved_by_type' => $record->produced_by_type,
                        'moved_by_id' => $record->produced_by_id,
                        'movement_date' => $productionTime,
                        'created_at' => $productionTime,
                        'updated_at' => $productionTime,
                        'notes' => "Imported consumption for {$recipe->product_name} ({$referenceNo})",
                    ]);

                    $movement->forceFill([
                        'shift' => $shiftType,
                        'department_id' => $departmentId,
                    ])->save();

                    if ((int) $ingredientUomId === (int) $itemUomId && $ingredient->cost_per_unit > 0) {
                        $movement->forceFill([
                            'unit_cost' => (float) $ingredient->cost_per_unit,
                            'cost_impact' => (float) $ingredient->cost_per_unit * $quantityInItemUom,
                        ])->save();
                    }
                }

                $created++;
            }
        }

        $this->info("Stock movements created: {$created}");
        $this->info("Skipped: {$skipped}");
        if ($warnings > 0) {
            $this->warn("Warnings: {$warnings}");
        }

        return 0;
    }
}
