<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ProductStock;
use App\Services\Chatbot\Contracts\ChatTool;
use Carbon\Carbon;

/**
 * Read-only finished-goods / sellable stock per PRODUCT for a given day in the
 * CURRENT branch (the daily POS stock model: product_stocks closing_quantity).
 * Complements get_item_stock, which covers raw-material inventory items.
 */
class GetProductStockTool implements ChatTool
{
    public function name(): string
    {
        return 'get_product_stock';
    }

    public function description(): string
    {
        return 'Look up sellable finished-goods stock for products in the current '
            . 'branch for a given day (defaults to today), from the daily sales '
            . 'stock. Returns the available (closing) quantity per product, summed '
            . 'across shifts. Optionally filter by product name. Use for "how much '
            . 'of product X is available to sell?" or what is in stock to sell today. '
            . 'For raw-material inventory use get_item_stock instead.';
    }

    public function permission(): ?string
    {
        return 'view-sales';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'Optional product name or SKU (or part of it) to filter by.'],
                'date'  => ['type' => 'string', 'description' => 'Stock date (YYYY-MM-DD). Defaults to today.'],
                'limit' => ['type' => 'integer', 'description' => 'Max products to list (default 30, max 100).'],
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

        $date = isset($input['date']) ? Carbon::parse($input['date'])->toDateString() : now()->toDateString();
        $term = trim((string) ($input['query'] ?? ''));
        $limit = min(max((int) ($input['limit'] ?? 30), 1), 100);

        $query = ProductStock::query()
            ->whereDate('product_stocks.stock_date', $date)
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('products.branch_id', $branchId)
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('products.name', 'like', "%{$term}%")
                    ->orWhere('products.sku', 'like', "%{$term}%");
            }))
            ->groupBy('product_stocks.product_id', 'products.name', 'products.sku')
            ->selectRaw('products.name as name, products.sku as sku, '
                . 'COALESCE(SUM(product_stocks.closing_quantity),0) as available')
            ->orderBy('products.name');

        $total = (clone $query)->get()->count();

        $rows = $query->limit($limit)->get();

        return [
            'branch'   => current_branch()?->name,
            'date'     => $date,
            'total'    => $total,
            'showing'  => $rows->count(),
            'products' => $rows->map(fn ($r) => [
                'name'      => $r->name,
                'sku'       => $r->sku,
                'available' => round((float) $r->available, 2),
            ])->all(),
            'note' => $rows->isEmpty()
                ? "No sellable stock recorded for {$date} in this branch (has Stock Opening run?)."
                : ($total > $rows->count() ? "Showing {$limit} of {$total}; refine with a product name." : null),
        ];
    }
}
