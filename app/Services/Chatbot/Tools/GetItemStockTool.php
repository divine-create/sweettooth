<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Stock;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only on-hand lookup for a specific item (by name/SKU) in the CURRENT branch.
 */
class GetItemStockTool implements ChatTool
{
    public function name(): string
    {
        return 'get_item_stock';
    }

    public function description(): string
    {
        return 'Look up the current on-hand quantity of a specific inventory item '
            . 'in the current branch by name or SKU. Use for questions like '
            . '"how much X do we have?" or "is item Y in stock?".';
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
                'query' => ['type' => 'string', 'description' => 'Item name or SKU (or part of it) to search for.'],
            ],
            'required' => ['query'],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $branchId = current_branch_id();

        abort_unless($branchId !== null, 422, 'No active branch.');
        abort_unless(is_super_admin() || auth()->user()?->can($this->permission()), 403);

        $term = trim((string) ($input['query'] ?? ''));

        if ($term === '') {
            return ['matches' => [], 'note' => 'No search term given.'];
        }

        $rows = Stock::query()
            ->where('stocks.branch_id', $branchId)
            ->join('items', 'items.id', '=', 'stocks.item_id')
            ->where(function ($q) use ($term) {
                $q->where('items.name', 'like', "%{$term}%")
                    ->orWhere('items.sku', 'like', "%{$term}%");
            })
            ->orderBy('items.name')
            ->limit(10)
            ->get([
                'items.name as name',
                'items.sku as sku',
                'stocks.quantity_available as quantity_available',
                'items.reorder_level as reorder_level',
                'stocks.average_cost as average_cost',
            ]);

        return [
            'branch'  => current_branch()?->name,
            'matches' => $rows->map(fn ($r) => [
                'name'               => $r->name,
                'sku'                => $r->sku,
                'quantity_available' => (float) $r->quantity_available,
                'reorder_level'      => (float) $r->reorder_level,
                'average_cost'       => round((float) $r->average_cost, 2),
            ])->all(),
            'note' => $rows->isEmpty() ? 'No matching items found in this branch.' : null,
        ];
    }
}
