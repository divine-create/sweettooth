<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ParsesProductionJson;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\UnitOfMeasure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductsFromProductionData extends Command
{
    use ParsesProductionJson;

    protected $signature = 'import:production-products-new
        {path? : Path to JSON file or directory (defaults to real_data/receips-sweetooth-main)}
        {--branch-id= : Branch UUID to assign products to}
        {--prices-path= : Path to SWEETTOOTH PRODUCT PRICES.xlsx for price import}
        {--dry-run : Parse only, do not write to database}';

    protected $description = 'Import products from production JSON files with optional price import from Excel';

    protected array $uomCache = [];
    protected array $departmentCache = [];
    protected array $productTypeCache = [];
    protected array $productCache = [];
    protected array $pricesFromFile = [];

    public function handle(): int
    {
        $path = $this->argument('path') ?: base_path('real_data/receips-sweetooth-main');
        $pricesPath = $this->option('prices-path') ?: base_path('real_data/SWEETTOOTH PRODUCT PRICES.xlsx');
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        if (! $branchId) {
            $this->error('No branch found. Provide --branch-id or create a branch first.');
            return 1;
        }

        // Load prices from Excel file if it exists
        if (file_exists($pricesPath)) {
            $this->loadPricesFromExcel($pricesPath);
            $this->info("Loaded " . count($this->pricesFromFile) . " prices from Excel file.");
        }

        $rows = $this->loadJsonRows($path);
        if (empty($rows)) {
            $this->warn('No rows to import.');
            return 0;
        }

        // Extract all unique products from all production records
        $products = $this->extractUniqueProducts($rows);
        
        $this->info("Found " . count($products) . " unique products to import.");

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $product) {
            $name = $product['name'];
            $externalId = $product['external_id'];
            $uomToken = $product['most_common_uom'];
            $avgCost = $product['avg_cost'];
            $location = $product['most_common_location'];

            $result = $this->findOrCreateProduct($name, $externalId, $branchId, $uomToken, $avgCost, $location, $dryRun);

            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Product import completed:");
        $this->info("  Created: {$created}");
        $this->info("  Updated: {$updated}");
        $this->info("  Skipped: {$skipped}");

        return 0;
    }

    protected function loadPricesFromExcel(string $path): void
    {
        try {
            $data = \Excel::toArray([], $path);
            
            if (empty($data)) {
                $this->warn("No data found in Excel file: {$path}");
                return;
            }

            // Assume first sheet contains the prices
            $sheet = reset($data);
            
            foreach ($sheet as $index => $row) {
                // Skip header row
                if ($index === 0) {
                    continue;
                }

                // Expected columns: Product Name, SKU/Code, Price, Cost, etc.
                // Adjust column indices based on actual Excel structure
                $productName = $row[0] ?? null;
                $productSku = $row[1] ?? null;
                $price = $this->cleanNumericValue($row[2] ?? 0);
                $cost = $this->cleanNumericValue($row[3] ?? 0);

                if ($productName) {
                    $key = $productSku ?: $this->normalizeKey($productName);
                    $this->pricesFromFile[$key] = [
                        'name' => $productName,
                        'sku' => $productSku,
                        'price' => $price,
                        'cost' => $cost > 0 ? $cost : null,
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->warn("Could not load prices from Excel: " . $e->getMessage());
        }
    }

    protected function cleanNumericValue($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove currency symbols, commas, spaces
            $cleaned = preg_replace('/[^0-9.]/', '', $value);
            return is_numeric($cleaned) ? (float) $cleaned : 0;
        }

        return 0;
    }

    protected function extractUniqueProducts(array $rows): array
    {
        $products = [];

        foreach ($rows as $row) {
            [$name, $externalId] = $this->parseNameAndExternalId($row['produced_product'] ?? '');
            if ($name === '') {
                continue;
            }

            [$producedQty, $uomToken] = $this->parseQuantityAndUom($row['quantity_produced'] ?? '');
            $totalCost = $this->parseMoney($row['total_cost'] ?? '');
            $unitCost = $producedQty > 0 ? ($totalCost / $producedQty) : 0;
            $location = $row['table_info']['location'] ?? '';

            $key = $externalId ?: $this->normalizeKey($name);

            if (! isset($products[$key])) {
                $products[$key] = [
                    'name' => $name,
                    'external_id' => $externalId,
                    'uom_tokens' => [],
                    'locations' => [],
                    'costs' => [],
                    'most_common_uom' => null,
                    'most_common_location' => null,
                    'avg_cost' => 0,
                ];
            }

            if ($uomToken) {
                $products[$key]['uom_tokens'][$uomToken] = 
                    ($products[$key]['uom_tokens'][$uomToken] ?? 0) + 1;
            }

            if ($location) {
                $locKey = $this->normalizeKey($location);
                $products[$key]['locations'][$locKey] = 
                    ($products[$key]['locations'][$locKey] ?? 0) + 1;
            }

            if ($unitCost > 0) {
                $products[$key]['costs'][] = $unitCost;
            }
        }

        // Calculate most common UOM, location, and average cost
        foreach ($products as &$product) {
            if (! empty($product['uom_tokens'])) {
                arsort($product['uom_tokens']);
                $product['most_common_uom'] = array_key_first($product['uom_tokens']);
            }

            if (! empty($product['locations'])) {
                arsort($product['locations']);
                $product['most_common_location'] = array_key_first($product['locations']);
            }

            if (! empty($product['costs'])) {
                $product['avg_cost'] = array_sum($product['costs']) / count($product['costs']);
            }
        }

        return $products;
    }

    protected function findOrCreateProduct(
        string $name,
        ?string $externalId,
        string $branchId,
        ?string $uomToken,
        float $avgCost,
        ?string $location,
        bool $dryRun
    ): string {
        $key = $externalId ?: $this->normalizeKey($name);
        
        // Check cache first
        if (isset($this->productCache[$key])) {
            return 'skipped';
        }

        // Try to find existing product - MUST filter by branch_id
        $query = Product::query()->where('branch_id', $branchId);
        if ($externalId) {
            $query->where('sku', $externalId);
        } else {
            $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        }
        
        $product = $query->first();

        $uomId = $uomToken ? $this->getUomIdByToken($uomToken) : null;
        if (! $uomId) {
            $uomId = UnitOfMeasure::where('code', 'pcs')->value('id');
        }

        // Get or create department and product type
        $departmentId = $this->getOrCreateDepartment($location, $branchId, $dryRun);
        $productTypeId = $this->getOrCreateProductType($location, $departmentId, $dryRun);

        // Check for price from Excel file
        $priceFromExcel = $this->pricesFromFile[$key]['price'] ?? null;
        $costFromExcel = $this->pricesFromFile[$key]['cost'] ?? null;

        if ($product) {
            // Update existing product if needed
            $needsUpdate = false;
            $updateData = [];

            if (! $product->uom_id && $uomId) {
                $updateData['uom_id'] = $uomId;
                $needsUpdate = true;
            }

            if (! $product->branch_id) {
                $updateData['branch_id'] = $branchId;
                $needsUpdate = true;
            }

            if ($departmentId && ! $product->sales_department_id) {
                $updateData['sales_department_id'] = $departmentId;
                $needsUpdate = true;
            }

            if ($productTypeId && ! $product->product_type_id) {
                $updateData['product_type_id'] = $productTypeId;
                $needsUpdate = true;
            }

            // Update cost if not set
            $newCost = $costFromExcel ?? ($avgCost > 0 ? round($avgCost, 4) : null);
            if ($newCost && (! $product->cost || $product->cost == 0)) {
                $updateData['cost'] = $newCost;
                $needsUpdate = true;
            }

            // Update price if available from Excel
            if ($priceFromExcel && (! $product->price || $product->price == 0)) {
                $updateData['price'] = round($priceFromExcel, 2);
                $needsUpdate = true;
            }

            if ($needsUpdate && ! $dryRun) {
                $product->update($updateData);
                $this->productCache[$key] = $product->id;
                return 'updated';
            }

            $this->productCache[$key] = $product->id;
            return 'skipped';
        }

        // Create new product
        if (! $dryRun) {
            $price = $priceFromExcel ?? 0;
            $cost = $costFromExcel ?? ($avgCost > 0 ? round($avgCost, 4) : 0);

            $product = Product::create([
                'name' => $name,
                'sku' => $externalId ?: $this->generateUniqueSku($name, 'PRD'),
                'branch_id' => $branchId,
                'product_type_id' => $productTypeId,
                'sales_department_id' => $departmentId,
                'price' => $price > 0 ? round($price, 2) : 0,
                'cost' => $cost > 0 ? round($cost, 4) : 0,
                'uom_id' => $uomId,
                'is_active' => true,
                'is_available' => true,
            ]);
            $this->productCache[$key] = $product->id;
            return 'created';
        }

        return 'created';
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

        $uomId = UnitOfMeasure::where('code', $code)->value('id');
        $this->uomCache[$token] = $uomId;

        return $uomId;
    }

    protected function getOrCreateDepartment(?string $location, string $branchId, bool $dryRun): ?int
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
        
        // Return cached value if already looked up
        if (array_key_exists($locKey, $this->departmentCache)) {
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
        
        // Cache the result (even if null to avoid repeated lookups)
        $this->departmentCache[$locKey] = $department?->id;
        
        if (! $department) {
            $this->warn("Department not found for location: {$location} (mapped to: {$mappedLocation}) in branch");
        }
        
        return $department?->id;
    }

    protected function getOrCreateProductType(?string $location, ?int $departmentId, bool $dryRun): ?int
    {
        if (! $location || ! $departmentId) {
            return null;
        }

        // Map location to product type
        $typeMap = [
            'HOT KITCHEN' => ['name' => 'Hot Kitchen Items', 'code' => 'HK'],
            'PASTRY' => ['name' => 'Pastry Items', 'code' => 'PS'],
            'GELATO' => ['name' => 'Gelato Items', 'code' => 'GL'],
            'CORNER STORE' => ['name' => 'Cornerstone Items', 'code' => 'CS'],
            'TILL' => ['name' => 'Till Items', 'code' => 'TL'],
            'CONCESSION' => ['name' => 'Concession Items', 'code' => 'CN'],
            'CORNERSTORE' => ['name' => 'Cornerstone Items', 'code' => 'CS'],
        ];
        
        $locUpper = strtoupper($location);
        $meta = $typeMap[$locUpper] ?? $this->defaultProductTypeMeta($location);
        
        $locKey = $this->normalizeKey($meta['code']);

        if (isset($this->productTypeCache[$locKey])) {
            return $this->productTypeCache[$locKey];
        }

        $productType = ProductType::withTrashed()
            ->where(function ($q) use ($meta) {
                $q->where('name', $meta['name'])
                  ->orWhere('code', $meta['code']);
            })
            ->first();

        if (! $productType && $departmentId) {
            $code = $this->generateUniqueProductTypeCode($meta['code']);

            if (! $dryRun) {
                $productType = ProductType::create([
                    'department_id' => $departmentId,
                    'name' => $meta['name'],
                    'code' => $code,
                    'description' => "{$meta['name']} (imported)",
                    'status' => 'active',
                    'sort_order' => 0,
                ]);
            }
        }

        if ($productType && $productType->trashed()) {
            $productType->restore();
        }

        $this->productTypeCache[$locKey] = $productType?->id;
        return $productType?->id;
    }

    protected function defaultProductTypeMeta(string $location): array
    {
        $location = $this->normalizeKey($location);
        $defaults = [
            'HOT KITCHEN' => ['name' => 'Hot Kitchen', 'code' => 'HK'],
            'PASTRY' => ['name' => 'Pastry', 'code' => 'PA'],
            'GELATO' => ['name' => 'Gelato', 'code' => 'GE'],
            'CORNER STORE' => ['name' => 'Corner Store', 'code' => 'CS'],
            'TILL' => ['name' => 'Till', 'code' => 'TL'],
            'CONCESSION' => ['name' => 'Concession', 'code' => 'CN'],
        ];

        if (isset($defaults[$location])) {
            return $defaults[$location];
        }

        $name = \Illuminate\Support\Str::title(strtolower($location ?: 'General'));
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $location));
        $code = $code !== '' ? substr($code, 0, 3) : 'GEN';

        return ['name' => $name, 'code' => $code];
    }

    protected function generateUniqueProductTypeCode(string $baseCode): string
    {
        $baseCode = strtoupper(substr($baseCode, 0, 10));
        $code = $baseCode;
        $counter = 1;

        while (ProductType::where('code', $code)->exists()) {
            $suffix = (string) $counter;
            $code = substr($baseCode, 0, max(1, 10 - strlen($suffix))) . $suffix;
            $counter++;
        }

        return $code;
    }

    protected function generateUniqueSku(string $name, string $prefix = 'PRD'): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 8));
        if ($base === '') {
            $base = 'PROD';
        }

        $sku = $prefix . '-' . $base;
        $counter = 1;

        while (Product::where('sku', $sku)->exists()) {
            $sku = $prefix . '-' . $base . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }
}
