<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\DailyProduce;
use App\Models\Item;
use App\Models\ItemRequest;
use App\Models\ItemRequestDetail;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductionRecord;
use App\Models\ProductionRequest;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Spatie\Permission\Models\Role;

class PortHarcourtRealDataSeeder extends Seeder
{
    private const BRANCH_CODE = 'PHC-002';
    private const BRANCH_NAME = 'SweetTooth Port Harcourt';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Port Harcourt branch and super admin...');

        DB::transaction(function (): void {
            $branch = $this->seedBranch();
            $categories = $this->seedCategories();
            $departments = $this->seedDepartments($branch, $categories);
            $this->ensureSuperAdminUser($branch, $departments);
        });

        $this->command->info('Port Harcourt branch seeding completed.');
    }

    /**
     * @return array<string, DepartmentCategory>
     */
    private function seedCategories(): array
    {
        $categories = [
            'sales' => DepartmentCategory::updateOrCreate(
                ['name' => 'Sales'],
                ['description' => 'Departments responsible for sales operations.']
            ),
            'production' => DepartmentCategory::updateOrCreate(
                ['name' => 'Production'],
                ['description' => 'Departments responsible for preparing products.']
            ),
            'support' => DepartmentCategory::updateOrCreate(
                ['name' => 'Support'],
                ['description' => 'Support departments for shared operations.']
            ),
        ];

        return $categories;
    }

    private function seedBranch(): Branch
    {
        return Branch::updateOrCreate(
            ['code' => self::BRANCH_CODE],
            [
                'name' => self::BRANCH_NAME,
                'location' => '78 Trans Amadi Industrial Layout',
                'phone' => '+234-803-456-7890',
                'email' => 'portharcourt@sweettooth.com',
                'description' => 'Port Harcourt main branch',
                'country' => 'Nigeria',
                'state' => 'Rivers',
                'city' => 'Port Harcourt',
                'postal_code' => '500001',
                'timezone' => 'Africa/Lagos',
                'is_active' => true,
            ]
        );
    }

    /**
     * @param  array<string, DepartmentCategory>  $categories
     * @return array<string, Department>
     */
    private function seedDepartments(Branch $branch, array $categories): array
    {
        $definitions = [
            'hot_kitchen' => [
                'name' => 'HOT KITCHEN',
                'category_id' => $categories['production']->id,
                'description' => 'Handles hot-kitchen production workflows.',
            ],
            'pastry' => [
                'name' => 'PASTRY',
                'category_id' => $categories['production']->id,
                'description' => 'Handles pastry production workflows.',
            ],
            'gelato' => [
                'name' => 'GELATO',
                'category_id' => $categories['production']->id,
                'description' => 'Handles gelato production workflows.',
            ],
            'cornerstone' => [
                'name' => 'CORNERSTONE',
                'category_id' => $categories['production']->id,
                'description' => 'Production-only corner-store preparation line.',
            ],
            'till_concession' => [
                'name' => 'Till Sales',
                'slug' => 'till_concession',
                'category_id' => $categories['sales']->id,
                'description' => 'Till sales operations for ice cream, pancakes, and related products.',
            ],
            'concession' => [
                'name' => 'Concession',
                'slug' => 'concession',
                'category_id' => $categories['sales']->id,
                'description' => 'Independent concession sales department for pastries and confectionery.',
            ],
            'corner_store' => [
                'name' => 'Corner Store',
                'slug' => 'corner_store',
                'category_id' => $categories['sales']->id,
                'description' => 'Corner store sales operations for drinks, tea, coffee, and related products.',
            ],
            'inventory' => [
                'name' => 'Inventory/Store',
                'category_id' => $categories['support']->id,
                'description' => 'Inventory and stock control.',
            ],
            'hr' => [
                'name' => 'HR',
                'category_id' => $categories['support']->id,
                'description' => 'Human resources support.',
            ],
        ];

        $departments = [];
        foreach ($definitions as $key => $definition) {
            $slug = (string) ($definition['slug'] ?? Str::slug($definition['name']));

            // Normalize legacy auto-slugged sales department records
            // (e.g. till-concession, corner-store) into canonical underscore slugs.
            $legacySlug = Str::slug($definition['name']);
            if (isset($definition['slug']) && $slug !== $legacySlug) {
                $canonicalExists = Department::query()
                    ->where('branch_id', $branch->id)
                    ->where('slug', $slug)
                    ->exists();

                if (! $canonicalExists) {
                    $legacyDepartment = Department::query()
                        ->where('branch_id', $branch->id)
                        ->where('slug', $legacySlug)
                        ->first();

                    if ($legacyDepartment !== null) {
                        $legacyDepartment->slug = $slug;
                        $legacyDepartment->save();
                    }
                }
            }

            $departments[$key] = Department::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'slug' => $slug,
                ],
                [
                    'category_id' => $definition['category_id'],
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ]
            );
        }

        return $departments;
    }

    /**
     * @param  array<string, Department>  $departments
     * @return array<string, ProductType>
     */
    private function seedProductTypes(array $departments): array
    {
        $definitions = [
            'hot_kitchen' => [
                'name' => 'Hot Kitchen Items',
                'code' => 'HK',
                'department_id' => $departments['hot_kitchen']->id,
                'description' => 'Products produced by Hot Kitchen.',
            ],
            'pastry' => [
                'name' => 'Pastry Items',
                'code' => 'PS',
                'department_id' => $departments['pastry']->id,
                'description' => 'Products produced by Pastry.',
            ],
            'gelato' => [
                'name' => 'Gelato Items',
                'code' => 'GL',
                'department_id' => $departments['gelato']->id,
                'description' => 'Products produced by Gelato.',
            ],
            'cornerstone' => [
                'name' => 'Cornerstone Items',
                'code' => 'CS',
                'department_id' => $departments['cornerstone']->id,
                'description' => 'Products produced by Cornerstone.',
            ],
        ];

        $productTypes = [];
        foreach ($definitions as $key => $definition) {
            $productType = ProductType::withTrashed()->firstOrNew(['code' => $definition['code']]);
            if ($productType->exists && $productType->trashed()) {
                $productType->restore();
            }

            $productType->fill([
                'department_id' => $definition['department_id'],
                'name' => $definition['name'],
                'description' => $definition['description'],
                'status' => 'active',
                'sort_order' => 1,
            ])->save();

            $productTypes[$key] = $productType;
        }

        return $productTypes;
    }

    /**
     * @return array<string, int>
     */
    private function buildUomMap(): array
    {
        return UnitOfMeasure::query()
            ->pluck('id', 'code')
            ->mapWithKeys(static fn ($id, $code) => [strtolower((string) $code) => (int) $id])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseHrUsers(string $filePath): array
    {
        if (! file_exists($filePath)) {
            $this->command->warn("HR workbook not found: {$filePath}");

            return [];
        }

        $sheet = IOFactory::load($filePath)->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headerRow = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $headerRow[$column] = $this->normalizeHeaderName((string) $sheet->getCellByColumnAndRow($column, 1)->getFormattedValue());
        }

        $nameColumn = $this->findColumn($headerRow, ['name', 'staff_name', 'employee_name', 'full_name']);
        $firstNameColumn = $this->findColumn($headerRow, ['first_name', 'firstname', 'first name']);
        $lastNameColumn = $this->findColumn($headerRow, ['last_name', 'lastname', 'surname', 'last name']);
        $resumptionColumn = $this->findColumn($headerRow, [
            'resumption_date',
            'resumption',
            'date_of_resumption',
            'date_joined',
            'employment_date',
            'hire_date',
        ]);
        $jobColumn = $this->findColumn($headerRow, [
            'job_description',
            'job_title',
            'designation',
            'position',
            'role',
        ]);
        $dobColumn = $this->findColumn($headerRow, ['dob', 'date_of_birth', 'birth_date']);
        $emailColumn = $this->findColumn($headerRow, ['email', 'email_address']);
        $addressColumn = $this->findColumn($headerRow, ['home_address', 'address', 'residential_address']);
        $phoneColumn = $this->findColumn($headerRow, ['phone_number', 'phone', 'mobile', 'mobile_number']);
        $emergencyColumn = $this->findColumn($headerRow, [
            'contact_in_case_of_emergency',
            'emergency_contact',
            'next_of_kin',
            'next_of_kin_phone',
        ]);
        $departmentColumn = $this->findColumn($headerRow, ['department', 'dept']);

        $records = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $name = '';
            if ($nameColumn !== null) {
                $name = trim((string) $sheet->getCellByColumnAndRow($nameColumn, $row)->getFormattedValue());
            }
            if ($name === '' && ($firstNameColumn !== null || $lastNameColumn !== null)) {
                $firstName = $firstNameColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($firstNameColumn, $row)->getFormattedValue())
                    : '';
                $lastName = $lastNameColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($lastNameColumn, $row)->getFormattedValue())
                    : '';
                $name = trim($firstName.' '.$lastName);
            }
            if ($name === '') {
                continue;
            }

            $records[] = [
                'name' => $name,
                'department' => $departmentColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($departmentColumn, $row)->getFormattedValue())
                    : '',
                'job_description' => $jobColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($jobColumn, $row)->getFormattedValue())
                    : '',
                'email' => $emailColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($emailColumn, $row)->getFormattedValue())
                    : '',
                'phone' => $phoneColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($phoneColumn, $row)->getFormattedValue())
                    : '',
                'address' => $addressColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($addressColumn, $row)->getFormattedValue())
                    : '',
                'dob' => $dobColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($dobColumn, $row)->getFormattedValue())
                    : '',
                'resumption_date' => $resumptionColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($resumptionColumn, $row)->getFormattedValue())
                    : '',
                'emergency_contact' => $emergencyColumn !== null
                    ? trim((string) $sheet->getCellByColumnAndRow($emergencyColumn, $row)->getFormattedValue())
                    : '',
            ];
        }

        return $records;
    }

    /**
     * @return array{products: array<int, array<string, mixed>>, recipes: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function parseHotKitchenRecipes(string $filePath): array
    {
        if (! file_exists($filePath)) {
            $this->command->warn("Hot Kitchen recipe workbook not found: {$filePath}");

            return [
                'products' => [],
                'recipes' => [],
                'items' => [],
            ];
        }

        $sheet = IOFactory::load($filePath)->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();

        $products = [];
        $recipes = [];
        $itemsMap = [];
        $currentRecipe = null;

        for ($row = 1; $row <= $highestRow; $row++) {
            $first = trim((string) $sheet->getCellByColumnAndRow(1, $row)->getFormattedValue());
            $second = trim((string) $sheet->getCellByColumnAndRow(2, $row)->getFormattedValue());
            $third = trim((string) $sheet->getCellByColumnAndRow(3, $row)->getFormattedValue());

            if ($first === '') {
                continue;
            }

            $upperFirst = strtoupper($first);
            if ($upperFirst === 'INGREDIENT' || Str::startsWith($upperFirst, 'WASTAGE')) {
                continue;
            }

            if (Str::startsWith($upperFirst, 'TOTAL OUTPUT QUANTITY:')) {
                if ($currentRecipe !== null) {
                    [$yieldQuantity, $yieldUom] = $this->extractQuantityAndUom($first);
                    if ($yieldQuantity !== null) {
                        $currentRecipe['yield_quantity'] = $yieldQuantity;
                    }
                    if ($yieldUom !== null) {
                        $currentRecipe['yield_uom'] = $yieldUom;
                    }

                    $priceText = $second !== '' ? $second : $third;
                    $currentRecipe['price'] = $this->toDecimal($priceText) ?? $currentRecipe['price'];
                    $recipes[] = $currentRecipe;

                    $products[] = [
                        'name' => $currentRecipe['name'],
                        'source' => $currentRecipe['source'],
                        'price' => $currentRecipe['price'],
                        'unit_weight' => null,
                        'uom_code' => $this->toUomCode($currentRecipe['yield_uom']),
                    ];
                }

                $currentRecipe = null;

                continue;
            }

            // Product title rows
            if ($second === '' && $third === '') {
                $name = $this->cleanName($first);
                if ($name === '') {
                    continue;
                }

                $currentRecipe = [
                    'name' => $name,
                    'source' => $this->inferSourceFromName($name),
                    'price' => 0.0,
                    'yield_quantity' => 1.0,
                    'yield_uom' => 'PCS',
                    'ingredients' => [],
                ];

                continue;
            }

            if ($currentRecipe === null) {
                continue;
            }

            $ingredientName = $this->cleanName($first);
            if ($ingredientName === '') {
                continue;
            }

            [$quantity, $uom] = $this->extractQuantityAndUom($second);
            $wastage = $this->toPercentage($third) ?? 0.0;

            $currentRecipe['ingredients'][] = [
                'name' => $ingredientName,
                'quantity' => $quantity ?? 0.0,
                'uom' => $uom ?? 'G',
                'wastage_percent' => $wastage,
            ];

            $itemKey = $this->normalizeNameKey($ingredientName);
            if (! isset($itemsMap[$itemKey])) {
                $itemsMap[$itemKey] = [
                    'name' => $ingredientName,
                    'uom_code' => $this->toUomCode($uom),
                ];
            }
        }

        return [
            'products' => $products,
            'recipes' => $recipes,
            'items' => array_values($itemsMap),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parsePriceWorkbookProducts(string $filePath): array
    {
        if (! file_exists($filePath)) {
            $this->command->warn("Product prices workbook not found: {$filePath}");

            return [];
        }

        $sheet = IOFactory::load($filePath)->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();

        $products = [];
        for ($row = 4; $row <= $highestRow; $row++) {
            $gelatoName = trim((string) $sheet->getCellByColumnAndRow(2, $row)->getFormattedValue());
            $gelatoWeight = trim((string) $sheet->getCellByColumnAndRow(3, $row)->getFormattedValue());
            $gelatoPrice = trim((string) $sheet->getCellByColumnAndRow(4, $row)->getFormattedValue());
            if ($gelatoName !== '') {
                $products[] = [
                    'name' => $this->cleanName($gelatoName),
                    'source' => 'gelato',
                    'price' => $this->toDecimal($gelatoPrice),
                    'unit_weight' => $this->toDecimal($gelatoWeight),
                    'uom_code' => 'g',
                ];
            }

            $pastryName = trim((string) $sheet->getCellByColumnAndRow(7, $row)->getFormattedValue());
            $pastryPrice = trim((string) $sheet->getCellByColumnAndRow(8, $row)->getFormattedValue());
            if ($pastryName !== '') {
                $products[] = [
                    'name' => $this->cleanName($pastryName),
                    'source' => 'pastry',
                    'price' => $this->toDecimal($pastryPrice),
                    'unit_weight' => null,
                    'uom_code' => 'pcs',
                ];
            }

            $tillName = trim((string) $sheet->getCellByColumnAndRow(11, $row)->getFormattedValue());
            $tillPrice = trim((string) $sheet->getCellByColumnAndRow(12, $row)->getFormattedValue());
            if ($tillName !== '') {
                $products[] = [
                    'name' => $this->cleanName($tillName),
                    'source' => 'till_concession',
                    'price' => $this->toDecimal($tillPrice),
                    'unit_weight' => null,
                    'uom_code' => 'pcs',
                ];
            }
        }

        return array_values(array_filter($products, static fn (array $entry) => $entry['name'] !== ''));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseProductionAnalysisProducts(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $sheetSourceMap = [
            'GELATO' => 'gelato',
            'PASTRY' => 'pastry',
            'CORNERSTORE.' => 'cornerstone',
            'CONCESSION' => 'concession',
            'HOT KITCHEN' => 'hot_kitchen',
        ];

        $products = [];
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= 260 && Coordinate::columnIndexFromString($columnAddress) <= 7;
            }
        });

        foreach ($sheetSourceMap as $sheetName => $source) {
            $reader->setLoadSheetsOnly([$sheetName]);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            for ($row = 3; $row <= 260; $row++) {
                $name = trim((string) $sheet->getCellByColumnAndRow(1, $row)->getFormattedValue());
                if ($name === '') {
                    continue;
                }

                if (in_array(strtoupper($name), ['PRODUCTS', 'RAW MATERIALS', 'COFFEE'], true)) {
                    continue;
                }

                $products[] = [
                    'name' => $this->cleanName($name),
                    'source' => $source,
                    'price' => null,
                    'cost_per_unit' => $this->toDecimal((string) $sheet->getCellByColumnAndRow(4, $row)->getFormattedValue()),
                    'unit_weight' => null,
                    'uom_code' => $this->toUomCode((string) $sheet->getCellByColumnAndRow(5, $row)->getFormattedValue()),
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        return array_values(array_filter($products, static fn (array $entry) => $entry['name'] !== ''));
    }

    /**
     * @param  array<int, array<string, mixed>>  $priceProducts
     * @param  array<int, array<string, mixed>>  $analysisProducts
     * @param  array<int, array<string, mixed>>  $recipeProducts
     * @return array<string, array<string, mixed>>
     */
    private function compileProducts(array $priceProducts, array $analysisProducts, array $recipeProducts): array
    {
        $compiled = [];

        foreach (array_merge($priceProducts, $analysisProducts, $recipeProducts) as $entry) {
            $name = (string) Arr::get($entry, 'name', '');
            if ($name === '') {
                continue;
            }

            $key = $this->normalizeNameKey($name);
            $source = (string) Arr::get($entry, 'source', 'hot_kitchen');
            $departmentKey = $this->sourceToDepartmentKey($source, $name);
            $salesDepartmentKeys = $this->sourceToSalesDepartmentKeys($source, $name);
            $sourcePriority = $this->sourcePriority($source);

            if (! isset($compiled[$key])) {
                $compiled[$key] = [
                    'name' => $name,
                    'source' => $source,
                    'source_priority' => $sourcePriority,
                    'department_key' => $departmentKey,
                    'sales_department_keys' => $salesDepartmentKeys,
                    'price' => Arr::get($entry, 'price'),
                    'cost_per_unit' => Arr::get($entry, 'cost_per_unit'),
                    'unit_weight' => Arr::get($entry, 'unit_weight'),
                    'uom_code' => Arr::get($entry, 'uom_code', 'pcs'),
                ];

                continue;
            }

            if ($sourcePriority > $compiled[$key]['source_priority']) {
                $compiled[$key]['source'] = $source;
                $compiled[$key]['source_priority'] = $sourcePriority;
                $compiled[$key]['department_key'] = $departmentKey;
            }

            $compiled[$key]['sales_department_keys'] = array_values(array_unique(array_merge(
                $compiled[$key]['sales_department_keys'],
                $salesDepartmentKeys
            )));

            if (($compiled[$key]['price'] === null || (float) $compiled[$key]['price'] <= 0) && Arr::get($entry, 'price') !== null) {
                $compiled[$key]['price'] = Arr::get($entry, 'price');
            }

            if (($compiled[$key]['cost_per_unit'] === null || (float) $compiled[$key]['cost_per_unit'] <= 0) && Arr::get($entry, 'cost_per_unit') !== null) {
                $compiled[$key]['cost_per_unit'] = Arr::get($entry, 'cost_per_unit');
            }

            if (($compiled[$key]['unit_weight'] === null || (float) $compiled[$key]['unit_weight'] <= 0) && Arr::get($entry, 'unit_weight') !== null) {
                $compiled[$key]['unit_weight'] = Arr::get($entry, 'unit_weight');
            }

            if (($compiled[$key]['uom_code'] === null || $compiled[$key]['uom_code'] === 'pcs') && Arr::get($entry, 'uom_code') !== null) {
                $compiled[$key]['uom_code'] = Arr::get($entry, 'uom_code');
            }
        }

        foreach ($compiled as $key => $entry) {
            unset($compiled[$key]['source_priority']);
            $compiled[$key]['price'] = (float) ($compiled[$key]['price'] ?? 0);
            $compiled[$key]['cost_per_unit'] = (float) ($compiled[$key]['cost_per_unit'] ?? 0);
        }

        return $compiled;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<string, Department>  $departments
     * @return array<int, User>
     */
    private function seedUsers(array $records, Branch $branch, array $departments): array
    {
        $createdUsers = [];
        $defaultPasswordHash = Hash::make('password');
        $counter = 1;

        foreach ($records as $record) {
            $name = trim((string) Arr::get($record, 'name', ''));
            if ($name === '') {
                continue;
            }

            $jobDescription = (string) Arr::get($record, 'job_description', '');
            $departmentName = (string) Arr::get($record, 'department', '');
            $department = $this->resolveDepartmentFromJob($jobDescription, $departments, $departmentName);
            $email = $this->sanitizeEmail((string) Arr::get($record, 'email', ''), $name, $counter);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $defaultPasswordHash,
                    'branch_id' => $branch->id,
                    'last_accessed_branch_id' => $branch->id,
                    'is_active' => true,
                    'employee_number' => sprintf('PHC-EMP-%04d', $counter),
                    'department_id' => $department?->id,
                    'phone' => $this->extractPhone((string) Arr::get($record, 'phone', '')),
                    'hire_date' => $this->normalizeDate((string) Arr::get($record, 'resumption_date', '')),
                    'date_of_birth' => $this->normalizeDate((string) Arr::get($record, 'dob', ''), 'Y-m-d'),
                    'address' => (string) Arr::get($record, 'address', ''),
                    'emergency_contact_name' => Str::limit((string) Arr::get($record, 'emergency_contact', ''), 250, ''),
                    'employment_status' => 'active',
                    'user_type' => 'employee',
                    'email_verified_at' => now(),
                ]
            );

            $roleName = $this->resolveRoleFromJob($jobDescription);
            if ($roleName !== null) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role !== null) {
                    $user->syncRoles([$role]);
                }
            }

            $createdUsers[] = $user;
            $counter++;
        }

        return $createdUsers;
    }

    /**
     * @param  array<int, array<string, mixed>>  $itemRecords
     * @param  array<string, int>  $uomMap
     * @return array<string, Item>
     */
    private function seedItems(array $itemRecords, Branch $branch, array $uomMap): array
    {
        $items = [];

        foreach ($itemRecords as $record) {
            $name = trim((string) Arr::get($record, 'name', ''));
            if ($name === '') {
                continue;
            }

            $normalizedKey = $this->normalizeNameKey($name);
            $sku = 'PHC-ITM-'.strtoupper(substr(sha1($normalizedKey), 0, 8));
            $uomCode = strtolower((string) Arr::get($record, 'uom_code', 'unit'));
            $uomId = $uomMap[$uomCode] ?? $uomMap['unit'] ?? $uomMap['pcs'] ?? null;

            $item = Item::updateOrCreate(
                ['sku' => $sku],
                [
                    'branch_id' => $branch->id,
                    'name' => $name,
                    'category' => $this->inferItemCategory($name),
                    'uom_id' => $uomId,
                    'reorder_level' => 10,
                    'max_stock_level' => 500,
                    'status' => 'active',
                ]
            );

            Stock::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'item_id' => $item->id,
                ],
                [
                    'quantity_available' => 500,
                    'quantity_reserved' => 0,
                    'quantity_damaged' => 0,
                    'average_cost' => 0,
                    'health_status' => 'good',
                ]
            );

            $items[$normalizedKey] = $item;
        }

        return $items;
    }

    /**
     * @param  array<string, array<string, mixed>>  $compiledProducts
     * @param  array<string, ProductType>  $productTypes
     * @param  array<string, int>  $uomMap
     * @param  array<string, Department>  $departments
     * @return array<string, Product>
     */
    private function seedProducts(array $compiledProducts, Branch $branch, array $productTypes, array $uomMap, array $departments): array
    {
        $products = [];

        foreach ($compiledProducts as $key => $entry) {
            $name = (string) Arr::get($entry, 'name', '');
            if ($name === '') {
                continue;
            }

            $departmentKey = (string) Arr::get($entry, 'department_key', 'hot_kitchen');
            $productType = $productTypes[$departmentKey] ?? $productTypes['hot_kitchen'];

            $uomCode = strtolower((string) Arr::get($entry, 'uom_code', 'pcs'));
            $uomId = $uomMap[$uomCode] ?? $uomMap['pcs'] ?? $uomMap['unit'] ?? null;
            $primarySalesDepartmentId = $this->resolvePrimarySalesDepartmentId(
                (array) Arr::get($entry, 'sales_department_keys', []),
                $departments
            );

            $sku = 'PHC-PRD-'.strtoupper(substr(sha1($key), 0, 8));

            $product = Product::withTrashed()->firstOrNew(['sku' => $sku]);
            if ($product->exists && $product->trashed()) {
                $product->restore();
            }

            $price = (float) Arr::get($entry, 'price', 0);
            $cost = (float) Arr::get($entry, 'cost_per_unit', 0);
            if ($price <= 0 && $cost > 0) {
                $price = round($cost * 1.2, 2);
            }

            $product->fill([
                'branch_id' => $branch->id,
                'name' => $name,
                'product_type_id' => $productType->id,
                'sales_department_id' => $primarySalesDepartmentId,
                'description' => 'Seeded from real-data workbook source: '.Arr::get($entry, 'source', 'unknown'),
                'price' => $price,
                'cost' => $cost > 0 ? $cost : null,
                'shelf_life_days' => 3,
                'uom_id' => $uomId,
                'unit_weight' => Arr::get($entry, 'unit_weight'),
                'is_active' => true,
                'is_available' => true,
            ])->save();

            $products[$key] = $product;
        }

        return $products;
    }

    /**
     * @param  array<string, array<string, mixed>>  $compiledProducts
     * @param  array<string, Product>  $products
     * @param  array<string, Department>  $departments
     */
    private function attachProductsToSalesDepartments(array $compiledProducts, array $products, array $departments): void
    {
        $sortOrder = [
            'till_concession' => 1,
            'concession' => 1,
            'corner_store' => 1,
        ];
        $managedSalesDepartmentIds = collect(['till_concession', 'concession', 'corner_store'])
            ->map(static fn (string $departmentKey): ?int => $departments[$departmentKey]->id ?? null)
            ->filter()
            ->values()
            ->all();

        foreach ($compiledProducts as $key => $entry) {
            $product = $products[$key] ?? null;
            if ($product === null) {
                continue;
            }

            $targetDepartmentIds = [];
            foreach ((array) Arr::get($entry, 'sales_department_keys', []) as $departmentKey) {
                $department = $departments[$departmentKey] ?? null;
                if ($department === null) {
                    continue;
                }

                $targetDepartmentIds[] = $department->id;

                DB::table('department_product')->updateOrInsert(
                    [
                        'department_id' => $department->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'is_available' => true,
                        'department_price' => $product->price,
                        'sort_order' => $sortOrder[$departmentKey] ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $sortOrder[$departmentKey] = ($sortOrder[$departmentKey] ?? 1) + 1;
            }

            if ($managedSalesDepartmentIds !== []) {
                $cleanupQuery = DB::table('department_product')
                    ->where('product_id', $product->id)
                    ->whereIn('department_id', $managedSalesDepartmentIds);

                if ($targetDepartmentIds !== []) {
                    $cleanupQuery->whereNotIn('department_id', array_values(array_unique($targetDepartmentIds)));
                }

                $cleanupQuery->delete();
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipes
     * @param  array<string, Product>  $products
     * @param  array<string, Item>  $items
     * @param  array<string, Department>  $departments
     * @param  array<string, int>  $uomMap
     * @param  array<int, User>  $users
     */
    private function seedRecipes(
        array $recipes,
        array $products,
        array $items,
        Branch $branch,
        array $departments,
        array $uomMap,
        array $users
    ): array {
        $createdRecipes = [];
        $creator = $users[0] ?? User::query()->where('branch_id', $branch->id)->first();
        if ($creator === null) {
            return [];
        }

        foreach ($recipes as $recipeData) {
            $name = (string) Arr::get($recipeData, 'name', '');
            if ($name === '') {
                continue;
            }

            $productKey = $this->normalizeNameKey($name);
            $product = $products[$productKey] ?? null;
            if ($product === null) {
                continue;
            }

            $source = (string) Arr::get($recipeData, 'source', 'hot_kitchen');
            $departmentKey = $this->sourceToDepartmentKey($source, $name);
            $department = $departments[$departmentKey] ?? $departments['hot_kitchen'];

            $recipeSku = 'PHC-RCP-'.strtoupper(substr(sha1($product->sku.'-recipe'), 0, 10));
            $yieldUom = $this->toUomCode((string) Arr::get($recipeData, 'yield_uom', 'pcs'));

            $recipe = Recipe::updateOrCreate(
                ['sku' => $recipeSku],
                [
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_type_id' => $product->product_type_id,
                    'cost_per_unit' => (float) ($product->cost ?? 0),
                    'uom_id' => $uomMap[$yieldUom] ?? $uomMap['pcs'] ?? $uomMap['unit'] ?? null,
                    'yield_quantity' => (float) Arr::get($recipeData, 'yield_quantity', 1),
                    'preparation_time' => null,
                    'instructions' => null,
                    'status' => 'active',
                    'created_by_id' => $creator->id,
                    'created_by_type' => User::class,
                ]
            );

            $createdRecipes[$this->normalizeNameKey($product->name)] = $recipe;

            $ingredientSortOrder = 1;
            foreach ((array) Arr::get($recipeData, 'ingredients', []) as $ingredientData) {
                $ingredientName = (string) Arr::get($ingredientData, 'name', '');
                if ($ingredientName === '') {
                    continue;
                }

                $itemKey = $this->normalizeNameKey($ingredientName);
                $item = $items[$itemKey] ?? null;
                if ($item === null) {
                    continue;
                }

                $uomCode = $this->toUomCode((string) Arr::get($ingredientData, 'uom', 'g'));
                $uomId = $uomMap[$uomCode] ?? $item->uom_id ?? $uomMap['unit'] ?? null;

                RecipeIngredient::updateOrCreate(
                    [
                        'recipe_id' => $recipe->id,
                        'item_id' => $item->id,
                    ],
                    [
                        'quantity' => (float) Arr::get($ingredientData, 'quantity', 0),
                        'uom_id' => $uomId,
                        'cost_per_unit' => 0,
                        'waste_percentage' => (float) Arr::get($ingredientData, 'wastage_percent', 0),
                        'sort_order' => $ingredientSortOrder++,
                        'notes' => null,
                        'preparation_notes' => null,
                    ]
                );
            }
        }

        return $createdRecipes;
    }

    /**
     * @param  array<string, Department>  $departments
     */
    private function ensureSuperAdminUser(Branch $branch, array $departments): void
    {
        $department = $departments['hr'] ?? null;

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@sweettooth.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'employee_number' => 'ADMIN-001',
                'is_active' => true,
                'branch_id' => $branch->id,
                'last_accessed_branch_id' => $branch->id,
                'department_id' => $department?->id,
                'employment_status' => 'active',
                'user_type' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        $managingDirectorRole = Role::where('name', 'Managing Director')->where('guard_name', 'web')->first();

        $roles = [];
        if ($superAdminRole !== null) {
            $roles[] = $superAdminRole;
        }
        if ($managingDirectorRole !== null) {
            $roles[] = $managingDirectorRole;
        }

        if (! empty($roles)) {
            $superAdmin->syncRoles($roles);
        }
    }

    private function resolveDepartmentFromJob(string $jobDescription, array $departments, ?string $departmentName = null): ?Department
    {
        $departmentName = $departmentName !== null ? trim($departmentName) : '';
        if ($departmentName !== '') {
            $department = $this->resolveDepartmentFromName($departmentName, $departments);
            if ($department !== null) {
                return $department;
            }
        }

        $job = Str::lower($jobDescription);

        if (Str::contains($job, ['inventory', 'store'])) {
            return $departments['inventory'] ?? null;
        }

        if (Str::contains($job, ['hr', 'human resource'])) {
            return $departments['hr'] ?? null;
        }

        if (Str::contains($job, ['gelato', 'ice cream'])) {
            return $departments['gelato'] ?? null;
        }

        if (Str::contains($job, ['pastry', 'baker', 'cake'])) {
            return $departments['pastry'] ?? null;
        }

        if (Str::contains($job, ['corner', 'barista', 'coffee'])) {
            return $departments['corner_store'] ?? null;
        }

        if (Str::contains($job, ['concession'])) {
            return $departments['concession'] ?? $departments['till_concession'] ?? null;
        }

        if (Str::contains($job, ['chef', 'kitchen', 'cook', 'production'])) {
            return $departments['hot_kitchen'] ?? null;
        }

        return $departments['till_concession'] ?? null;
    }

    private function resolveDepartmentFromName(string $departmentName, array $departments): ?Department
    {
        $name = Str::lower(trim($departmentName));

        if ($name === '') {
            return null;
        }

        if (Str::contains($name, ['inventory', 'store'])) {
            return $departments['inventory'] ?? null;
        }

        if (Str::contains($name, ['hr', 'human'])) {
            return $departments['hr'] ?? null;
        }

        if (Str::contains($name, ['gelato', 'ice cream'])) {
            return $departments['gelato'] ?? null;
        }

        if (Str::contains($name, ['pastry', 'baker', 'cake'])) {
            return $departments['pastry'] ?? null;
        }

        if (Str::contains($name, ['corner', 'barista', 'coffee'])) {
            return $departments['corner_store'] ?? null;
        }

        if (Str::contains($name, ['concession'])) {
            return $departments['concession'] ?? $departments['till_concession'] ?? null;
        }

        if (Str::contains($name, ['till'])) {
            return $departments['till_concession'] ?? null;
        }

        if (Str::contains($name, ['hot kitchen', 'kitchen', 'production'])) {
            return $departments['hot_kitchen'] ?? null;
        }

        return null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function discoverWorkbooks(string $dataDir): array
    {
        $files = array_merge(
            glob($dataDir.'/*.xlsx') ?: [],
            glob($dataDir.'/new/*.xlsx') ?: []
        );

        $files = array_values(array_unique($files));

        $workbooks = [
            'hr' => [],
            'hot_kitchen' => [],
            'product_prices' => [],
            'production_analysis' => [],
        ];

        foreach ($files as $path) {
            $name = Str::lower(basename($path));

            if (Str::contains($name, ['hr', 'staff'])) {
                $workbooks['hr'][] = $path;
                continue;
            }

            if (Str::contains($name, ['hot kitchen recipes'])) {
                $workbooks['hot_kitchen'][] = $path;
                continue;
            }

            if (Str::contains($name, ['product prices'])) {
                $workbooks['product_prices'][] = $path;
                continue;
            }

            if (Str::contains($name, ['production_analysis', 'production - sweettooth', 'production - sweettooth cofectionaries'])) {
                $workbooks['production_analysis'][] = $path;
                continue;
            }
        }

        foreach ($workbooks as $key => $paths) {
            $workbooks[$key] = array_values(array_unique($paths));
        }

        return $workbooks;
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, array<string, mixed>>
     */
    private function parseHrWorkbooks(array $paths): array
    {
        $records = [];
        $seen = [];

        foreach ($paths as $path) {
            foreach ($this->parseHrUsers($path) as $row) {
                $email = Str::lower(trim((string) Arr::get($row, 'email', '')));
                $name = Str::lower(trim((string) Arr::get($row, 'name', '')));
                $key = $email !== '' ? $email : $name;
                if ($key === '') {
                    continue;
                }
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $records[] = $row;
            }
        }

        return $records;
    }

    /**
     * @return array<int, string>
     */
    private function discoverRecipeJson(string $dataDir): array
    {
        return array_values(array_unique(
            glob($dataDir.'/receips-sweetooth-main/*.json') ?: []
        ));
    }

    /**
     * @param  array<int, string>  $paths
     * @return array{products: array<int, array<string, mixed>>, recipes: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function parseRecipeJsonFiles(array $paths): array
    {
        $products = [];
        $recipes = [];
        $itemsMap = [];
        $productions = [];

        foreach ($paths as $path) {
            if (! file_exists($path)) {
                continue;
            }

            $payload = json_decode((string) file_get_contents($path), true);
            if (! is_array($payload)) {
                continue;
            }

            foreach ($payload as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $productName = $this->cleanName((string) Arr::get($entry, 'produced_product', ''));
                if ($productName === '') {
                    continue;
                }

                $source = $this->inferSourceFromLocation((string) Arr::get($entry, 'table_info.location', ''));
                [$yieldQty, $yieldUom] = $this->extractQuantityAndUom((string) Arr::get($entry, 'quantity_produced', ''));

                $recipe = [
                    'name' => $productName,
                    'source' => $source,
                    'price' => $this->toDecimal((string) Arr::get($entry, 'total_cost', '')) ?? 0.0,
                    'yield_quantity' => $yieldQty ?? 1.0,
                    'yield_uom' => $yieldUom ?? 'PCS',
                    'ingredients' => [],
                ];

                foreach ((array) Arr::get($entry, 'ingredients', []) as $ingredientEntry) {
                    if (! is_array($ingredientEntry)) {
                        continue;
                    }

                    $ingredientName = $this->cleanName((string) Arr::get($ingredientEntry, 'ingredient', ''));
                    if ($ingredientName === '') {
                        continue;
                    }

                    [$qty, $uom] = $this->extractQuantityAndUom((string) Arr::get($ingredientEntry, 'input_qty', ''));
                    $wastage = $this->toPercentage((string) Arr::get($ingredientEntry, 'wastage_percent', '')) ?? 0.0;

                    $recipe['ingredients'][] = [
                        'name' => $ingredientName,
                        'quantity' => $qty ?? 0.0,
                        'uom' => $uom ?? 'G',
                        'wastage_percent' => $wastage,
                    ];

                    $itemKey = $this->normalizeNameKey($ingredientName);
                    if (! isset($itemsMap[$itemKey])) {
                        $itemsMap[$itemKey] = [
                            'name' => $ingredientName,
                            'uom_code' => $this->toUomCode($uom),
                        ];
                    }
                }

                $products[] = [
                    'name' => $productName,
                    'source' => $source,
                    'price' => 0.0,
                    'unit_weight' => null,
                    'uom_code' => $this->toUomCode($yieldUom),
                ];

                $recipes[] = $recipe;

                $productions[] = [
                    'reference_no' => (string) Arr::get($entry, 'reference_no', ''),
                    'production_date' => (string) Arr::get($entry, 'production_date', ''),
                    'date_time' => (string) Arr::get($entry, 'table_info.date_time', ''),
                    'location' => (string) Arr::get($entry, 'table_info.location', ''),
                    'product_name' => $productName,
                    'quantity_produced' => (string) Arr::get($entry, 'quantity_produced', ''),
                    'total_cost' => (string) Arr::get($entry, 'total_cost', ''),
                    'ingredients' => (array) Arr::get($entry, 'ingredients', []),
                ];
            }
        }

        return [
            'products' => $products,
            'recipes' => $recipes,
            'items' => array_values($itemsMap),
            'productions' => $productions,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $productions
     * @param  array<string, Department>  $departments
     * @param  array<string, Recipe>  $recipes
     * @param  array<string, Item>  $items
     * @param  array<string, int>  $uomMap
     * @param  array<int, User>  $users
     */
    private function seedProductionFromJson(
        array $productions,
        Branch $branch,
        array $departments,
        array $recipes,
        array $items,
        array $uomMap,
        array $users
    ): void {
        if ($productions === []) {
            return;
        }

        $creator = $users[0] ?? User::query()->where('branch_id', $branch->id)->first();
        if ($creator === null) {
            return;
        }

        foreach ($productions as $entry) {
            $productName = $this->cleanName((string) Arr::get($entry, 'product_name', ''));
            if ($productName === '') {
                continue;
            }

            $dateTime = $this->parseProductionDateTime($entry);
            if ($dateTime === null) {
                continue;
            }

            $shiftType = $this->deriveShiftType($dateTime);
            $source = $this->inferSourceFromLocation((string) Arr::get($entry, 'location', ''));
            $departmentKey = $this->sourceToDepartmentKey($source, $productName);
            $department = $departments[$departmentKey] ?? $departments['hot_kitchen'];

            $shift = $this->getOrCreateShift($branch, $department, $creator, $dateTime, $shiftType);

            $productKey = $this->normalizeNameKey($productName);
            $recipe = $recipes[$productKey] ?? null;
            if ($recipe === null) {
                $recipe = Recipe::query()
                    ->where('branch_id', $branch->id)
                    ->whereHas('product', function ($q) use ($productName) {
                        $q->where('name', $productName);
                    })
                    ->first();
            }

            if ($recipe === null) {
                continue;
            }

            [$producedQty] = $this->extractQuantityAndUom((string) Arr::get($entry, 'quantity_produced', ''));
            $producedQty = $producedQty ?? 0.0;

            $referenceNo = trim((string) Arr::get($entry, 'reference_no', ''));
            if ($referenceNo === '') {
                $referenceNo = 'PROD-'.Str::upper(Str::random(6));
            }

            $requestNumber = 'PRD-'.$referenceNo;

            $itemRequest = ItemRequest::updateOrCreate(
                ['request_number' => $requestNumber],
                [
                    'branch_id' => $branch->id,
                    'department_id' => $department->id,
                    'requested_by_id' => $creator->id,
                    'requested_by_type' => User::class,
                    'approved_by_id' => $creator->id,
                    'approved_by_type' => User::class,
                    'dispatched_by_id' => $creator->id,
                    'dispatched_by_type' => User::class,
                    'request_date' => $dateTime->toDateString(),
                    'shift' => $shiftType,
                    'status' => 'completed',
                    'approved_at' => $dateTime->toDateTimeString(),
                    'dispatched_at' => $dateTime->toDateTimeString(),
                    'notes' => 'Imported from JSON production data.',
                ]
            );

            foreach ((array) Arr::get($entry, 'ingredients', []) as $ingredientEntry) {
                $ingredientName = $this->cleanName((string) Arr::get($ingredientEntry, 'ingredient', ''));
                if ($ingredientName === '') {
                    continue;
                }

                $itemKey = $this->normalizeNameKey($ingredientName);
                $item = $items[$itemKey] ?? null;
                if ($item === null) {
                    $item = $this->createFallbackItem($ingredientName, $branch, $uomMap);
                    if ($item === null) {
                        continue;
                    }
                }

                [$qty, $uom] = $this->extractQuantityAndUom((string) Arr::get($ingredientEntry, 'input_qty', ''));
                $qty = $qty ?? 0.0;
                $uomCode = $this->toUomCode($uom);
                $uomId = $uomMap[$uomCode] ?? $item->uom_id ?? null;

                ItemRequestDetail::updateOrCreate(
                    [
                        'request_id' => $itemRequest->id,
                        'item_id' => $item->id,
                    ],
                    [
                        'requires_request' => false,
                        'quantity_requested' => $qty,
                        'quantity_approved' => $qty,
                        'quantity_dispatched' => $qty,
                        'uom_id' => $uomId,
                        'notes' => 'Imported from JSON production data.',
                    ]
                );
            }

            $productionRequest = ProductionRequest::updateOrCreate(
                [
                    'shift_id' => $shift->id,
                    'item_request_id' => $itemRequest->id,
                    'recipe_id' => $recipe->id,
                ],
                [
                    'planned_production_quantity' => $producedQty,
                    'notes' => 'Imported from JSON production data.',
                ]
            );

            $dailyProduce = DailyProduce::updateOrCreate(
                [
                    'shift_id' => $shift->id,
                    'recipe_id' => $recipe->id,
                    'production_request_id' => $productionRequest->id,
                ],
                [
                    'produce_date' => $dateTime->toDateString(),
                    'shift_type' => $shiftType,
                    'opening_quantity' => 0,
                    'requested_quantity' => $producedQty,
                    'produced_quantity' => $producedQty,
                    'batches_produced_this_shift' => 1,
                    'batches_remaining' => 0,
                    'fulfillment_status' => 'completed',
                    'sent_out_quantity' => 0,
                    'order_quantity' => 0,
                    'callback_quantity' => 0,
                    'closing_quantity' => $producedQty,
                    'expected_closing' => $producedQty,
                    'variance' => 0,
                    'status' => 'completed',
                    'notes' => 'Imported from JSON production data.',
                ]
            );

            ProductionRecord::updateOrCreate(
                [
                    'daily_produce_id' => $dailyProduce->id,
                    'recipe_id' => $recipe->id,
                    'batch_number' => $referenceNo,
                ],
                [
                    'produced_by_id' => $creator->id,
                    'produced_by_type' => User::class,
                    'quantity_produced' => $producedQty,
                    'quantity_approved' => $producedQty,
                    'quantity_rejected' => 0,
                    'quantity_sent_out' => 0,
                    'quantity_for_order' => 0,
                    'quantity_remaining' => $producedQty,
                    'production_time' => $dateTime->toDateTimeString(),
                    'quality_status' => 'good',
                    'dispatch_status' => 'available',
                    'notes' => 'Imported from JSON production data.',
                ]
            );
        }
    }

    private function parseProductionDateTime(array $entry): ?Carbon
    {
        $rawDateTime = trim((string) Arr::get($entry, 'date_time', ''));
        if ($rawDateTime !== '') {
            $parsed = Carbon::createFromFormat('m/d/Y h:i A', $rawDateTime);
            if ($parsed !== false) {
                return $parsed;
            }

            try {
                return Carbon::parse($rawDateTime);
            } catch (\Throwable) {
                // fall through
            }
        }

        $rawDate = trim((string) Arr::get($entry, 'production_date', ''));
        if ($rawDate !== '') {
            $parsed = Carbon::createFromFormat('m/d/Y', $rawDate);
            if ($parsed !== false) {
                return $parsed->setTime(9, 0);
            }

            try {
                return Carbon::parse($rawDate)->setTime(9, 0);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function deriveShiftType(Carbon $dateTime): string
    {
        $hour = (int) $dateTime->format('H');

        return $hour < 14 ? 'morning' : 'afternoon';
    }

    private function getOrCreateShift(
        Branch $branch,
        Department $department,
        User $creator,
        Carbon $dateTime,
        string $shiftType
    ): Shift {
        $shiftDate = $dateTime->toDateString();

        $existing = Shift::query()
            ->where('branch_id', $branch->id)
            ->where('department_id', $department->id)
            ->where('shift_date', $shiftDate)
            ->where('shift_type', $shiftType)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $shiftNumber = Shift::generateShiftNumber(
            $department->slug ?? (string) $department->id,
            $creator->id,
            $dateTime,
            $shiftType
        );

        return Shift::create([
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'employee_id' => $creator->id,
            'shift_number' => $shiftNumber,
            'shift_date' => $shiftDate,
            'shift_type' => $shiftType,
            'clock_in' => $dateTime->toDateTimeString(),
            'clock_out' => $dateTime->toDateTimeString(),
            'status' => 'closed',
            'notes' => 'Imported from JSON production data.',
        ]);
    }

    private function createFallbackItem(string $name, Branch $branch, array $uomMap): ?Item
    {
        $normalizedKey = $this->normalizeNameKey($name);
        $sku = 'PHC-ITM-'.strtoupper(substr(sha1($normalizedKey), 0, 8));
        $uomId = $uomMap['unit'] ?? $uomMap['pcs'] ?? null;

        $item = Item::updateOrCreate(
            ['sku' => $sku],
            [
                'branch_id' => $branch->id,
                'name' => $name,
                'category' => $this->inferItemCategory($name),
                'uom_id' => $uomId,
                'reorder_level' => 10,
                'max_stock_level' => 500,
                'status' => 'active',
            ]
        );

        Stock::updateOrCreate(
            [
                'branch_id' => $branch->id,
                'item_id' => $item->id,
            ],
            [
                'quantity_available' => 500,
                'quantity_reserved' => 0,
                'quantity_damaged' => 0,
                'average_cost' => 0,
                'health_status' => 'good',
            ]
        );

        return $item;
    }

    private function inferSourceFromLocation(string $location): string
    {
        $value = Str::upper(trim($location));

        if ($value === '') {
            return 'hot_kitchen';
        }

        if (Str::contains($value, ['GELATO'])) {
            return 'gelato';
        }

        if (Str::contains($value, ['PASTRY'])) {
            return 'pastry';
        }

        if (Str::contains($value, ['CORNER'])) {
            return 'cornerstone';
        }

        if (Str::contains($value, ['CONCESSION'])) {
            return 'concession';
        }

        return 'hot_kitchen';
    }

    private function resolveRoleFromJob(string $jobDescription): ?string
    {
        $job = Str::lower($jobDescription);

        if (Str::contains($job, ['managing director', 'md'])) {
            return 'Managing Director';
        }

        if (Str::contains($job, ['admin'])) {
            return 'Admin';
        }

        if (Str::contains($job, ['cost accountant'])) {
            return 'Cost Accountant';
        }

        if (Str::contains($job, ['accountant'])) {
            return 'Accountant';
        }

        if (Str::contains($job, ['hr manager', 'human resource manager'])) {
            return 'HR Manager';
        }

        if (Str::contains($job, ['hr', 'human resource'])) {
            return 'HR Officer';
        }

        if (Str::contains($job, ['floor manager'])) {
            return 'Floor Manager';
        }

        if (Str::contains($job, ['assistant shop floor manager'])) {
            return 'Assistant Shop Floor Manager';
        }

        if (Str::contains($job, ['production manager'])) {
            return 'Production Manager';
        }

        if (Str::contains($job, ['sales manager'])) {
            return 'Sales Manager';
        }

        if (Str::contains($job, ['inventory manager'])) {
            return 'Inventory Manager';
        }

        if (Str::contains($job, ['inventory team lead'])) {
            return 'Inventory Team Lead';
        }

        if (Str::contains($job, ['procurement'])) {
            return 'Procurement Officer';
        }

        if (Str::contains($job, ['store keeper', 'storekeeper'])) {
            return 'Store Keeper';
        }

        if (Str::contains($job, ['head chef'])) {
            return 'Head Chef';
        }

        if (Str::contains($job, ['hot kitchen'])) {
            return 'Hot Kitchen Chef';
        }

        if (Str::contains($job, ['pastry'])) {
            return 'Pastry Chef';
        }

        if (Str::contains($job, ['gelato'])) {
            return 'Gelato Chef';
        }

        if (Str::contains($job, ['kitchen assistant supervisor'])) {
            return 'Kitchen Assistant Supervisor';
        }

        if (Str::contains($job, ['kitchen assistant'])) {
            return 'Kitchen Assistant';
        }

        if (Str::contains($job, ['data processor'])) {
            return 'Data Processor';
        }

        if (Str::contains($job, ['till supervisor'])) {
            return 'Till Supervisor';
        }

        if (Str::contains($job, ['cornerstore supervisor', 'corner store supervisor'])) {
            return 'Cornerstore Supervisor';
        }

        if (Str::contains($job, ['consession supervisor', 'concession supervisor'])) {
            return 'Consession Supervisor';
        }

        if (Str::contains($job, ['barista trainer', 'trainer'])) {
            return 'Coffee Barista Trainer';
        }

        if (Str::contains($job, ['barista'])) {
            return 'Coffee Barista';
        }

        if (Str::contains($job, ['cashier'])) {
            return 'Cashier';
        }

        if (Str::contains($job, ['wait staff', 'waiter', 'waitress'])) {
            return 'Wait Staff';
        }

        if (Str::contains($job, ['lobby host supervisor'])) {
            return 'Lobby Host Supervisor';
        }

        if (Str::contains($job, ['lobby host'])) {
            return 'Lobby Host';
        }

        if (Str::contains($job, ['consession', 'concession'])) {
            return 'Consession Attendant';
        }

        if (Str::contains($job, ['facility officer'])) {
            return 'Facility Officer';
        }

        if (Str::contains($job, ['cleaner', 'cleaners supervisor'])) {
            return 'Cleaners Supervisor';
        }

        if (Str::contains($job, ['chief security officer'])) {
            return 'Chief Security Officer';
        }

        if (Str::contains($job, ['security'])) {
            return 'Security Officer';
        }

        if (Str::contains($job, ['social media'])) {
            return 'Social Media Manager';
        }

        if (Str::contains($job, ['driver'])) {
            return 'Driver';
        }

        if (Str::contains($job, ['inventory', 'store'])) {
            return 'Inventory Staff';
        }

        if (Str::contains($job, ['chef', 'kitchen', 'pastry', 'gelato', 'production'])) {
            return 'Production Staff';
        }

        if (Str::contains($job, ['sales', 'floor'])) {
            return 'Sales Staff';
        }

        return 'Sales Staff';
    }

    private function sanitizeEmail(string $rawEmail, string $name, int $counter): string
    {
        $email = Str::lower(trim($rawEmail));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        if ($email !== '' && ! Str::contains($email, '@')) {
            $candidate = $email.'@sweettooth.local';
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        $slug = Str::slug($name, '.');

        return $slug.'.'.$counter.'@sweettooth.local';
    }

    private function extractPhone(string $rawPhone): ?string
    {
        $digits = preg_replace('/[^0-9+]/', '', $rawPhone);

        return $digits !== '' ? Str::limit($digits, 25, '') : null;
    }

    private function normalizeDate(string $rawDate, string $format = 'Y-m-d H:i:s'): ?string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return null;
        }

        $timestamp = strtotime($rawDate);
        if ($timestamp === false) {
            return null;
        }

        return date($format, $timestamp);
    }

    private function inferItemCategory(string $name): string
    {
        $upper = Str::upper($name);

        if (Str::contains($upper, ['BOX', 'CUP', 'CONTAINER', 'WRAP', 'BAG', 'PACKAGING'])) {
            return 'packaging';
        }

        if (Str::contains($upper, ['DETERGENT', 'TOWEL', 'SOAP', 'TISSUE', 'CLEANER'])) {
            return 'consumable';
        }

        if (Str::contains($upper, ['MIXER', 'OVEN', 'FREEZER', 'BOWL', 'KNIFE', 'MACHINE'])) {
            return 'equipment';
        }

        return 'raw_material';
    }

    private function sourceToDepartmentKey(string $source, string $name): string
    {
        $upperName = Str::upper($name);
        if (Str::contains($upperName, ['CORNERSTORE', 'CORNER STORE'])) {
            return 'cornerstone';
        }
        if ($source === 'cornerstore' || $source === 'corner_store' || $source === 'cornerstone' || $this->isCornerStoreProductName($upperName)) {
            return 'cornerstone';
        }
        if ($source === 'pastry' || $this->isPastryProductName($upperName)) {
            return 'pastry';
        }

        return match ($source) {
            'gelato' => 'gelato',
            'hot_kitchen' => 'hot_kitchen',
            'till_concession', 'concession' => 'hot_kitchen',
            default => 'hot_kitchen',
        };
    }

    /**
     * @return array<int, string>
     */
    private function sourceToSalesDepartmentKeys(string $source, string $name): array
    {
        $upperName = Str::upper($name);
        if ($source === 'cornerstone' || $source === 'corner_store' || $this->isCornerStoreProductName($upperName)) {
            return ['corner_store'];
        }
        if ($source === 'pastry' || $this->isPastryProductName($upperName)) {
            return ['concession'];
        }

        return ['till_concession'];
    }

    private function sourcePriority(string $source): int
    {
        return match ($source) {
            'cornerstone', 'corner_store' => 5,
            'hot_kitchen', 'gelato', 'pastry' => 4,
            'till_concession', 'concession' => 3,
            default => 1,
        };
    }

    /**
     * @param  array<int, string>  $salesDepartmentKeys
     * @param  array<string, Department>  $departments
     */
    private function resolvePrimarySalesDepartmentId(array $salesDepartmentKeys, array $departments): ?int
    {
        foreach ($salesDepartmentKeys as $salesDepartmentKey) {
            $department = $departments[$salesDepartmentKey] ?? null;
            if ($department !== null) {
                return $department->id;
            }
        }

        return null;
    }

    private function isPastryProductName(string $upperName): bool
    {
        if ($this->matchesKeyword($upperName, ['PANCAKE', 'WAFFLE', 'CREPE'])) {
            return false;
        }

        if ($this->matchesKeyword($upperName, ['CAKE'])) {
            return true;
        }

        return $this->matchesKeyword($upperName, [
            'PASTRY',
            'CROISSANT',
            'DONUT',
            'DOUGHNUT',
            'MUFFIN',
            'BROWNIE',
            'COOKIE',
            'BISCUIT',
            'CUPCAKE',
            'SCONE',
            'ECLAIR',
            'TART',
            'PIE',
            'CHIN CHIN',
        ]);
    }

    private function isCornerStoreProductName(string $upperName): bool
    {
        return $this->matchesKeyword($upperName, [
            'CORNERSTORE',
            'CORNER STORE',
            'COFFEE',
            'TEA',
            'LATTE',
            'CAPPUCCINO',
            'ESPRESSO',
            'MOCHA',
            'AMERICANO',
            'MACCHIATO',
            'FRAPPE',
            'SMOOTHIE',
            'MILKSHAKE',
            'SHAKE',
            'JUICE',
            'SODA',
            'SOFT DRINK',
            'WATER',
            'LEMONADE',
            'HOT CHOCOLATE',
            'ICED TEA',
            'BEVERAGE',
            'DRINK',
        ]);
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function matchesKeyword(string $value, array $keywords): bool
    {
        $escapedKeywords = array_map(
            static fn (string $keyword): string => preg_quote($keyword, '/'),
            $keywords
        );

        $pattern = '/\b(?:'.implode('|', $escapedKeywords).')\b/u';

        return preg_match($pattern, $value) === 1;
    }

    private function inferSourceFromName(string $name): string
    {
        $upperName = Str::upper($name);

        if (Str::contains($upperName, ['CORNERSTORE', 'CORNER STORE'])) {
            return 'cornerstone';
        }

        return 'hot_kitchen';
    }

    private function normalizeHeaderName(string $value): string
    {
        return Str::snake(Str::of($value)->trim()->replaceMatches('/\s+/', ' ')->toString());
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $candidates
     */
    private function findColumn(array $headers, array $candidates): ?int
    {
        foreach ($headers as $column => $header) {
            if (in_array($header, $candidates, true)) {
                return $column;
            }
        }

        return null;
    }

    private function cleanName(string $name): string
    {
        $name = str_replace("\xc2\xa0", ' ', $name);
        $name = preg_replace('/\([^)]*\)/', '', $name);
        $name = preg_replace('/\s+/', ' ', (string) $name);

        return trim((string) $name);
    }

    private function normalizeNameKey(string $name): string
    {
        $normalized = Str::upper($this->cleanName($name));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim((string) $normalized);
    }

    /**
     * @return array{0: float|null, 1: string|null}
     */
    private function extractQuantityAndUom(string $value): array
    {
        $value = str_replace("\xc2\xa0", ' ', strtoupper(trim($value)));
        if ($value === '') {
            return [null, null];
        }

        if (preg_match('/([0-9.,]+)\s*([A-Z]+)/', $value, $matches) !== 1) {
            return [$this->toDecimal($value), null];
        }

        return [
            $this->toDecimal($matches[1]),
            $matches[2] ?? null,
        ];
    }

    private function toDecimal(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim($value);
        $cleaned = str_ireplace(['PRICE', ':', 'N', '#'], '', $cleaned);
        $cleaned = str_replace([',', ' '], '', $cleaned);
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }

    private function toPercentage(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $cleaned = str_replace('%', '', trim($value));
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }

    private function toUomCode(?string $rawUom): string
    {
        $uom = Str::upper(trim((string) $rawUom));

        return match ($uom) {
            'G', 'GRAM', 'GRAMS' => 'g',
            'KG', 'KG.' => 'kg',
            'ML' => 'ml',
            'L', 'LTR', 'LITRE', 'LITER', 'LITERS' => 'l',
            'PCS', 'PIECE', 'PIECES', 'PORTION', 'PACK', 'CUP', 'CU' => 'pcs',
            'UNIT', 'UNITS' => 'unit',
            default => 'unit',
        };
    }
}
