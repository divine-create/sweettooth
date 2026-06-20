<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Product;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only recipe lookup for a produced product in the CURRENT branch:
 * yield, cost, prep time, instructions and the ingredient items + quantities.
 */
class GetProductRecipeTool implements ChatTool
{
    public function name(): string
    {
        return 'get_product_recipe';
    }

    public function description(): string
    {
        return 'Look up the recipe for a produced product in the current branch by '
            . 'name or SKU: its yield, cost per unit, prep time, instructions and the '
            . 'list of ingredient items with quantities. Use for "what\'s the recipe '
            . 'for X?", "what goes into Y?", or ingredient/yield questions.';
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
                'product' => ['type' => 'string', 'description' => 'Product name or SKU (or part of it).'],
            ],
            'required' => ['product'],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $branchId = current_branch_id();

        abort_unless($branchId !== null, 422, 'No active branch.');
        abort_unless(is_super_admin() || auth()->user()?->can($this->permission()), 403);

        $term = trim((string) ($input['product'] ?? ''));

        if ($term === '') {
            return ['note' => 'No product name or SKU given.'];
        }

        $product = Product::query()
            ->where('branch_id', $branchId)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            })
            ->with(['recipes.ingredients.item:id,name'])
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$term])
            ->first();

        if (! $product) {
            return ['note' => "No product matching '{$term}' found in this branch."];
        }

        if ($product->recipes->isEmpty()) {
            return [
                'product'    => $product->name,
                'has_recipe' => false,
                'note'       => 'This product has no recipe defined yet.',
            ];
        }

        return [
            'product'    => $product->name,
            'sku'        => $product->sku,
            'has_recipe' => true,
            'recipes'    => $product->recipes->map(fn ($recipe) => [
                'yield_quantity'   => (float) $recipe->yield_quantity,
                'cost_per_unit'    => round((float) $recipe->cost_per_unit, 2),
                'preparation_time' => $recipe->preparation_time,
                'is_wip'           => (bool) $recipe->is_wip,
                'instructions'     => $recipe->instructions,
                'ingredients'      => $recipe->ingredients->map(fn ($ing) => [
                    'item'             => $ing->item?->name ?? 'component',
                    'quantity'         => (float) $ing->quantity,
                    'waste_percentage' => $ing->waste_percentage !== null ? (float) $ing->waste_percentage : null,
                ])->all(),
            ])->all(),
        ];
    }
}
