<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_produces', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_produces', 'batches_produced_this_shift')) {
                $table->integer('batches_produced_this_shift')->default(0)->after('produced_quantity');
            }
            if (! Schema::hasColumn('daily_produces', 'batches_remaining')) {
                $table->integer('batches_remaining')->nullable()->after('batches_produced_this_shift');
            }
            if (! Schema::hasColumn('daily_produces', 'fulfillment_status')) {
                $table->enum('fulfillment_status', ['pending', 'partial', 'completed', 'exceeded'])
                    ->default('pending')
                    ->after('batches_remaining');
            }
            if (! $this->indexExists('daily_produces', 'daily_produces_fulfillment_status_index')) {
                $table->index('fulfillment_status');
            }
        });

        $rows = DB::table('daily_produces as dp')
            ->leftJoin('recipes as r', 'r.id', '=', 'dp.recipe_id')
            ->leftJoin('production_requests as pr', 'pr.id', '=', 'dp.production_request_id')
            ->select('dp.id', 'dp.produced_quantity', 'r.yield_quantity', 'pr.batches_requested')
            ->get();

        foreach ($rows as $row) {
            $yield = (float) ($row->yield_quantity ?? 0);
            $producedUnits = (float) ($row->produced_quantity ?? 0);
            $requestedBatches = (int) ($row->batches_requested ?? 0);

            $producedBatches = $yield > 0 ? (int) floor($producedUnits / $yield) : 0;
            $remaining = $requestedBatches > 0 ? max(0, $requestedBatches - $producedBatches) : null;

            $status = 'pending';
            if ($requestedBatches > 0) {
                if ($producedBatches > $requestedBatches) {
                    $status = 'exceeded';
                } elseif ($producedBatches === 0) {
                    $status = 'pending';
                } elseif ($producedBatches >= $requestedBatches) {
                    $status = 'completed';
                } else {
                    $status = 'partial';
                }
            }

            DB::table('daily_produces')
                ->where('id', $row->id)
                ->update([
                    'batches_produced_this_shift' => $producedBatches,
                    'batches_remaining' => $remaining,
                    'fulfillment_status' => $status,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('daily_produces', function (Blueprint $table) {
            if ($this->indexExists('daily_produces', 'daily_produces_fulfillment_status_index')) {
                $table->dropIndex(['fulfillment_status']);
            }

            $dropColumns = [];
            foreach (['batches_produced_this_shift', 'batches_remaining', 'fulfillment_status'] as $column) {
                if (Schema::hasColumn('daily_produces', $column)) {
                    $dropColumns[] = $column;
                }
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

        return collect(DB::select("SHOW INDEXES FROM {$table}"))
            ->pluck('Key_name')
            ->contains($indexName);
    }
};
