<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Product;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only list of products in the CURRENT branch that have no recipe defined
 * (Product hasMany Recipe). Useful for spotting setup gaps before production.
 */
class GetProductsWithoutRecipeTool implements ChatTool
{
    public function name(): string
    {
        return 'get_products_without_recipe';
    }

    public function description(): string
    {
        return 'List products in the current branch that do not have a recipe defined '
            . 'yet. Use for questions about which products are missing recipes, recipe '
            . 'setup gaps, or products that cannot be produced. Returns a total count '
            . 'and a sample list.';
    }

    public function permission(): ?string
    {
        return 'view-production';
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
            ->whereDoesntHave('recipes')
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
