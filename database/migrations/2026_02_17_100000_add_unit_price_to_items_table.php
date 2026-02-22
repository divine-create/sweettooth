<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasUnitPrice = Schema::hasColumn('items', 'unit_price');
        $hasLastUnitPrice = Schema::hasColumn('items', 'last_unit_price');

        Schema::table('items', function (Blueprint $table) use ($hasUnitPrice, $hasLastUnitPrice) {
            if (! $hasUnitPrice) {
                $table->decimal('unit_price', 12, 4)->default(0)->after('max_stock_level');
            }
            if (! $hasLastUnitPrice) {
                $table->decimal('last_unit_price', 12, 4)->default(0)->after('unit_price');
            }
        });

        if (Schema::hasColumn('items', 'unit_price') && ! $this->indexExists('items', 'items_unit_price_index')) {
            Schema::table('items', function (Blueprint $table) {
                $table->index('unit_price', 'items_unit_price_index');
            });
        }

        // Backfill from latest approved purchase item cost when available.
        $rows = DB::table('items')
            ->select('id')
            ->get();

        foreach ($rows as $row) {
            $latestCost = DB::table('purchase_items as pi')
                ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
                ->where('pi.item_id', $row->id)
                ->where('p.status', 'approved')
                ->orderByDesc('p.purchase_date')
                ->orderByDesc('pi.id')
                ->value('pi.cost_per_unit');

            if ($latestCost !== null) {
                DB::table('items')
                    ->where('id', $row->id)
                    ->update([
                        'unit_price' => (float) $latestCost,
                        'last_unit_price' => (float) $latestCost,
                    ]);
            }
        }
    }

    public function down(): void
    {
        $hasUnitPrice = Schema::hasColumn('items', 'unit_price');
        $hasLastUnitPrice = Schema::hasColumn('items', 'last_unit_price');
        $hasIndex = $this->indexExists('items', 'items_unit_price_index');

        if (! $hasUnitPrice && ! $hasLastUnitPrice) {
            return;
        }

        Schema::table('items', function (Blueprint $table) use ($hasIndex, $hasUnitPrice, $hasLastUnitPrice) {
            if ($hasIndex) {
                $table->dropIndex('items_unit_price_index');
            }

            $dropColumns = [];
            if ($hasUnitPrice) {
                $dropColumns[] = 'unit_price';
            }
            if ($hasLastUnitPrice) {
                $dropColumns[] = 'last_unit_price';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->pluck('name')
                ->contains($indexName);
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return collect(DB::select("SHOW INDEXES FROM `{$table}`"))
                ->pluck('Key_name')
                ->contains($indexName);
        }

        if ($driver === 'pgsql') {
            return collect(DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND schemaname = current_schema()",
                [$table]
            ))
                ->pluck('indexname')
                ->contains($indexName);
        }

        return false;
    }
};
