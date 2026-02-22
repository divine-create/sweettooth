<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Item;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\UomConversionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportStockMovementsFast extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:stock-movements-fast
        {--branch-id= : Branch UUID}
        {--start-date= : Start date (YYYY-MM-DD)}
        {--end-date= : End date (YYYY-MM-DD)}
        {--limit= : Limit number of records to process}
        {--dry-run : Parse only}';

    protected $description = 'Fast stock movements import from production records';

    public function handle(): int
    {
        $branchId = $this->option('branch-id') ?: Branch::value('id');
        $startDate = $this->option('start-date') ? Carbon::parse($this->option('start-date')) : null;
        $endDate = $this->option('end-date') ? Carbon::parse($this->option('end-date')) : null;
        $limit = (int) ($this->option('limit') ?: 500);
        $dryRun = (bool) $this->option('dry-run');

        if (! $branchId) {
            $this->error('No branch found.');
            return 1;
        }

        $this->info("Importing stock movements from production records...");
        if ($startDate) $this->info("From: {$startDate->toDateString()}");
        if ($endDate) $this->info("To: {$endDate->toDateString()}");
        $this->info("Limit: {$limit} records");

        // Get production records with recipes
        $query = ProductionRecord::query()
            ->with(['recipe.ingredients.item'])
            ->whereHas('recipe', function ($q) {
                $q->has('ingredients');
            });

        if ($startDate) {
            $query->where('production_time', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('production_time', '<=', $endDate);
        }

        $total = $query->count();
        $this->info("Total production records found: {$total}");

        $records = $query->limit($limit)->get();
        
        $created = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $record) {
            try {
                $recipe = $record->recipe;
                if (! $recipe || ! $recipe->ingredients) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Calculate batch factor
                $unitsProduced = (float) $record->quantity_approved;
                $yieldQty = (float) ($recipe->yield_quantity ?: 1);
                $batchFactor = $yieldQty > 0 ? ($unitsProduced / $yieldQty) : $unitsProduced;

                if ($batchFactor <= 0) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $productionTime = $record->production_time ?: now();
                $shiftType = $productionTime->hour < 14 ? 'morning' : 'afternoon';

                foreach ($recipe->ingredients as $ingredient) {
                    $item = $ingredient->item;
                    if (! $item) continue;

                    $quantityNeeded = $ingredient->getActualQuantityNeeded() * $batchFactor;
                    if ($quantityNeeded <= 0) continue;

                    // Get or create stock
                    $stock = Stock::firstOrCreate(
                        ['branch_id' => $branchId, 'item_id' => $item->id],
                        ['quantity_available' => 0, 'quantity_reserved' => 0, 'quantity_damaged' => 0]
                    );

                    // Check if movement exists
                    $exists = StockMovement::query()
                        ->where('reference_type', ProductionRecord::class)
                        ->where('reference_id', $record->id)
                        ->where('stock_id', $stock->id)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    if (! $dryRun) {
                        $before = (float) $stock->quantity_available;
                        $after = $before - $quantityNeeded;

                        $stock->quantity_available = $after;
                        $stock->saveQuietly();

                        StockMovement::create([
                            'stock_id' => $stock->id,
                            'branch_id' => $branchId,
                            'type' => 'out',
                            'quantity' => $quantityNeeded,
                            'quantity_before' => $before,
                            'quantity_after' => $after,
                            'reference_type' => ProductionRecord::class,
                            'reference_id' => $record->id,
                            'moved_by_type' => $record->produced_by_type,
                            'moved_by_id' => $record->produced_by_id,
                            'movement_date' => $productionTime,
                            'shift' => $shiftType,
                            'department_id' => $record->dailyProduce?->shift?->department_id,
                            'unit_cost' => $item->unit_price ?? 0,
                            'cost_impact' => ($item->unit_price ?? 0) * $quantityNeeded,
                            'notes' => "Production: {$recipe->product_name} (Ref: {$record->batch_number})",
                        ]);
                    }

                    $created++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
            $bar->advance();
            
            // Commit every 50 records
            if ($created % 50 === 0 && $created > 0) {
                DB::commit();
                DB::beginTransaction();
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("Stock movements import completed:");
        $this->info("  Created: {$created}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Errors: {$errors}");

        return 0;
    }
}
