<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('production_requests', 'batches_requested')) {
                $table->integer('batches_requested')->nullable()->after('requested_units');
            }
            if (! Schema::hasColumn('production_requests', 'batches_produced')) {
                $table->integer('batches_produced')->default(0)->after('batches_requested');
            }
            if (! Schema::hasColumn('production_requests', 'batches_pending')) {
                $table->integer('batches_pending')->default(0)->after('batches_produced');
            }
            if (! Schema::hasColumn('production_requests', 'partial_fulfillment_allowed')) {
                $table->boolean('partial_fulfillment_allowed')->default(true)->after('batches_pending');
            }
            if (! Schema::hasColumn('production_requests', 'fulfillment_status')) {
                $table->enum('fulfillment_status', ['pending', 'partial', 'completed', 'exceeded'])
                    ->default('pending')
                    ->after('partial_fulfillment_allowed');
            }
            if (! $this->indexExists('production_requests', 'production_requests_fulfillment_status_index')) {
                $table->index('fulfillment_status');
            }
            if (! $this->indexExists('production_requests', 'production_requests_batches_pending_index')) {
                $table->index('batches_pending');
            }
        });

        $rows = DB::table('production_requests as pr')
            ->leftJoin('recipes as r', 'r.id', '=', 'pr.recipe_id')
            ->select('pr.id', 'pr.planned_production_quantity', 'r.yield_quantity')
            ->get();

        foreach ($rows as $row) {
            $planned = (float) ($row->planned_production_quantity ?? 0);
            $yield = (float) ($row->yield_quantity ?? 0);

            $requested = null;
            if ($planned > 0 && $yield > 0) {
                $requested = (int) ceil($planned / $yield);
            }

            $produced = 0;
            if ($yield > 0) {
                $producedUnits = (float) DB::table('daily_produces')
                    ->where('production_request_id', $row->id)
                    ->sum('produced_quantity');
                $produced = (int) floor($producedUnits / $yield);
            }

            $pending = $requested !== null ? max(0, $requested - $produced) : 0;

            $status = 'pending';
            if ($requested !== null) {
                if ($produced > $requested) {
                    $status = 'exceeded';
                } elseif ($produced === 0) {
                    $status = 'pending';
                } elseif ($produced >= $requested) {
                    $status = 'completed';
                } else {
                    $status = 'partial';
                }
            }

            DB::table('production_requests')
                ->where('id', $row->id)
                ->update([
                    'batches_requested' => $requested,
                    'batches_produced' => $produced,
                    'batches_pending' => $pending,
                    'fulfillment_status' => $status,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('production_requests', function (Blueprint $table) {
            if ($this->indexExists('production_requests', 'production_requests_fulfillment_status_index')) {
                $table->dropIndex(['fulfillment_status']);
            }
            if ($this->indexExists('production_requests', 'production_requests_batches_pending_index')) {
                $table->dropIndex(['batches_pending']);
            }
            $dropColumns = [];
            foreach (['batches_requested', 'batches_produced', 'batches_pending', 'partial_fulfillment_allowed', 'fulfillment_status'] as $column) {
                if (Schema::hasColumn('production_requests', $column)) {
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
