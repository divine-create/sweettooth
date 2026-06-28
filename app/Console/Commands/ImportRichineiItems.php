<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ReadsRichineiExport;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Console\Command;

class ImportRichineiItems extends Command
{
    use ReadsRichineiExport;

    protected $signature = 'import:richinei-items
        {--branch-id= : Branch UUID (defaults to first branch)}
        {--dry-run : Report only; write nothing}';

    protected $description = 'Import raw-material items from the Richinei export (everything classified as an Item, not a sellable product)';

    protected array $catCache = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        $products = $this->loadExport('products');
        if (empty($products)) {
            $this->warn('No items to import.');

            return self::SUCCESS;
        }
        if (! $this->loadCategoryMap()) {
            $this->error('category_department_map.csv not found/empty.');

            return self::FAILURE;
        }

        // source product_id => [selling_price, unit token]
        $vmap = [];
        foreach ($this->loadExport('variations') as $v) {
            $vmap[(string) ($v['product_id'] ?? '')] = [
                'price' => (float) ($v['selling_price'] ?? 0),
                'unit' => $v['unit'] ?? null,
            ];
        }

        $this->info(count($products).' source products.'.($dryRun ? '  [DRY RUN]' : ''));

        $created = $updated = $skippedProduct = $skippedComplete = 0;

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $p) {
            $bar->advance();

            $sku = (string) ($p['id'] ?? '');
            $name = $this->cleanName($p['product'] ?? null);
            if ($sku === '' || $name === '') {
                continue;
            }

            $class = $this->classify(
                (string) ($p['category'] ?? ''),
                $name,
                (int) ($p['not_for_selling'] ?? 0) === 1,
            );
            if ($class['kind'] !== 'item') {
                $skippedProduct++; // sellable -> handled by products importer

                continue;
            }

            $uomId = $this->richineiUomId($vmap[$sku]['unit'] ?? ($p['unit'] ?? null));
            $reorder = $this->money($p['alert_quantity'] ?? null);

            // Match by SKU, then by NAME — the source itself sometimes holds the
            // same item under two ids (e.g. two "CORN FLAKES"); name-match collapses
            // those into one instead of creating duplicates.
            $existing = Item::where('branch_id', $branchId)->where('sku', $sku)->first()
                ?? Item::where('sku', $sku)->first()
                ?? Item::where('branch_id', $branchId)->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first()
                ?? Item::whereRaw('UPPER(TRIM(name)) = ?', [strtoupper($name)])->first();

            if ($existing) {
                $fill = [];
                if (! $existing->uom_id && $uomId) {
                    $fill['uom_id'] = $uomId;
                }
                if (! $existing->category_id) {
                    $fill['category_id'] = $this->categoryId($p['category'] ?? null, $branchId, $dryRun);
                }
                if (($existing->reorder_level === null) && $reorder > 0) {
                    $fill['reorder_level'] = $reorder;
                }
                if ($fill && ! $dryRun) {
                    $existing->update($fill);
                }
                $fill ? $updated++ : $skippedComplete++;

                continue;
            }

            if (! $dryRun) {
                Item::create([
                    'branch_id' => $branchId,
                    'name' => $name,
                    'sku' => $sku,
                    'category_id' => $this->categoryId($p['category'] ?? null, $branchId, $dryRun),
                    'uom_id' => $uomId,
                    'reorder_level' => $reorder > 0 ? $reorder : null,
                    'unit_price' => 0, // source list has no purchase cost; backfill manually/later
                    'last_unit_price' => 0,
                    'status' => (int) ($p['is_inactive'] ?? 0) === 1 ? 'inactive' : 'active',
                    'requires_request' => true,
                ]);
            }
            $created++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Items — created: {$created}, updated: {$updated}, already-complete: {$skippedComplete}");
        $this->line("Skipped — sellable products (not items): {$skippedProduct}");

        if ($dryRun) {
            $this->warn('DRY RUN: nothing was written.');
        }

        return self::SUCCESS;
    }

    /** Get/create an ItemCategory by source category name; returns its UUID id. */
    protected function categoryId(?string $category, ?string $branchId, bool $dryRun): ?string
    {
        $name = $this->cleanName($category) ?: 'Uncategorized';
        $key = strtoupper($name);

        if (array_key_exists($key, $this->catCache)) {
            return $this->catCache[$key];
        }

        $existing = ItemCategory::whereRaw('UPPER(name) = ?', [$key])->first();
        if ($existing) {
            return $this->catCache[$key] = $existing->id;
        }

        if ($dryRun) {
            return $this->catCache[$key] = null;
        }

        $cat = ItemCategory::create([
            'name' => $name,
            'status' => 'active',
            'branch_id' => $branchId,
        ]);

        return $this->catCache[$key] = $cat->id;
    }
}
