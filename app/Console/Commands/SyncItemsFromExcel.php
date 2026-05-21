<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncItemsFromExcel extends Command
{
    protected $signature   = 'items:sync-from-excel {--dry-run : Print changes without committing}';
    protected $description = 'Sync items table (names, UOMs, deletions) from /tmp/excel_items.json';

    public function handle(): int
    {
        $jsonPath = '/tmp/excel_items.json';

        if (! file_exists($jsonPath)) {
            $this->error("File not found: {$jsonPath}");
            return 1;
        }

        $excel = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($excel)) {
            $this->error('Failed to parse excel JSON.');
            return 1;
        }

        $excelIds = array_map('intval', array_keys($excel));
        $isDryRun = $this->option('dry-run');

        // UOM symbol → id map (last id wins for duplicates, so 'pk'=32 beats 'pk'=15)
        $uomMap = DB::table('units_of_measure')
            ->orderBy('id')
            ->pluck('id', 'symbol')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $nameUpdates  = 0;
        $uomUpdates   = 0;
        $deletedItems = 0;
        $deletedDeps  = [];

        DB::transaction(function () use (
            $excel, $excelIds, $uomMap, $isDryRun,
            &$nameUpdates, &$uomUpdates, &$deletedItems, &$deletedDeps
        ) {
            $localItems = DB::table('items')
                ->get(['id', 'name', 'uom_id'])
                ->keyBy(fn ($r) => (int) $r->id);

            // ── 1. Update names ───────────────────────────────────────────────
            foreach ($excelIds as $id) {
                $row = $localItems->get($id);
                if (! $row) {
                    continue;
                }
                $excelName = $excel[(string) $id]['name'];
                if ($row->name !== $excelName) {
                    DB::table('items')->where('id', $id)->update(['name' => $excelName]);
                    $this->line("  name  [{$id}]: '{$row->name}' → '{$excelName}'");
                    $nameUpdates++;
                }
            }

            // ── 2. Update UOMs ────────────────────────────────────────────────
            foreach ($excelIds as $id) {
                $row = $localItems->get($id);
                if (! $row) {
                    continue;
                }
                $excelUomSymbol = $excel[(string) $id]['uom'];
                $excelUomId     = $uomMap[$excelUomSymbol] ?? null;
                if ($excelUomId && (int) $row->uom_id !== $excelUomId) {
                    DB::table('items')->where('id', $id)->update(['uom_id' => $excelUomId]);
                    $this->line("  uom   [{$id}]: uom_id={$row->uom_id} → {$excelUomId} ({$excelUomSymbol})");
                    $uomUpdates++;
                }
            }

            // ── 3. Cascade-delete items not in Excel ──────────────────────────
            $toDelete = $localItems->keys()
                ->diff($excelIds)
                ->values()
                ->toArray();

            if (empty($toDelete)) {
                $this->info('No items to delete.');
                if ($isDryRun) {
                    DB::rollBack();
                }
                return;
            }

            // 3a. raw_material_utilizations
            $cnt = DB::table('raw_material_utilizations')->whereIn('item_id', $toDelete)->count();
            DB::table('raw_material_utilizations')->whereIn('item_id', $toDelete)->delete();
            $deletedDeps['raw_material_utilizations'] = $cnt;

            // 3b. Resolve stock IDs
            $stockIds = DB::table('stocks')->whereIn('item_id', $toDelete)->pluck('id')->toArray();

            // 3c. stock_batches
            $cnt = DB::table('stock_batches')->whereIn('stock_id', $stockIds)->count();
            DB::table('stock_batches')->whereIn('stock_id', $stockIds)->delete();
            $deletedDeps['stock_batches'] = $cnt;

            // 3d. stock_movements
            $cnt = DB::table('stock_movements')->whereIn('stock_id', $stockIds)->count();
            DB::table('stock_movements')->whereIn('stock_id', $stockIds)->delete();
            $deletedDeps['stock_movements'] = $cnt;

            // 3e. production_store_stocks (table may not exist on all installs)
            if (DB::getSchemaBuilder()->hasTable('production_store_stocks')) {
                $cnt = DB::table('production_store_stocks')->whereIn('item_id', $toDelete)->count();
                DB::table('production_store_stocks')->whereIn('item_id', $toDelete)->delete();
                $deletedDeps['production_store_stocks'] = $cnt;
            }

            // 3f. production_store_movements
            $cnt = DB::table('production_store_movements')->whereIn('item_id', $toDelete)->count();
            DB::table('production_store_movements')->whereIn('item_id', $toDelete)->delete();
            $deletedDeps['production_store_movements'] = $cnt;

            // 3g. recipe_ingredients
            $cnt = DB::table('recipe_ingredients')->whereIn('item_id', $toDelete)->count();
            DB::table('recipe_ingredients')->whereIn('item_id', $toDelete)->delete();
            $deletedDeps['recipe_ingredients'] = $cnt;

            // 3h. material_request_dispatches
            $cnt = DB::table('material_request_dispatches')->whereIn('item_id', $toDelete)->count();
            DB::table('material_request_dispatches')->whereIn('item_id', $toDelete)->delete();
            $deletedDeps['material_request_dispatches'] = $cnt;

            // 3i. material_request_details
            $cnt = DB::table('material_request_details')->whereIn('item_id', $toDelete)->count();
            DB::table('material_request_details')->whereIn('item_id', $toDelete)->delete();
            $deletedDeps['material_request_details'] = $cnt;

            // 3j. purchase_items
            $cnt = DB::table('purchase_items')->whereIn('item_id', $toDelete)->count();
            DB::table('purchase_items')->whereIn('item_id', $toDelete)->delete();
            $deletedDeps['purchase_items'] = $cnt;

            // 3k. stocks
            DB::table('stocks')->whereIn('item_id', $toDelete)->delete();
            $deletedDeps['stocks'] = count($stockIds);

            // 3l. items
            $deletedItems = DB::table('items')->whereIn('id', $toDelete)->delete();

            if ($isDryRun) {
                DB::rollBack();
            }
        });

        // ── Summary ───────────────────────────────────────────────────────────
        $mode = $isDryRun ? '[DRY RUN] ' : '';
        $this->info('');
        $this->info("{$mode}Sync complete:");
        $this->info("  Name updates : {$nameUpdates}");
        $this->info("  UOM updates  : {$uomUpdates}");
        $this->info("  Items deleted: {$deletedItems}");
        foreach ($deletedDeps as $table => $cnt) {
            if ($cnt > 0) {
                $this->info("    └─ {$table}: {$cnt}");
            }
        }

        if ($isDryRun) {
            $this->warn('Dry run — no changes were committed.');
        }

        return 0;
    }
}
