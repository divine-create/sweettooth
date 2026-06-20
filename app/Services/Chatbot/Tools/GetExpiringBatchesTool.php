<?php

namespace App\Services\Chatbot\Tools;

use App\Models\StockBatch;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only list of stock batches expiring soon in the CURRENT branch (FEFO).
 */
class GetExpiringBatchesTool implements ChatTool
{
    public function name(): string
    {
        return 'get_expiring_batches';
    }

    public function description(): string
    {
        return 'List stock batches in the current branch that expire within a given '
            . 'number of days (still having quantity remaining). Use for questions '
            . 'about expiring stock, what is going off soon, or wastage risk.';
    }

    public function permission(): ?string
    {
        return 'view-inventory';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'days' => ['type' => 'integer', 'description' => 'Look-ahead window in days (default 30, max 365).'],
            ],
            'required' => [],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $branchId = current_branch_id();

        abort_unless($branchId !== null, 422, 'No active branch.');
        abort_unless(is_super_admin() || auth()->user()?->can($this->permission()), 403);

        $days = min(max((int) ($input['days'] ?? 30), 1), 365);
        $cutoff = now()->copy()->addDays($days)->endOfDay();

        $rows = StockBatch::query()
            ->where('stock_batches.branch_id', $branchId)
            ->where('stock_batches.quantity_remaining', '>', 0)
            ->whereNotNull('stock_batches.expiry_date')
            ->where('stock_batches.expiry_date', '<=', $cutoff)
            ->join('stocks', 'stocks.id', '=', 'stock_batches.stock_id')
            ->join('items', 'items.id', '=', 'stocks.item_id')
            ->orderBy('stock_batches.expiry_date')
            ->limit(25)
            ->get([
                'items.name as name',
                'stock_batches.batch_number as batch_number',
                'stock_batches.expiry_date as expiry_date',
                'stock_batches.quantity_remaining as quantity_remaining',
            ]);

        return [
            'branch'      => current_branch()?->name,
            'window_days' => $days,
            'count'       => $rows->count(),
            'batches'     => $rows->map(function ($r) {
                $expiry = $r->expiry_date ? \Illuminate\Support\Carbon::parse($r->expiry_date) : null;

                return [
                    'item'               => $r->name,
                    'batch_number'       => $r->batch_number,
                    'expiry_date'        => $expiry?->toDateString(),
                    'days_until_expiry'  => $expiry ? now()->startOfDay()->diffInDays($expiry, false) : null,
                    'quantity_remaining' => (float) $r->quantity_remaining,
                ];
            })->all(),
        ];
    }
}
