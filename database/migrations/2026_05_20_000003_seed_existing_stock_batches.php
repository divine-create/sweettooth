<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill one batch per existing stock record that has available quantity.
        // Carries over the current expiry_date and average_cost so FEFO works immediately.
        DB::table('stocks')
            ->where('quantity_available', '>', 0)
            ->orderBy('id')
            ->chunk(200, function ($stocks) {
                $now = now();
                $rows = [];

                foreach ($stocks as $stock) {
                    $rows[] = [
                        'stock_id'           => $stock->id,
                        'branch_id'          => $stock->branch_id,
                        'purchase_item_id'   => null,
                        'batch_number'       => null,
                        'expiry_date'        => $stock->expiry_date,
                        'quantity_received'  => $stock->quantity_available,
                        'quantity_remaining' => $stock->quantity_available,
                        'unit_cost'          => $stock->average_cost ?? 0,
                        'status'             => 'active',
                        'received_at'        => $stock->last_stock_take_date ?? $stock->created_at,
                        'notes'              => 'Migrated from existing stock record',
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }

                if ($rows) {
                    DB::table('stock_batches')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // Remove only the backfilled rows (no purchase_item_id and migrated note)
        DB::table('stock_batches')
            ->whereNull('purchase_item_id')
            ->where('notes', 'Migrated from existing stock record')
            ->delete();
    }
};
