<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Product;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only list of products on a sales menu (have a sales department) in the
 * CURRENT branch that still have NO price set. These can't be sold at the POS
 * until a price is entered. Mirrors get_products_without_recipe for sales setup.
 */
class GetUnpricedProductsTool implements ChatTool
{
    public function name(): string
    {
        return 'get_unpriced_products';
    }

    public function description(): string
    {
        return 'List products in the current branch that are on a sales menu '
            . '(assigned to a sales department) but have NO price set yet (price '
            . 'is empty or zero). These cannot be sold at the POS until priced. '
            . 'Use for questions about which products are unpriced or sales price '
            . 'setup gaps. Returns a total count and a sample list.';
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
                'active_only' => ['type' => 'boolean', 'description' => 'Only include active products (default true).'],
                'limit'       => ['type' => 'integer', 'description' => 'Max products to list (default 30, max 100). The total count is always returned.'],
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

        $activeOnly = $input['active_only'] ?? true;
        $limit = min(max((int) ($input['limit'] ?? 30), 1), 100);

        $query = Product::query()
            ->where('branch_id', $branchId)
            // On a sales menu (has a sales department) but unpriced.
            ->whereNotNull('sales_department_id')
            ->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0))
            ->when($activeOnly, fn ($q) => $q->where('is_active', true));

        $total = (clone $query)->count();

        $products = $query->orderBy('name')
            ->limit($limit)
            ->get(['name', 'sku', 'is_active'])
            ->map(fn ($p) => [
                'name'      => $p->name,
                'sku'       => $p->sku,
                'is_active' => (bool) $p->is_active,
            ])
            ->all();

        return [
            'branch'      => current_branch()?->name,
            'active_only' => (bool) $activeOnly,
            'total'       => $total,
            'showing'     => count($products),
            'products'    => $products,
            'note'        => $total > count($products)
                ? "Showing {$limit} of {$total}; ask for more or refine if needed."
                : null,
        ];
    }
}
