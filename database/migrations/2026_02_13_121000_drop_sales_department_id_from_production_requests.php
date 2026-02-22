<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('production_requests', 'sales_department_id')) {
            return;
        }

        $this->migrateLegacySalesRequests();

        $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'production_requests')
            ->where('COLUMN_NAME', 'sales_department_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        $idxExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'production_requests')
            ->where('INDEX_NAME', 'production_requests_sales_department_id_index')
            ->exists();

        Schema::table('production_requests', function (Blueprint $table) use ($fkExists, $idxExists) {
            if ($fkExists) {
                $table->dropForeign(['sales_department_id']);
            }

            if ($idxExists) {
                $table->dropIndex('production_requests_sales_department_id_index');
            }

            $table->dropColumn('sales_department_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_requests', 'sales_department_id')) {
            return;
        }

        Schema::table('production_requests', function (Blueprint $table) {
            $table->foreignId('sales_department_id')
                ->nullable()
                ->after('shift_id')
                ->constrained('departments')
                ->onDelete('set null');

            $table->index('sales_department_id');
        });
    }

    private function migrateLegacySalesRequests(): void
    {
        if (! Schema::hasTable('sales_production_requests') || ! Schema::hasTable('sales_production_request_items')) {
            return;
        }

        $defaultBranchId = DB::table('branches')->value('id');

        if (! $defaultBranchId) {
            return;
        }

        $rows = DB::table('production_requests as pr')
            ->leftJoin('shifts as s', 's.id', '=', 'pr.shift_id')
            ->leftJoin('departments as sd', 'sd.id', '=', 'pr.sales_department_id')
            ->whereNotNull('pr.sales_department_id')
            ->select([
                'pr.id',
                'pr.recipe_id',
                'pr.planned_production_quantity',
                'pr.status',
                'pr.priority',
                'pr.created_by_id',
                'pr.created_at',
                'pr.updated_at',
                'pr.completed_at',
                'pr.requested_units',
                'pr.notes',
                'pr.sales_department_id',
                'pr.production_department_id',
                's.branch_id as shift_branch_id',
                'sd.branch_id as sales_dept_branch_id',
            ])
            ->orderBy('pr.id')
            ->get();

        foreach ($rows as $row) {
            $branchId = $row->shift_branch_id ?: $row->sales_dept_branch_id ?: $defaultBranchId;

            if (! $branchId) {
                continue;
            }

            $mappedStatus = $this->mapLegacyStatus((string) $row->status);

            $headerId = DB::table('sales_production_requests')->insertGetId([
                'branch_id' => $branchId,
                'sales_department_id' => $row->sales_department_id,
                'request_number' => 'SPR-MIG-PR-' . $row->id,
                'status' => $mappedStatus,
                'priority' => in_array($row->priority, ['normal', 'urgent'], true) ? $row->priority : 'normal',
                'requested_by_id' => $row->created_by_id,
                'requested_by_type' => $row->created_by_id ? 'App\\Models\\User' : null,
                'completed_at' => $row->completed_at,
                'dispatched_at' => in_array($mappedStatus, ['dispatched', 'received_by_sales'], true) ? $row->updated_at : null,
                'received_at' => $mappedStatus === 'received_by_sales' ? $row->updated_at : null,
                'notes' => $row->notes,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            $productId = null;
            if ($row->recipe_id) {
                $productId = DB::table('recipes')->where('id', $row->recipe_id)->value('product_id');
            }

            DB::table('sales_production_request_items')->insert([
                'sales_production_request_id' => $headerId,
                'production_department_id' => $row->production_department_id,
                'product_id' => $productId,
                'quantity_requested' => $row->requested_units ?? $row->planned_production_quantity ?? 0,
                'quantity_produced' => in_array($mappedStatus, ['completed', 'dispatched', 'received_by_sales'], true)
                    ? ($row->planned_production_quantity ?? 0)
                    : 0,
                'status' => $mappedStatus,
                'notes' => 'Migrated from production_requests id=' . $row->id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    private function mapLegacyStatus(string $legacyStatus): string
    {
        return match ($legacyStatus) {
            'approved' => 'approved_by_production',
            'in_progress', 'quality_check' => 'processing',
            'completed' => 'completed',
            'dispatched' => 'dispatched',
            'accepted' => 'received_by_sales',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
};
