<?php

namespace App\Console\Commands\Concerns;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentCategory;
use App\Models\ProductType;
use App\Models\UnitOfMeasure;
use Illuminate\Support\Str;

trait ParsesProductionJson
{
    protected array $uomIdCache = [];

    protected function resolveJsonFiles(string $path): array
    {
        if (is_dir($path)) {
            $files = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.json');
        } else {
            $files = [$path];
        }

        $files = array_values(array_filter($files, fn ($file) => is_file($file)));
        sort($files);

        return $files;
    }

    protected function loadJsonRows(string $path): array
    {
        $rows = [];
        $files = $this->resolveJsonFiles($path);

        if (empty($files)) {
            $this->warn("No JSON files found at: {$path}");
            return $rows;
        }

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $decoded = json_decode($content, true);

            if (! is_array($decoded)) {
                $this->warn("Skipping invalid JSON file: {$file}");
                continue;
            }

            foreach ($decoded as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    protected function parseNameAndExternalId(?string $value): array
    {
        $value = trim((string) $value);
        $externalId = null;

        if (preg_match('/\((\d+)\)\s*$/', $value, $matches)) {
            $externalId = $matches[1];
            $value = trim(preg_replace('/\s*\(\d+\)\s*$/', '', $value));
        }

        return [$value, $externalId];
    }

    protected function normalizeKey(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return strtoupper($value);
    }

    protected function parseQuantityAndUom(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [0.0, null];
        }

        if (preg_match('/([0-9.,]+)\s*([A-Za-z%]+)$/', $value, $matches)) {
            $quantity = str_replace(',', '', $matches[1]);
            $uom = strtoupper($matches[2]);
            return [is_numeric($quantity) ? (float) $quantity : 0.0, $uom];
        }

        $parts = preg_split('/\s+/', $value);
        if (count($parts) >= 2) {
            $uom = strtoupper(array_pop($parts));
            $quantity = str_replace(',', '', $parts[0]);
            return [is_numeric($quantity) ? (float) $quantity : 0.0, $uom];
        }

        $quantity = str_replace(',', '', $value);
        return [is_numeric($quantity) ? (float) $quantity : 0.0, null];
    }

    protected function parsePercent(?string $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return 0.0;
        }

        return (float) $cleaned;
    }

    protected function parseMoney(?string $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', $value);
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return 0.0;
        }

        return (float) $cleaned;
    }

    protected function uomCodeFromToken(?string $token): ?string
    {
        if (! $token) {
            return null;
        }

        $token = strtoupper(trim($token));

        $map = [
            'G' => 'g',
            'KG' => 'kg',
            'ML' => 'ml',
            'L' => 'l',
            'CL' => 'cl',
            'PCS' => 'pcs',
            'PC' => 'pcs',
            'UNIT' => 'unit',
            'UNITS' => 'unit',
            'PORTION' => 'unit',
            'PK' => 'unit',
            'PKS' => 'unit',
            'CU' => 'cup',
            'CP' => 'cup',
            'SC' => 'scoop',
            'RO' => 'unit',
            'SPN' => 'unit',
        ];

        return $map[$token] ?? null;
    }

    protected function resolveUomId(?string $token): ?int
    {
        $code = $this->uomCodeFromToken($token);
        if (! $code) {
            return null;
        }

        if (! array_key_exists($code, $this->uomIdCache)) {
            $this->uomIdCache[$code] = UnitOfMeasure::where('code', $code)->value('id');
        }

        return $this->uomIdCache[$code] ?: null;
    }

    protected function resolveBranchId(?string $branchIdOption): ?string
    {
        if ($branchIdOption) {
            return $branchIdOption;
        }

        $branch = Branch::query()->first();

        return $branch?->id;
    }

    protected function loadMapOption(?string $option): array
    {
        if (! $option) {
            return [];
        }

        $payload = $option;
        if (is_file($option)) {
            $payload = file_get_contents($option) ?: '';
        }

        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            $this->warn('Invalid JSON mapping provided; ignoring.');
            return [];
        }

        $map = [];
        foreach ($decoded as $key => $value) {
            $map[$this->normalizeKey((string) $key)] = $value;
        }

        return $map;
    }

    protected function resolveDepartmentId(
        ?string $location,
        ?string $branchId,
        array $departmentMap,
        bool $createMissing,
        ?string $categoryId,
    ): ?int {
        $location = $this->normalizeKey($location);
        if ($location === '') {
            return null;
        }

        if (isset($departmentMap[$location])) {
            return (int) $departmentMap[$location];
        }

        $departmentQuery = Department::query()->whereRaw('UPPER(name) = ?', [$location]);
        if ($branchId) {
            $departmentQuery->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        }
        $department = $departmentQuery->first();

        if ($department) {
            return (int) $department->id;
        }

        if (! $createMissing) {
            return null;
        }

        $categoryId = $categoryId ?: DepartmentCategory::query()->value('id');
        if (! $categoryId) {
            $this->warn("Unable to create department for {$location}: no department_categories found.");
            return null;
        }

        $department = Department::create([
            'branch_id' => $branchId,
            'category_id' => $categoryId,
            'name' => $location,
            'description' => "{$location} (imported)",
        ]);

        $this->warn("Created department: {$department->name}");

        return (int) $department->id;
    }

    protected function resolveProductTypeId(
        ?string $location,
        int $departmentId,
        array $productTypeMap,
        bool $createMissing,
    ): ?int {
        $location = $this->normalizeKey($location);
        if ($location === '') {
            return null;
        }

        if (isset($productTypeMap[$location])) {
            return (int) $productTypeMap[$location];
        }

        $meta = $this->defaultProductTypeMeta($location);
        $productType = ProductType::query()
            ->where('name', $meta['name'])
            ->orWhere('code', $meta['code'])
            ->first();

        if ($productType) {
            return (int) $productType->id;
        }

        if (! $createMissing) {
            return null;
        }

        $code = $this->generateUniqueProductTypeCode($meta['code']);

        $productType = ProductType::create([
            'department_id' => $departmentId,
            'name' => $meta['name'],
            'code' => $code,
            'description' => "{$meta['name']} (imported)",
            'status' => 'active',
            'sort_order' => 0,
        ]);

        $this->warn("Created product type: {$productType->name}");

        return (int) $productType->id;
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

        $name = Str::title(strtolower($location ?: 'General'));
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

    protected function generateSku(string $name, string $modelClass): string
    {
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'item';
        }

        $sku = $base;
        $counter = 1;

        while ($modelClass::where('sku', $sku)->exists()) {
            $sku = $base . '_' . $counter;
            $counter++;
        }

        return $sku;
    }

    protected function classifyCategory(string $name): string
    {
        $name = strtolower($name);

        if (preg_match('/(machine|oven|freezer|refrigerator|mixer|blender|grinder|cooker|pot|pan|kettle|scale|thermometer|timer|dishwasher|dish|plate|cup|glass|spoon|fork|knife|cutlery|utensil|tool|brush|sponge|cleaner|sanitizer|soap)/i', $name)) {
            return 'equipment';
        }

        if (preg_match('/(box|bag|pack|wrap|container|tray|foil|film|paper|cup|plate|sleeve|nylon|sack|basket|bottle|jar|holder|case|cover|lid|seal|tape|label)/i', $name)) {
            return 'packaging';
        }

        if (preg_match('/(flour|sugar|salt|pepper|oil|butter|milk|egg|cheese|cream|yogurt|fruit|vegetable|meat|fish|seafood|grain|spice|herb|seasoning|powder|extract|syrup|sauce|jam|honey|chocolate|coffee|tea|water|juice|dairy|coconut|almond|walnut|peanut|cashew|pistachio|pecan|macadamia|coconut|oats|rice|corn|maize|potato|yam|onion|garlic|ginger|carrot|tomato|lettuce|spinach|kale|cabbage|broccoli|pepper|chili|mushroom|avocado|apple|banana|orange|lemon|lime|grape|strawberry|blueberry|raspberry|blackberry|cherry|plum|peach|apricot|pear|mango|pineapple|papaya|kiwi|fig|date|raisin|olive|cinnamon|vanilla|cumin|coriander|paprika|turmeric|oregano|basil|thyme|rosemary|parsley|cilantro|mint|soy sauce|fish sauce|oyster sauce|hoisin sauce|sriracha|worcestershire|ketchup|mayonnaise|vinegar|baking powder|baking soda|yeast|gelatin|agar|pectin|xanthan gum|lecithin|emulsifier|preservative)/i', $name)) {
            return 'raw_material';
        }

        return 'consumable';
    }
}
