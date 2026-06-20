<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Stock;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only list of items at/below their reorder level for the CURRENT branch.
 */
class GetLowStockItemsTool implements ChatTool
{
    public function name(): string
    {
        return 'get_low_stock_items';
    }

    public function description(): string
    {
        return 'List inventory items in the current branch whose available quantity '
            . 'is at or below their reorder level (items that need restocking). '
            . 'Use for questions about low stock, reorder alerts, or what to buy.';
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
                'limit' => ['type' => 'integer', 'description' => 'Max items to return (default 20, max 50).'],
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

        $limit = min(max((int) ($input['limit'] ?? 20), 1), 50);

        $rows = Stock::query()
            ->where('stocks.branch_id', $branchId)
            ->join('items', 'items.id', '=', 'stocks.item_id')
            ->where('items.reorder_level', '>', 0)
            ->whereColumn('stocks.quantity_available', '<=', 'items.reorder_level')
            ->orderByRaw('(items.reorder_level - stocks.quantity_available) DESC')
            ->limit($limit)
            ->get([
                'items.name as name',
                'items.sku as sku',
                'stocks.quantity_available as quantity_available',
                'items.reorder_level as reorder_level',
            ]);

        return [
            'branch'         => current_branch()?->name,
            'count'          => $rows->count(),
            'items'          => $rows->map(fn ($r) => [
                'name'               => $r->name,
                'sku'                => $r->sku,
                'quantity_available' => (float) $r->quantity_available,
                'reorder_level'      => (float) $r->reorder_level,
            ])->all(),
        ];
    }
}
