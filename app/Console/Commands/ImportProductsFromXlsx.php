<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\UnitOfMeasure;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportProductsFromXlsx extends Command
{
    protected $signature = 'products:import
                            {--file=products.xlsx : Path to the xlsx file (relative to project root or absolute)}
                            {--dry-run : Preview what would be imported without writing to the database}';

    protected $description = 'Import products from an xlsx file into the database';

    // Normalise Location column values to exact department names in the DB
    private array $locationMap = [
        'HOT KITCHEN'  => 'HOT KITCHEN',
        'HOT KITCHE'   => 'HOT KITCHEN',
        'PASTRY'       => 'PASTRY',
        'GELATO'       => 'GELATO',
        'CORNER STORE' => 'CORNERSTONE',
        'CORNESTOR'    => 'CORNERSTONE',
        'CORNERSTONE'  => 'CORNERSTONE',
        'TIL'          => 'Till Sales',
        'TILL'         => 'Till Sales',
        'CONCESSION'   => 'Concession',
    ];

    // Derive product type code from the normalised department name
    private array $deptToTypeCode = [
        'HOT KITCHEN' => 'HK',
        'PASTRY'      => 'PS',
        'GELATO'      => 'GL',
        'CORNERSTONE' => 'CS',
        'Till Sales'  => 'CS',
        'Concession'  => 'CS',
    ];

    // Normalise SASales Point column values to exact department names in the DB
    private array $salesMap = [
        'CORNERSTORE'  => 'Corner Store',
        'TIL'          => 'Till Sales',
        'CONCESSION'   => 'Concession',
        'HOT KITCHEN'  => 'HOT KITCHEN',
        'HOT'          => 'HOT KITCHEN',
        'PASTRY'       => 'PASTRY',
        'RAW MATERIAL' => null,
    ];

    // Map Excel UOM codes to system UOM codes
    private array $uomCodeMap = [
        'G'       => 'g',
        'KG'      => 'kg',
        'ML'      => 'ml',
        'PCS'     => 'pcs',
        'SC'      => 'scoop',
        'SCP'     => 'scoop',
        'CU'      => 'cup_serving',
        'PORTION' => 'portion',
        'CP'      => 'cp',
        'TB'      => 'tb',
        'PK'      => 'pk',
        'PKS'     => 'pk',
    ];

    // New UOMs to create if they don't exist yet
    private array $newUomDefinitions = [
        'portion' => ['name' => 'Portion',      'symbol' => 'portion', 'category' => 'serving'],
        'cp'      => ['name' => 'Coffee Cup',   'symbol' => 'cp',      'category' => 'serving'],
        'tb'      => ['name' => 'Tea Bag',      'symbol' => 'tb',      'category' => 'serving'],
        'pk'      => ['name' => 'Pack',         'symbol' => 'pk',      'category' => 'count'],
    ];

    public function handle(): int
    {
        $file = $this->option('file');
        $dryRun = $this->option('dry-run');

        if (! str_starts_with($file, '/')) {
            $file = base_path($file);
        }

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info($dryRun ? '[DRY RUN] Reading: '.$file : 'Reading: '.$file);

        $branch = Branch::first();
        if (! $branch) {
            $this->error('No branch found in the database.');
            return self::FAILURE;
        }

        // Load reference data
        $departments  = Department::all()->keyBy('name');
        $productTypes = ProductType::all()->keyBy('code');
        $uomMap       = $this->buildUomMap($dryRun);

        $spreadsheet = IOFactory::load($file);
        $sheet       = $spreadsheet->getSheetByName('All Products');

        if (! $sheet) {
            $this->error("Sheet 'All Products' not found in the file.");
            return self::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, true);

        $created      = 0;
        $skipped      = 0;
        $unresolved   = 0;
        $rowNumber    = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            if ($rowNumber === 1) {
                continue; // header
            }

            $name       = trim((string) ($row['A'] ?? ''));
            $locationRaw = trim((string) ($row['B'] ?? ''));
            $uomRaw     = strtoupper(trim((string) ($row['C'] ?? '')));
            $salesRaw   = strtoupper(trim((string) ($row['D'] ?? '')));

            if ($name === '') {
                continue;
            }

            // Resolve all departments from Location column (may have pipe-separated values)
            $locationParts    = array_map('trim', explode('|', $locationRaw));
            $resolvedDepts    = [];
            $hasUnresolved    = false;

            foreach ($locationParts as $part) {
                $normalisedName = $this->locationMap[strtoupper($part)] ?? null;

                if ($normalisedName === null) {
                    $this->warn("  Row {$rowNumber}: unknown location '{$part}' for product '{$name}'");
                    $hasUnresolved = true;
                    continue;
                }

                $dept = $departments->get($normalisedName);
                if (! $dept) {
                    $this->warn("  Row {$rowNumber}: department '{$normalisedName}' not found in DB for product '{$name}'");
                    $hasUnresolved = true;
                    continue;
                }

                $resolvedDepts[] = $dept;
            }

            if (empty($resolvedDepts)) {
                $unresolved++;
                continue;
            }

            $primaryDept = $resolvedDepts[0];

            // Product type from primary department
            $typeCode    = $this->deptToTypeCode[$primaryDept->name] ?? 'CS';
            $productType = $productTypes->get($typeCode);

            if (! $productType) {
                $this->warn("  Row {$rowNumber}: product type code '{$typeCode}' not found, skipping '{$name}'");
                $unresolved++;
                continue;
            }

            // UOM
            $uomId = $uomMap[strtoupper($uomRaw)] ?? $uomMap['PCS'];

            // Sales department
            $salesDeptId = null;
            if ($salesRaw !== '') {
                $salesDeptName = $this->salesMap[$salesRaw] ?? null;
                if ($salesDeptName !== null) {
                    $salesDept   = $departments->get($salesDeptName);
                    $salesDeptId = $salesDept?->id;
                }
            }

            // SKU — deterministic hash of the product name
            $sku = 'IMP-'.strtoupper(substr(sha1(strtolower($name)), 0, 8));

            if ($dryRun) {
                $this->line(sprintf(
                    "  [dry] %-50s | dept: %-12s | type: %s | uom_id: %s | sales_dept_id: %s",
                    $name,
                    $primaryDept->name,
                    $typeCode,
                    $uomId,
                    $salesDeptId ?? 'null'
                ));
                $created++;
                continue;
            }

            // Check for existing by name too, in case SKU was generated differently before
            $existing = Product::withTrashed()->where('sku', $sku)->first()
                ?? Product::withTrashed()->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $skipped++;
            } else {
                $product = new Product();
                $product->fill([
                    'name'               => $name,
                    'sku'                => $sku,
                    'branch_id'          => $branch->id,
                    'product_type_id'    => $productType->id,
                    'department_id'      => $primaryDept->id,
                    'sales_department_id'=> $salesDeptId,
                    'uom_id'             => $uomId,
                    'price'              => 0,
                    'shelf_life_days'    => 0,
                    'is_active'          => true,
                    'is_available'       => true,
                ]);
                $product->save();

                // Link all departments via pivot
                $deptIds = collect($resolvedDepts)->pluck('id')->toArray();
                $product->departments()->syncWithoutDetaching($deptIds);

                $created++;
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("[DRY RUN] Would create: {$created} products");
            $this->info("[DRY RUN] Unresolvable rows: {$unresolved}");
        } else {
            $this->info("Created:     {$created}");
            $this->info("Skipped:     {$skipped} (already exist)");
            $this->info("Unresolved:  {$unresolved} (unknown department — see warnings above)");
        }

        return self::SUCCESS;
    }

    private function buildUomMap(bool $dryRun): array
    {
        $uomCreated = 0;
        $map        = [];

        // Ensure all UOMs referenced by the spreadsheet exist
        $allNeededCodes = array_unique(array_values($this->uomCodeMap));

        foreach ($allNeededCodes as $code) {
            $definition = $this->newUomDefinitions[$code] ?? null;

            $uom = UnitOfMeasure::where('code', $code)->first();

            if (! $uom) {
                if ($definition && ! $dryRun) {
                    $uom = UnitOfMeasure::create([
                        'code'       => $code,
                        'name'       => $definition['name'],
                        'symbol'     => $definition['symbol'],
                        'category'   => $definition['category'],
                        'is_active'  => true,
                        'sort_order' => 99,
                    ]);
                    $uomCreated++;
                    $this->line("  Created UOM: {$code} ({$definition['name']})");
                } elseif ($dryRun && $definition) {
                    $this->line("  [dry] Would create UOM: {$code} ({$definition['name']})");
                } else {
                    $this->warn("  UOM '{$code}' not found and no definition provided — will fall back to 'pcs'");
                }
            }

            if ($uom) {
                $map[$code] = $uom->id;
            }
        }

        // Build the Excel-code → uom_id map
        $result = [];
        foreach ($this->uomCodeMap as $excelCode => $systemCode) {
            $result[$excelCode] = $map[$systemCode] ?? null;
        }

        // Fallback: if pcs id is missing from map, add it
        if (! isset($result['PCS'])) {
            $pcs = UnitOfMeasure::where('code', 'pcs')->first();
            if ($pcs) {
                $result['PCS'] = $pcs->id;
            }
        }

        if (! $dryRun) {
            $this->info("UOMs created: {$uomCreated}");
        }

        return $result;
    }
}
