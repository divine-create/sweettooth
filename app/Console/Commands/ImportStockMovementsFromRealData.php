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

class ImportStockMovementsFromRealData extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-stock-movements-real
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign movements to}
        {--create-departments : Create departments if missing}
        {--department-category-id= : Category ID for auto-created departments}
        {--start-date= : Only import movements from this date onwards (YYYY-MM-DD)}
        {--end-date= : Only import movements up to this date (YYYY-MM-DD)}
        {--use-approved : Use approved quantity instead of produced quantity}
        {--batch-size= : Number of records to process per batch (default: 100)}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Create stock movements for ingredient usage based on production JSON data';

    protected array $departmentCache = [];
    protected array $itemCache = [];
    protected array $stockCache = [];
    protected array $uomCache = [];
    protected array $movementCache = [];

    public function handle(): int
    {
        $path = $this->argument('path') ?: base_path('real_data/receips-sweetooth-main');
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id or create a branch first.');
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

        $this->info("Processing " . count($filteredRows) . " production records for stock movements" .
            ($startDate ? " from {$startDate->toDateString()}" : "") .
            ($endDate ? " to {$endDate->toDateString()}" : "") . ".");

        $conversionService = app(UomConversionService::class);
        $created = 0;
        $skipped = 0;
        $errors = 0;
        $useApproved = (bool) $this->option('use-approved');

        $bar = $this->output->createProgressBar(count($filteredRows));
        $bar->start();

        foreach ($filteredRows as $row) {
            $result = $this->importStockMovementsForRow($row, $branchId, $conversionService, $useApproved, $dryRun);
            
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

        $this->info("Stock movements import completed:");
        $this->info("  Movements created: {$created}");
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
        $dateTimeRaw = $row['table_info']['date_time'] ?? null;
        if ($dateTimeRaw) {
            $parsed = Carbon::createFromFormat('m/d/Y h:i A', $dateTimeRaw);
            if ($parsed) {
                return $parsed;
            }
        }

        $productionDateRaw = $row['production_date'] ?? null;
        if ($productionDateRaw) {
            $parsed = Carbon::createFromFormat('m/d/Y', $productionDateRaw);
            if ($parsed) {
                return $parsed->setTime(12, 0);
            }
        }

        return Carbon::now();
    }

    protected function importStockMovementsForRow(
        array $row,
        string $branchId,
        UomConversionService $conversionService,
        bool $useApproved,
        bool $dryRun
    ): string {
        $referenceNo = trim((string) ($row['reference_no'] ?? ''));
        
        if ($referenceNo === '') {
            return 'skipped';
        }

        // Parse production date and location
        $productionTime = $this->parseProductionDate($row);
        $location = $row['table_info']['location'] ?? '';
        $departmentId = $this->getOrCreateDepartment($location, $branchId, $dryRun);

        if (! $departmentId) {
            $this->warn("Skipping {$referenceNo}: no department for location '{$location}'.");
            return 'error';
        }

        $shiftType = $productionTime->hour < 14 ? 'morning' : 'afternoon';

        // Find the recipe by product name - MUST filter by branch_id
        [$productName, $productExternalId] = $this->parseNameAndExternalId($row['produced_product'] ?? '');
        $recipe = $this->findRecipe($productName, $productExternalId, $branchId);

        if (! $recipe) {
            // If no recipe found, try to create movements directly from ingredients in the row
            return $this->createMovementsFromRowIngredients(
                $row, $branchId, $departmentId, $shiftType, $productionTime, 
                $conversionService, $dryRun
            );
        }

        // Calculate batch factor based on produced quantity
        [$producedQty] = $this->parseQuantityAndUom($row['quantity_produced'] ?? '');
        $yieldQty = (float) ($recipe->yield_quantity ?: 1);
        $batchFactor = $yieldQty > 0 ? ($producedQty / $yieldQty) : $producedQty;

        $movementsCreated = 0;

        // Load recipe ingredients
        $recipe->loadMissing(['ingredients.item']);

        foreach ($recipe->ingredients as $recipeIngredient) {
            $item = $recipeIngredient->item;
            if (! $item) {
                continue;
            }

            $movementResult = $this->createStockMovement(
                $item,
                $recipeIngredient->getActualQuantityNeeded() * $batchFactor,
                $recipeIngredient->uom_id,
                $branchId,
                $departmentId,
                $shiftType,
                $productionTime,
                $referenceNo,
                $recipe->product_name,
                $conversionService,
                $dryRun
            );

            if ($movementResult === 'created') {
                $movementsCreated++;
            }
        }

        return $movementsCreated > 0 ? 'created' : 'skipped';
    }

    protected function createMovementsFromRowIngredients(
        array $row,
        string $branchId,
        int $departmentId,
        string $shiftType,
        Carbon $productionTime,
        UomConversionService $conversionService,
        bool $dryRun
    ): string {
        $referenceNo = $row['reference_no'] ?? '';
        $productName = $row['produced_product'] ?? 'Unknown';
        $movementsCreated = 0;

        foreach ($row['ingredients'] ?? [] as $ingredient) {
            [$ingredientName, $ingredientExternalId] = $this->parseNameAndExternalId($ingredient['ingredient'] ?? '');
            if ($ingredientName === '') {
                continue;
            }

            [$inputQty, $ingredientUomToken] = $this->parseQuantityAndUom($ingredient['input_qty'] ?? '');
            if ($inputQty <= 0) {
                continue;
            }

            $item = $this->findItem($ingredientName, $ingredientExternalId, $branchId);
            if (! $item) {
                continue;
            }

            $ingredientUomId = $ingredientUomToken ? $this->getUomIdByToken($ingredientUomToken) : $item->uom_id;

            $movementResult = $this->createStockMovement(
                $item,
                $inputQty,
                $ingredientUomId,
                $branchId,
                $departmentId,
                $shiftType,
                $productionTime,
                $referenceNo,
                $productName,
                $conversionService,
                $dryRun
            );

            if ($movementResult === 'created') {
                $movementsCreated++;
            }
        }

        return $movementsCreated > 0 ? 'created' : 'skipped';
    }

    protected function createStockMovement(
        Item $item,
        float $quantity,
        ?int $fromUomId,
        string $branchId,
        int $departmentId,
        string $shiftType,
        Carbon $productionTime,
        string $referenceNo,
        string $productName,
        UomConversionService $conversionService,
        bool $dryRun
    ): string {
        // Convert quantity to item's base UOM if needed
        $quantityInItemUom = $quantity;
        $toUomId = $item->uom_id;

        if ($fromUomId && $toUomId && (int) $fromUomId !== (int) $toUomId) {
            $converted = $conversionService->tryConvert(
                $quantity,
                $fromUomId,
                $toUomId,
                ['branch_id' => $branchId, 'item_id' => $item->id]
            );

            if ($converted !== null) {
                $quantityInItemUom = $converted;
            }
        }

        if ($quantityInItemUom <= 0) {
            return 'skipped';
        }

        // Get or create stock record
        $stock = $this->getOrCreateStock($item, $branchId, $dryRun);
        if (! $stock) {
            return 'error';
        }

        // Check if movement already exists
        $existingMovement = StockMovement::query()
            ->where('reference_type', ProductionRecord::class)
            ->where('reference_id', 0) // We don't have a production_record_id when creating from raw data
            ->where('stock_id', $stock->id)
            ->where('movement_date', $productionTime)
            ->where('notes', 'like', "%{$referenceNo}%")
            ->first();

        if ($existingMovement) {
            return 'skipped';
        }

        // Create stock movement
        if (! $dryRun) {
            $before = (float) $stock->quantity_available;
            $after = $before - $quantityInItemUom;

            $stock->quantity_available = $after;
            $stock->save();

            StockMovement::create([
                'stock_id' => $stock->id,
                'branch_id' => $branchId,
                'type' => 'out',
                'quantity' => $quantityInItemUom,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => ProductionRecord::class,
                'reference_id' => null,
                'moved_by_type' => User::class,
                'moved_by_id' => null,
                'movement_date' => $productionTime,
                'shift' => $shiftType,
                'department_id' => $departmentId,
                'unit_cost' => $item->unit_price ?? 0,
                'cost_impact' => ($item->unit_price ?? 0) * $quantityInItemUom,
                'notes' => "Imported consumption for {$productName} (Ref: {$referenceNo})",
            ]);
        }

        return 'created';
    }

    protected function findRecipe(string $productName, ?string $productExternalId, string $branchId): ?Recipe
    {
        // MUST filter by branch_id to get the correct recipe for this branch
        $query = Recipe::query()->where('branch_id', $branchId);
        
        if ($productExternalId) {
            $query->whereHas('product', function ($q) use ($productExternalId) {
                $q->where('sku', $productExternalId);
            });
        } else {
            $query->whereRaw('LOWER(product_name) = ?', [strtolower($productName)]);
        }

        return $query->first();
    }

    protected function findItem(string $name, ?string $externalId, string $branchId): ?Item
    {
        $key = $externalId ?: $this->normalizeKey($name);
        
        if (isset($this->itemCache[$key])) {
            return Item::find($this->itemCache[$key]);
        }

        $query = Item::query()->where('branch_id', $branchId);
        
        if ($externalId) {
            $query->where('sku', $externalId);
        } else {
            $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        }

        $item = $query->first();
        
        if ($item) {
            $this->itemCache[$key] = $item->id;
        }

        return $item;
    }

    protected function getOrCreateStock(Item $item, string $branchId, bool $dryRun): ?Stock
    {
        $cacheKey = "{$branchId}_{$item->id}";
        
        if (isset($this->stockCache[$cacheKey])) {
            return Stock::find($this->stockCache[$cacheKey]);
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

        $this->stockCache[$cacheKey] = $stock->id;

        return $stock;
    }

    protected function getOrCreateDepartment(string $location, string $branchId, bool $dryRun): ?int
    {
        if (! $location) {
            return null;
        }

        // Map location names from production data to actual department names
        $locationMap = [
            'HOT KITCHEN' => 'HOT KITCHEN',
            'PASTRY' => 'PASTRY',
            'GELATO' => 'GELATO',
            'CORNER STORE' => 'Corner Store',
            'TILL' => 'Till Sales',
            'CONCESSION' => 'Concession',
            'TILL CONCESSION' => 'Till Sales',
            'CORNERSTORE' => 'Corner Store',
        ];

        $locUpper = strtoupper($location);
        $mappedLocation = $locationMap[$locUpper] ?? $location;
        $locKey = $this->normalizeKey($mappedLocation);
        
        if (isset($this->departmentCache[$locKey])) {
            return $this->departmentCache[$locKey];
        }

        // MUST filter by branch_id to get the correct department for this branch
        $department = Department::where('branch_id', $branchId)
            ->whereRaw('UPPER(name) = ?', [$mappedLocation])
            ->first();

        // If not found, try global departments (no branch_id)
        if (! $department) {
            $department = Department::whereNull('branch_id')
                ->whereRaw('UPPER(name) = ?', [$mappedLocation])
                ->first();
        }

        if (! $department && $this->option('create-departments')) {
            $categoryId = $this->option('department-category-id') ?: \App\Models\DepartmentCategory::value('id');
            
            if ($categoryId && ! $dryRun) {
                $department = Department::create([
                    'branch_id' => $branchId,
                    'category_id' => $categoryId,
                    'name' => $mappedLocation,
                    'description' => "{$mappedLocation} (imported)",
                ]);
            }
        }

        $this->departmentCache[$locKey] = $department?->id;
        return $department?->id;
    }

    protected function getUomIdByToken(string $token): ?int
    {
        if (isset($this->uomCache[$token])) {
            return $this->uomCache[$token];
        }

        $code = $this->uomCodeFromToken($token);
        if (! $code) {
            $code = 'pcs';
        }

        $uomId = \App\Models\UnitOfMeasure::where('code', $code)->value('id');
        $this->uomCache[$token] = $uomId;

        return $uomId;
    }
}
