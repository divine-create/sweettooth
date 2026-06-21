<?php

namespace App\Services\Chatbot\Tools;

use App\Models\Item;
use App\Models\Product;
use App\Services\Chatbot\Contracts\ChatTool;

/**
 * Read-only go-live readiness report for the CURRENT branch: a single consolidated
 * view of the common setup gaps that block trading — unpriced menu products,
 * products missing recipes, products with no sales department, and inventory items
 * missing a unit cost or reorder level.
 */
class GetSetupReadinessTool implements ChatTool
{
    public function name(): string
    {
        return 'get_setup_readiness';
    }

    public function description(): string
    {
        return 'Consolidated go-live readiness / setup-gap report for the current '
            . 'branch. Counts: products on a menu with no price, produced products '
            . 'with no recipe, products with no sales department, inventory items '
            . 'missing a unit cost, and items missing a reorder level. Use for '
            . '"are we ready to go live?", "what setup is left?", or readiness '
            . 'questions. Returns counts (active products/items only).';
    }

    public function permission(): ?string
    {
        return 'view-reports';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
            'additionalProperties' => false,
        ];
    }

    public function handle(array $input): array
    {
        $branchId = current_branch_id();

        abort_unless($branchId !== null, 422, 'No active branch.');
        abort_unless(is_super_admin() || auth()->user()?->can($this->permission()), 403);

        $unpriced = Product::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNotNull('sales_department_id')
            ->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0))
            ->count();

        $noRecipe = Product::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNull('linked_item_id') // produced in-house (not resale goods)
            ->whereDoesntHave('recipes')
            ->count();

        $noSalesDept = Product::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNull('sales_department_id')
            ->count();

        $itemsMissingCost = Item::query()
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('unit_price')->orWhere('unit_price', '<=', 0))
            ->count();

        $itemsMissingReorder = Item::query()
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('reorder_level')->orWhere('reorder_level', '<=', 0))
            ->count();

        $totalGaps = $unpriced + $noRecipe + $noSalesDept + $itemsMissingCost + $itemsMissingReorder;

        return [
            'branch' => current_branch()?->name,
            'gaps' => [
                'products_unpriced'        => $unpriced,
                'products_without_recipe'  => $noRecipe,
                'products_no_sales_dept'   => $noSalesDept,
                'items_missing_unit_cost'  => $itemsMissingCost,
                'items_missing_reorder'    => $itemsMissingReorder,
            ],
            'total_gaps'  => $totalGaps,
            'ready'       => $totalGaps === 0,
            'note'        => $totalGaps === 0
                ? 'No outstanding setup gaps detected for active products/items.'
                : 'Counts cover active products/items only. Use get_unpriced_products '
                    . 'or get_products_without_recipe for the detailed lists.',
        ];
    }
}
