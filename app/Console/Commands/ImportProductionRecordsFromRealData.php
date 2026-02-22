<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\Branch;
use App\Models\DailyProduce;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportProductionRecordsFromRealData extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-records-real
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign records to}
        {--produced-by-id= : User UUID to set as produced_by}
        {--create-shifts : Create shifts if missing}
        {--create-departments : Create departments if missing}
        {--department-category-id= : Category ID for auto-created departments}
        {--start-date= : Only import records from this date onwards (YYYY-MM-DD)}
        {--end-date= : Only import records up to this date (YYYY-MM-DD)}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import production records from real production JSON files with proper date handling';

    protected array $departmentCache = [];
    protected array $shiftCache = [];
    protected array $dailyProduceCache = [];
    protected array $recipeCache = [];

    public function handle(): int
    {
        $path = $this->argument('path') ?: base_path('real_data/receips-sweetooth-main');
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id or create a branch first.');
            return 1;
        }

        $producedById = $this->option('produced-by-id');
        if (! $producedById) {
            $producedById = User::query()->value('id');
        }
        if (! $producedById) {
            $this->error('No users found. Provide --produced-by-id.');
            return 1;
        }

        // Parse date filters
        $startDate = $this->option('start-date') ? Carbon::parse($this->option('start-date')) : null;
        $endDate = $this->option('end-date') ? Carbon::parse($this->option('end-date')) : null;

        $rows = $this->loadJsonRows($path);
        if (empty($rows)) {
            $this->warn('No rows to import.');
            return 0;
        }

        // Filter by date range if specified
        $filteredRows = $this->filterByDateRange($rows, $startDate, $endDate);
        
        $this->info("Found " . count($filteredRows) . " production records to import" . 
            ($startDate ? " from {$startDate->toDateString()}" : "") .
            ($endDate ? " to {$endDate->toDateString()}" : "") . ".");

        $created = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar(count($filteredRows));
        $bar->start();

        foreach ($filteredRows as $row) {
            $result = $this->importProductionRecord($row, $branchId, $producedById, $dryRun);
            
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Production records import completed:");
        $this->info("  Created: {$created}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Errors: {$errors}");

        return 0;
    }

    protected function filterByDateRange(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        if (! $startDate && ! $endDate) {
            return $rows;
        }

        return array_filter($rows, function ($row) use ($startDate, $endDate) {
            $productionDate = $this->parseProductionDate($row);
            
            if ($startDate && $productionDate->lt($startDate)) {
                return false;
            }
            
            if ($endDate && $productionDate->gt($endDate)) {
                return false;
            }
            
            return true;
        });
    }

    protected function parseProductionDate(array $row): Carbon
    {
        // Try to parse from table_info date_time first
        $dateTimeRaw = $row['table_info']['date_time'] ?? null;
        if ($dateTimeRaw) {
            $parsed = Carbon::createFromFormat('m/d/Y h:i A', $dateTimeRaw);
            if ($parsed) {
                return $parsed;
            }
        }

        // Fall back to production_date
        $productionDateRaw = $row['production_date'] ?? null;
        if ($productionDateRaw) {
            $parsed = Carbon::createFromFormat('m/d/Y', $productionDateRaw);
            if ($parsed) {
                return $parsed->setTime(12, 0);
            }
        }

        return Carbon::now();
    }

    protected function importProductionRecord(array $row, string $branchId, string $producedById, bool $dryRun): string
    {
        $referenceNo = trim((string) ($row['reference_no'] ?? ''));
        
        // Check if already imported
        if ($referenceNo && ProductionRecord::where('batch_number', $referenceNo)->exists()) {
            return 'skipped';
        }

        // Parse product info
        [$productName, $productExternalId] = $this->parseNameAndExternalId($row['produced_product'] ?? '');
        if ($productName === '') {
            return 'error';
        }

        // Find product
        $product = $this->findProduct($productName, $productExternalId);
        if (! $product) {
            $this->warn("Skipping {$referenceNo}: product '{$productName}' not found.");
            return 'error';
        }

        // Find recipe
        $recipe = $this->findRecipe($product);
        if (! $recipe) {
            $this->warn("Skipping {$referenceNo}: recipe for '{$productName}' not found.");
            return 'error';
        }

        // Parse quantities
        [$producedQty] = $this->parseQuantityAndUom($row['quantity_produced'] ?? '');
        [$wastedQty] = $this->parseQuantityAndUom($row['wasted_quantity'] ?? '');

        $producedQty = max(0.0, $producedQty);
        $wastedQty = max(0.0, $wastedQty);
        $approvedQty = max(0.0, $producedQty - $wastedQty);

        // Parse date/time
        $productionTime = $this->parseProductionDate($row);
        $shiftDate = $productionTime->toDateString();
        $shiftType = $productionTime->hour < 14 ? 'morning' : 'afternoon';

        // Get location and department
        $location = $row['table_info']['location'] ?? '';
        $departmentId = $this->getOrCreateDepartment($location, $branchId, $dryRun);
        
        if (! $departmentId) {
            $this->warn("Skipping {$referenceNo}: no department for location '{$location}'.");
            return 'error';
        }

        // Get or create shift
        $shift = $this->getOrCreateShift($departmentId, $branchId, $producedById, $shiftDate, $shiftType, $dryRun);
        if (! $shift) {
            $this->warn("Skipping {$referenceNo}: could not create shift.");
            return 'error';
        }

        // Get or create daily produce
        $dailyProduce = $this->getOrCreateDailyProduce($shift, $recipe, $dryRun);
        if (! $dailyProduce) {
            $this->warn("Skipping {$referenceNo}: could not create daily produce.");
            return 'error';
        }

        // Create production record
        if (! $dryRun) {
            $totalCost = $this->parseMoney($row['total_cost'] ?? '');
            $unitCost = $producedQty > 0 && $totalCost > 0 ? ($totalCost / $producedQty) : 0;

            ProductionRecord::create([
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
                'unit_cost' => $unitCost,
                'total_production_cost' => $totalCost,
                'notes' => $referenceNo ? "Imported ref: {$referenceNo}" : 'Imported record',
            ]);
        }

        return 'created';
    }

    protected function findProduct(string $name, ?string $externalId): ?Product
    {
        $query = Product::query();
        if ($externalId) {
            $query->where('sku', $externalId);
        } else {
            $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        }
        return $query->first();
    }

    protected function findRecipe(Product $product): ?Recipe
    {
        $cacheKey = "product_{$product->id}";
        
        if (isset($this->recipeCache[$cacheKey])) {
            return Recipe::find($this->recipeCache[$cacheKey]);
        }

        $recipe = Recipe::where('product_id', $product->id)->first();
        
        if ($recipe) {
            $this->recipeCache[$cacheKey] = $recipe->id;
        }

        return $recipe;
    }

    protected function getOrCreateDepartment(string $location, string $branchId, bool $dryRun): ?int
    {
        if (! $location) {
            return null;
        }

        $locKey = $this->normalizeKey($location);
        
        if (isset($this->departmentCache[$locKey])) {
            return $this->departmentCache[$locKey];
        }

        $department = Department::whereRaw('UPPER(name) = ?', [$location])->first();

        if (! $department && $this->option('create-departments')) {
            $categoryId = $this->option('department-category-id') ?: \App\Models\DepartmentCategory::value('id');
            
            if ($categoryId && ! $dryRun) {
                $department = Department::create([
                    'branch_id' => $branchId,
                    'category_id' => $categoryId,
                    'name' => $location,
                    'description' => "{$location} (imported)",
                ]);
                $this->warn("Created department: {$location}");
            }
        }

        $this->departmentCache[$locKey] = $department?->id;
        return $department?->id;
    }

    protected function getOrCreateShift(
        int $departmentId,
        string $branchId,
        string $producedById,
        string $shiftDate,
        string $shiftType,
        bool $dryRun
    ): ?Shift {
        $cacheKey = "{$branchId}_{$departmentId}_{$shiftDate}_{$shiftType}";
        
        if (isset($this->shiftCache[$cacheKey])) {
            return Shift::find($this->shiftCache[$cacheKey]);
        }

        $shift = Shift::query()
            ->where('branch_id', $branchId)
            ->where('department_id', $departmentId)
            ->where('shift_date', $shiftDate)
            ->where('shift_type', $shiftType)
            ->first();

        if (! $shift && $this->option('create-shifts')) {
            if (! $dryRun) {
                $shiftNumberBase = Str::upper("{$shiftDate}-{$departmentId}-{$shiftType}");
                $shiftNumber = $shiftNumberBase;
                $counter = 1;
                
                while (Shift::where('shift_number', $shiftNumber)->exists()) {
                    $shiftNumber = $shiftNumberBase . '-' . $counter;
                    $counter++;
                }

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

        if ($shift) {
            $this->shiftCache[$cacheKey] = $shift->id;
        }

        return $shift;
    }

    protected function getOrCreateDailyProduce(Shift $shift, Recipe $recipe, bool $dryRun): ?DailyProduce
    {
        $cacheKey = "shift_{$shift->id}_recipe_{$recipe->id}";
        
        if (isset($this->dailyProduceCache[$cacheKey])) {
            return DailyProduce::find($this->dailyProduceCache[$cacheKey]);
        }

        $dailyProduce = DailyProduce::query()
            ->where('shift_id', $shift->id)
            ->where('recipe_id', $recipe->id)
            ->first();

        if (! $dailyProduce && ! $dryRun) {
            $dailyProduce = DailyProduce::create([
                'shift_id' => $shift->id,
                'recipe_id' => $recipe->id,
                'produce_date' => $shift->shift_date,
                'shift_type' => $shift->shift_type,
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

        if ($dailyProduce) {
            $this->dailyProduceCache[$cacheKey] = $dailyProduce->id;
        }

        return $dailyProduce;
    }
}
