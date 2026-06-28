<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ReadsRichineiExport;
use App\Models\Item;
use App\Models\Stock;
use Illuminate\Console\Command;

class ImportRichineiStock extends Command
{
    use ReadsRichineiExport;

    protected $signature = 'import:richinei-stock
        {--branch-id= : Branch UUID (defaults to first branch)}
        {--overwrite : Also overwrite quantity for items whose stock is currently zero}
        {--dry-run : Report only; write nothing}';

    protected $description = 'Backfill opening stock from the Richinei export onto existing items (matched by sku = source product id)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $overwrite = (bool) $this->option('overwrite');
        $branchId = $this->resolveBranchId($this->option('branch-id'));

        // variations.json carries product_id + clean numeric qty_available.
        $variations = $this->loadExport('variations');
        if (empty($variations)) {
            $this->warn('No variation/stock data to import.');

            return self::SUCCESS;
        }

        // source product_id => qty_available
        $qtyBySku = [];
        foreach ($variations as $v) {
            $sku = (string) ($v['product_id'] ?? '');
            if ($sku === '') {
                continue;
            }
            $qtyBySku[$sku] = (float) ($v['qty_available'] ?? 0);
        }

        $items = Item::query()->where('branch_id', $branchId)->get(['id', 'sku', 'name']);
        $this->info($items->count().' items in branch; '.count($qtyBySku).' source stock figures.'.($dryRun ? '  [DRY RUN]' : ''));

        $createdRows = $filledZero = $noSource = $alreadyHasStock = 0;

        foreach ($items as $item) {
            if (! array_key_exists((string) $item->sku, $qtyBySku)) {
                $noSource++;

                continue;
            }
            $qty = $qtyBySku[(string) $item->sku];

            $stock = Stock::where('branch_id', $branchId)->where('item_id', $item->id)->first();

            if (! $stock) {
                if (! $dryRun) {
                    Stock::create([
                        'branch_id' => $branchId,
                        'item_id' => $item->id,
                        'quantity_available' => max($qty, 0),
                        'health_status' => 'good',
                    ]);
                }
                $createdRows++;
            } elseif ($overwrite && (float) $stock->quantity_available == 0.0 && $qty > 0) {
                if (! $dryRun) {
                    $stock->update(['quantity_available' => $qty]);
                }
                $filledZero++;
            } else {
                $alreadyHasStock++;
            }
        }

        $this->newLine();
        $this->info("Stock — new rows: {$createdRows}, zero-filled: {$filledZero}, untouched (already had stock): {$alreadyHasStock}, no source match: {$noSource}");

        if ($dryRun) {
            $this->warn('DRY RUN: nothing was written.');
        }
        if (! $overwrite) {
            $this->line('Tip: pass --overwrite to set quantity on items whose stock is currently zero.');
        }

        return self::SUCCESS;
    }
}
