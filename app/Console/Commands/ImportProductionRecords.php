<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\DailyProduce;
use App\Models\Product;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportProductionRecords extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-records
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign records to}
        {--department-map= : JSON map or file of location => department_id}
        {--create-missing-departments : Create departments when missing}
        {--department-category-id= : Category UUID for auto-created departments}
        {--produced-by-id= : User UUID to set as produced_by}
        {--mode=merge : merge or replace}
        {--force : Required when using mode=replace}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import production records (shifts, daily produces, production records) from production JSON files';

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
                $this->warn('Replace mode: truncating production_records and daily_produces.');
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                ProductionRecord::truncate();
                DailyProduce::truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        $departmentMap = $this->loadMapOption($this->option('department-map'));

        $producedById = $this->option('produced-by-id');
        if (! $producedById) {
            $producedById = User::query()->value('id');
        }
        if (! $producedById) {
            $this->error('No users found. Provide --produced-by-id.');
            return 1;
        }

        $rows = $this->loadJsonRows($path);
        if (empty($rows)) {
            $this->warn('No rows to import.');
            return 0;
        }

        $created = 0;
        $skipped = 0;
        $dailyProduceTotals = [];

        foreach ($rows as $row) {
            [$productName, $productExternalId] = $this->parseNameAndExternalId($row['produced_product'] ?? '');
            if ($productName === '') {
                continue;
            }

            $productQuery = Product::query();
            if ($productExternalId) {
                $productQuery->where('sku', $productExternalId);
            } else {
                $productQuery->whereRaw('LOWER(name) = ?', [strtolower($productName)]);
            }
            $product = $productQuery->first();

            if (! $product) {
                $this->warn("Skipping production record for {$productName}: product not found.");
                $skipped++;
                continue;
            }

            $recipe = Recipe::query()->where('product_id', $product->id)->first();
            if (! $recipe) {
                $recipe = Recipe::query()->whereRaw('LOWER(product_name) = ?', [strtolower($productName)])->first();
            }
            if (! $recipe) {
                $this->warn("Skipping production record for {$productName}: recipe not found.");
                $skipped++;
                continue;
            }

            [$producedQty] = $this->parseQuantityAndUom($row['quantity_produced'] ?? '');
            [$wastedQty] = $this->parseQuantityAndUom($row['wasted_quantity'] ?? '');

            $producedQty = max(0.0, $producedQty);
            $wastedQty = max(0.0, $wastedQty);
            $approvedQty = max(0.0, $producedQty - $wastedQty);

            $referenceNo = trim((string) ($row['reference_no'] ?? ''));
            $tableInfo = $row['table_info'] ?? [];
            $dateTimeRaw = $tableInfo['date_time'] ?? null;
            $productionDateRaw = $row['production_date'] ?? null;

            $productionTime = null;
            if ($dateTimeRaw) {
                $productionTime = Carbon::createFromFormat('m/d/Y h:i A', $dateTimeRaw);
            } elseif ($productionDateRaw) {
                $productionTime = Carbon::createFromFormat('m/d/Y', $productionDateRaw)->setTime(12, 0);
            } else {
                $productionTime = Carbon::now();
            }

            $location = $tableInfo['location'] ?? '';
            $departmentId = $this->resolveDepartmentId(
                $location,
                $branchId,
                $departmentMap,
                (bool) $this->option('create-missing-departments'),
                $this->option('department-category-id')
            );

            if (! $departmentId) {
                $this->warn("Skipping production record {$referenceNo}: no department for location {$location}.");
                $skipped++;
                continue;
            }

            $shiftType = $productionTime->hour < 14 ? 'morning' : 'afternoon';
            $shiftDate = $productionTime->copy()->startOfDay()->toDateString();

            $shift = Shift::query()
                ->where('branch_id', $branchId)
                ->where('department_id', $departmentId)
                ->where('shift_date', $shiftDate)
                ->where('shift_type', $shiftType)
                ->first();

            if (! $shift) {
                $shiftNumberBase = Str::upper("{$shiftDate}-{$departmentId}-{$shiftType}");
                $shiftNumber = $shiftNumberBase;
                $counter = 1;
                while (Shift::where('shift_number', $shiftNumber)->exists()) {
                    $shiftNumber = $shiftNumberBase . '-' . $counter;
                    $counter++;
                }

                if (! $dryRun) {
                    $shift = Shift::create([
                        'branch_id' => $branchId,
                        'department_id' => $departmentId,
                        'employee_id' => $producedById,
                        'shift_number' => $shiftNumber,
                        'shift_date' => $shiftDate,
                        'shift_type' => $shiftType,
                        'status' => 'closed',
                        'notes' => 'Imported shift',
                    ]);
                }
            }

            if (! $shift) {
                $this->warn("Skipping production record {$referenceNo}: could not create shift.");
                $skipped++;
                continue;
            }

            $dailyProduce = DailyProduce::query()
                ->where('shift_id', $shift->id)
                ->where('recipe_id', $recipe->id)
                ->first();

            if (! $dailyProduce && ! $dryRun) {
                $dailyProduce = DailyProduce::create([
                    'shift_id' => $shift->id,
                    'recipe_id' => $recipe->id,
                    'produce_date' => $shiftDate,
                    'shift_type' => $shiftType,
                    'opening_quantity' => 0,
                    'requested_quantity' => 0,
                    'produced_quantity' => 0,
                    'sent_out_quantity' => 0,
                    'order_quantity' => 0,
                    'callback_quantity' => 0,
                    'closing_quantity' => 0,
                    'expected_closing' => 0,
                    'variance' => 0,
                ]);
            }

            if (! $dailyProduce) {
                $this->warn("Skipping production record {$referenceNo}: daily produce not found.");
                $skipped++;
                continue;
            }

            if ($referenceNo) {
                $existing = ProductionRecord::query()
                    ->where('batch_number', $referenceNo)
                    ->first();
                if ($existing) {
                    $skipped++;
                    continue;
                }
            }

            if (! $dryRun) {
                $record = ProductionRecord::create([
                    'daily_produce_id' => $dailyProduce->id,
                    'recipe_id' => $recipe->id,
                    'batch_number' => $referenceNo ?: null,
                    'produced_by_id' => $producedById,
                    'produced_by_type' => User::class,
                    'quantity_produced' => $producedQty,
                    'quantity_approved' => $approvedQty,
                    'quantity_rejected' => $wastedQty,
                    'quantity_sent_out' => 0,
                    'quantity_for_order' => 0,
                    'quantity_remaining' => $approvedQty,
                    'production_time' => $productionTime,
                    'quality_status' => 'good',
                    'dispatch_status' => 'available',
                    'notes' => $referenceNo ? "Imported ref: {$referenceNo}" : 'Imported record',
                ]);

                $totalCost = $this->parseMoney($row['total_cost'] ?? '');
                $unitCost = ($producedQty > 0 && $totalCost > 0) ? ($totalCost / $producedQty) : 0.0;
                $record->forceFill([
                    'unit_cost' => $unitCost,
                    'total_production_cost' => $totalCost,
                ])->save();
            }

            $dailyProduceTotals[$dailyProduce->id] = ($dailyProduceTotals[$dailyProduce->id] ?? 0) + $approvedQty;
            $created++;
        }

        if (! $dryRun && ! empty($dailyProduceTotals)) {
            foreach ($dailyProduceTotals as $dailyProduceId => $totalProduced) {
                $produce = DailyProduce::find($dailyProduceId);
                if (! $produce) {
                    continue;
                }

                $produce->produced_quantity = $totalProduced;
                $produce->closing_quantity = $totalProduced;
                $produce->expected_closing = $produce->calculateExpectedClosing();
                $produce->variance = $produce->calculateVariance();
                $produce->save();
            }
        }

        $this->info("Production records created: {$created}");
        $this->info("Skipped: {$skipped}");

        return 0;
    }
}
