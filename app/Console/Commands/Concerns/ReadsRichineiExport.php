<?php

namespace App\Console\Commands\Concerns;

use App\Models\Branch;
use App\Models\Department;
use App\Models\UnitOfMeasure;

/**
 * Shared helpers for importing the Richinei (UltimatePOS) master-data export
 * produced by data-migration/extract_richinei.py into Sweettooth.
 *
 * The export is clean structured JSON (we control its shape), so these helpers
 * are intentionally simple compared to the production-history parser
 * (ParsesProductionJson). Source records are keyed by the UltimatePOS product id,
 * which Sweettooth stores as the `sku` — this is the idempotency key throughout.
 */
trait ReadsRichineiExport
{
    protected array $uomIdCache = [];

    /** Default location of the extractor output. */
    protected function exportPath(): string
    {
        return base_path('data-migration/out');
    }

    /** Load one export file (e.g. "suppliers") as an array of rows. */
    protected function loadExport(string $name): array
    {
        $file = rtrim($this->exportPath(), '/')."/{$name}.json";

        if (! is_file($file)) {
            $this->error("Export file not found: {$file}");
            $this->line('Run: python3 data-migration/extract_richinei.py');

            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function resolveBranchId(?string $branchIdOption): ?string
    {
        return $branchIdOption ?: Branch::query()->value('id');
    }

    /** Strip the trailing "(12345)" external id and any HTML from a name. */
    protected function cleanName(?string $value): string
    {
        $value = strip_tags((string) $value);
        $value = preg_replace('/\s*\(\d+\)\s*$/', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /** Strip currency/formatting, returning a float (handles "No Limit" -> 0). */
    protected function money(?string $value): float
    {
        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    /**
     * Map a Richinei unit token (G, ML, CU, SCP, ...) to a Sweettooth
     * units_of_measure code. Unknown tokens fall back to 'unit'.
     */
    protected function richineiUomCode(?string $token): string
    {
        $token = strtoupper(trim((string) $token));

        $map = [
            'G' => 'g', 'GRAM' => 'g',
            'KG' => 'kg', 'KILOGRAM' => 'kg',
            'MG' => 'mg',
            'ML' => 'ml', 'MILILITRE' => 'ml',
            'L' => 'l',
            'CL' => 'cl', 'CENTILITRE' => 'cl',
            'PCS' => 'pcs', 'PC' => 'pcs', 'PIECE' => 'pcs', 'PIECES' => 'pcs',
            'PORTION' => 'portion',
            'SCP' => 'scoop', 'SC' => 'scoop', 'SCOOP' => 'scoop',
            'CU' => 'cu', 'CUPS' => 'cu', 'CUP' => 'cup',
            'CP' => 'cp',
            'TB' => 'tb',
            'PK' => 'pk', 'PKS' => 'pk', 'PCK' => 'pk', 'PACK' => 'pack',
            'BTL' => 'bottle', 'BTLS' => 'bottle', 'BOTTLE' => 'bottle',
            'BG' => 'bag', 'BAGS' => 'bag',
            'CTN' => 'box', 'CARTON' => 'box',
            'RO' => 'ro', 'ROLL' => 'roll',
            'SPN' => 'spn', 'SPOON' => 'spn',
            // No dedicated Sweettooth code -> generic unit
            'MT' => 'unit', 'METER' => 'unit', 'HDS' => 'unit', 'CA' => 'unit',
            'CAN' => 'unit', 'PLT' => 'unit', 'PAR' => 'unit', 'PAIRS' => 'unit',
            'SHT' => 'unit', 'SHEETS' => 'unit', 'GLS' => 'unit', 'GLASS' => 'unit',
        ];

        return $map[$token] ?? 'unit';
    }

    protected function richineiUomId(?string $token): ?int
    {
        $code = $this->richineiUomCode($token);

        if (! array_key_exists($code, $this->uomIdCache)) {
            $this->uomIdCache[$code] = UnitOfMeasure::where('code', $code)->value('id')
                ?: UnitOfMeasure::where('code', 'unit')->value('id');
        }

        return $this->uomIdCache[$code] ?: null;
    }

    // ----- Category → department mapping (shared by products & recipes) -----

    /** The numeric ids used in category_department_map.csv → canonical names. */
    protected array $deptIdToName = [
        '1' => 'HOT KITCHEN', '2' => 'PASTRY', '3' => 'GELATO', '4' => 'CORNERSTONE',
        '5' => 'Till Sales', '6' => 'Concession', '7' => 'Corner Store', '8' => 'Inventory/Store',
    ];

    protected ?array $categoryMap = null;

    protected array $departmentIdCache = [];

    /** Load the reviewed map: normalized category => ['target'=>str, 'dept'=>?name]. */
    protected function loadCategoryMap(): array
    {
        if ($this->categoryMap !== null) {
            return $this->categoryMap;
        }

        $file = base_path('data-migration/category_department_map.csv');
        $map = [];

        if (is_file($file) && ($h = fopen($file, 'r')) !== false) {
            $header = fgetcsv($h);
            while (($row = fgetcsv($h)) !== false) {
                $r = array_combine($header, $row);
                $override = trim($r['OVERRIDE_department_id'] ?? '');
                $deptId = $override !== '' ? $override : trim($r['department_id'] ?? '');
                $map[$this->normCat($r['category'])] = [
                    'target' => trim($r['suggested_target'] ?? ''),
                    'dept' => $deptId !== '' ? ($this->deptIdToName[$deptId] ?? null) : null,
                ];
            }
            fclose($h);
        }

        return $this->categoryMap = $map;
    }

    protected function normCat(?string $c): string
    {
        $c = strip_tags(html_entity_decode((string) $c));

        return strtoupper(trim(preg_replace('/\s+/', ' ', $c))) ?: '(NONE)';
    }

    /**
     * Decide what a source product/recipe becomes.
     * Returns ['kind' => 'product'|'item'|'wip', 'dept' => ?canonicalName].
     */
    protected function classify(string $category, string $name, bool $notForSelling): array
    {
        $entry = $this->loadCategoryMap()[$this->normCat($category)] ?? ['target' => '', 'dept' => null];
        $target = $entry['target'];

        // Raw-material categories, or anything flagged not-for-selling -> Item.
        if (str_contains($target, 'ITEM')) {
            return ['kind' => 'item', 'dept' => null];
        }

        if (str_contains($target, 'BY-NAME')) {
            $dept = $this->classifyByName($name);
            if (! $dept) {
                return ['kind' => 'item', 'dept' => null]; // unrecognised name -> raw item
            }

            return ['kind' => str_contains($target, 'Work-In-Progress') ? 'wip' : 'product', 'dept' => $dept];
        }

        if ($entry['dept']) {
            return ['kind' => $notForSelling ? 'item' : 'product', 'dept' => $entry['dept']];
        }

        // No mapping at all -> treat as item so we never misfile onto a menu.
        return ['kind' => 'item', 'dept' => null];
    }

    /** Best-effort department from a product/recipe NAME (for (none)/WIP rows). */
    protected function classifyByName(string $name): ?string
    {
        $c = strtolower($name);
        $rules = [
            'GELATO' => '/gelato|ice ?cream|soft ?serve|sorbet|sundae|yoghurt|yogurt|parfait/',
            'PASTRY' => '/waffle|pancake|cake|dough?nut|croissant|cookie|bread|biscuit|muffin|cupcake|\bpie\b|tart|scone|brownie|danish|\bbun\b|cinnamon|crepe|chocolate|pastry|crumble/',
            'Concession' => '/\btea\b|coffee|latte|cappu|espresso|cocktail|mocktail|\bmilk\b|juice|smoothie|lemonade|chapman|\bbrew\b|\bwater\b|shake|frapp|chai|drink|mojito|colada/',
            'HOT KITCHEN' => '/rice|chicken|fish|beef|prawn|pasta|pizza|burger|sandwich|\begg|bacon|sausage|chips|fries|mash|salad|soup|\bwrap\b|grill|saute|mac|noodle|jollof|shawarma|meat|protein|omelette|toast|breakfast/',
        ];
        foreach ($rules as $dept => $pat) {
            if (preg_match($pat, $c)) {
                return $dept;
            }
        }

        return null;
    }

    /** Resolve a canonical department name to an id in this branch (cached). */
    protected function resolveDepartmentByName(?string $name, ?string $branchId): ?int
    {
        if (! $name) {
            return null;
        }
        if (array_key_exists($name, $this->departmentIdCache)) {
            return $this->departmentIdCache[$name];
        }

        $id = Department::query()
            ->whereRaw('UPPER(name) = ?', [strtoupper($name)])
            ->when($branchId, fn ($q) => $q->where(fn ($w) => $w->whereNull('branch_id')->orWhere('branch_id', $branchId)))
            ->orderByRaw('branch_id IS NULL') // prefer branch-specific over global
            ->value('id');

        return $this->departmentIdCache[$name] = ($id ? (int) $id : null);
    }
}
