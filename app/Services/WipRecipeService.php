<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Support\Collection;

class WipRecipeService
{
    /**
     * Resolve ingredients for a recipe.
     *
     * @param Recipe $recipe The recipe to resolve ingredients for
     * @param float $productionQty The quantity to produce (scales ingredients)
     * @param bool $recursive Whether to recursively resolve WIP items to their base ingredients
     * @return Collection Collection of resolved ingredients with item, quantity, cost info
     */
    public function resolveIngredients(Recipe $recipe, float $productionQty = 1, bool $recursive = true): Collection
    {
        $ingredients = collect();
        $yieldQty = (float) $recipe->yield_quantity;

        if ($yieldQty <= 0) {
            return $ingredients;
        }

        $batchFactor = $productionQty / $yieldQty;

        $recipe->loadMissing('ingredients.item');
        $recipe->loadMissing('ingredients.component');

        foreach ($recipe->ingredients as $ingredient) {
            if ($ingredient->isWip()) {
                if ($recursive) {
                    $wipIngredients = $this->resolveWipIngredient($ingredient, $batchFactor);
                    $ingredients = $ingredients->merge($wipIngredients);
                } else {
                    $scaledQuantity = (float) $ingredient->quantity * $batchFactor;
                    $scaledQuantityWithWaste = $this->applyWaste($scaledQuantity, (float) $ingredient->waste_percentage);

                    // For non-recursive, we treat the WIP as the "item"
                    // We need to get the Product model since WIPs are Products
                    $wipProduct = null;
                    if ($ingredient->component_id) {
                        $wipRecipe = Recipe::find($ingredient->component_id);
                        $wipProduct = $wipRecipe?->product;
                    }

                    if (!$wipProduct && $ingredient->item_id) {
                        $wipProduct = \App\Models\Product::find($ingredient->item_id);
                    }

                    $ingredients->push((object) [
                        'item' => $wipProduct,
                        'item_id' => $wipProduct?->id ?? $ingredient->item_id,
                        'item_name' => $wipProduct?->name ?? $ingredient->item_name,
                        'quantity_for_production' => $scaledQuantityWithWaste,
                        'quantity_base' => (float) $ingredient->quantity,
                        'quantity_scaled' => $scaledQuantity,
                        'cost_per_unit' => (float) ($wipProduct?->cost ?? $ingredient->cost_per_unit ?? 0),
                        'waste_percentage' => (float) $ingredient->waste_percentage,
                        'resolved_from_wip' => true,
                        'is_wip_item' => true,
                    ]);
                }
            } elseif ($ingredient->isItem()) {
                $scaledQuantity = (float) $ingredient->quantity * $batchFactor;
                $scaledQuantityWithWaste = $this->applyWaste($scaledQuantity, (float) $ingredient->waste_percentage);

                $ingredients->push((object) [
                    'item' => $ingredient->item,
                    'item_id' => $ingredient->item_id,
                    'item_name' => $ingredient->item?->name ?? 'Unknown',
                    'quantity_for_production' => $scaledQuantityWithWaste,
                    'quantity_base' => (float) $ingredient->quantity,
                    'quantity_scaled' => $scaledQuantity,
                    'cost_per_unit' => (float) ($ingredient->item?->unit_price ?? $ingredient->cost_per_unit ?? 0),
                    'waste_percentage' => (float) $ingredient->waste_percentage,
                    'resolved_from_wip' => false,
                ]);
            }
        }

        return $this->aggregateIngredients($ingredients);
    }

    /**
     * Resolve a WIP ingredient (recipe) to its base ingredients.
     */
    protected function resolveWipIngredient(RecipeIngredient $ingredient, float $batchFactor): Collection
    {
        $wipRecipe = null;

        if ($ingredient->component_id) {
            $wipRecipe = Recipe::find($ingredient->component_id);
        }

        if (! $wipRecipe && $ingredient->item_id) {
            $wipRecipe = Recipe::where('product_id', $ingredient->item_id)
                ->where('is_wip', true)
                ->first();
        }

        if (! $wipRecipe) {
            return collect();
        }

        $quantityUsed = (float) $ingredient->quantity * $batchFactor;

        $wipIngredients = $this->resolveIngredients($wipRecipe, $quantityUsed);

        return $wipIngredients->map(function ($resolved) use ($wipRecipe) {
            $resolved->quantity_for_production = $resolved->quantity_for_production;
            $resolved->wip_recipe_id = $wipRecipe->id;
            $resolved->wip_recipe_name = $wipRecipe->product_name;
            $resolved->resolved_from_wip = true;

            return $resolved;
        });
    }

    /**
     * Calculate total cost for a recipe including WIP items.
     */
    public function calculateCost(Recipe $recipe, float $quantity): float
    {
        $ingredients = $this->resolveIngredients($recipe, $quantity);

        return $ingredients->sum(function ($ing) {
            return $ing->quantity_for_production * $ing->cost_per_unit;
        });
    }

    /**
     * Get all WIP recipes available for use as ingredients.
     */
    public function getAvailableWipRecipes(?Recipe $excludeRecipe = null): Collection
    {
        $query = Recipe::query()
            ->where('is_wip', true)
            ->where('is_active', true)
            ->where('branch_id', current_branch_id());

        if ($excludeRecipe) {
            $query->where('id', '!=', $excludeRecipe->id);
        }

        return $query->with('ingredients.item')
            ->get();
    }

    /**
     * Check if using a recipe as ingredient would create a circular reference.
     */
    public function wouldCreateCircularReference(Recipe $parentRecipe, Recipe $childRecipe): bool
    {
        $visited = [];

        return $this->checkCircularReference($parentRecipe, $childRecipe, $visited);
    }

    /**
     * Recursively check for circular references.
     */
    protected function checkCircularReference(Recipe $current, Recipe $target, array &$visited): bool
    {
        if (in_array($current->id, $visited)) {
            return false;
        }

        if ($current->id === $target->id) {
            return true;
        }

        $visited[] = $current->id;

        $current->loadMissing('ingredients.component');

        foreach ($current->ingredients as $ingredient) {
            if ($ingredient->isWip()) {
                $childRecipe = Recipe::find($ingredient->component_id);
                if ($childRecipe && $this->checkCircularReference($childRecipe, $target, $visited)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get ingredient tree for a recipe (useful for display).
     */
    public function getIngredientTree(Recipe $recipe, int $depth = 0, int $maxDepth = 10): array
    {
        if ($depth > $maxDepth) {
            return [];
        }

        $recipe->loadMissing('ingredients.item');
        $recipe->loadMissing('ingredients.component');

        $tree = [];

        foreach ($recipe->ingredients as $ingredient) {
            if ($ingredient->isWip()) {
                $wipRecipe = Recipe::find($ingredient->component_id);
                $tree[] = [
                    'type' => 'wip',
                    'component_type' => 'recipe',
                    'component_id' => $ingredient->component_id,
                    'component_name' => $wipRecipe?->product_name ?? 'Unknown',
                    'quantity' => $ingredient->quantity,
                    'children' => $wipRecipe ? $this->getIngredientTree($wipRecipe, $depth + 1, $maxDepth) : [],
                ];
            } else {
                $tree[] = [
                    'type' => 'item',
                    'component_type' => 'item',
                    'component_id' => $ingredient->item_id,
                    'component_name' => $ingredient->item?->name ?? 'Unknown',
                    'quantity' => $ingredient->quantity,
                    'cost_per_unit' => $ingredient->cost_per_unit,
                    'waste_percentage' => $ingredient->waste_percentage,
                    'children' => [],
                ];
            }
        }

        return $tree;
    }

    /**
     * Apply waste percentage to quantity.
     */
    protected function applyWaste(float $quantity, float $wastePercentage): float
    {
        if ($wastePercentage > 0) {
            return $quantity * (1 + ($wastePercentage / 100));
        }

        return $quantity;
    }

    /**
     * Aggregate duplicate items from WIP resolution.
     */
    protected function aggregateIngredients(Collection $ingredients): Collection
    {
        $aggregated = [];

        foreach ($ingredients as $ingredient) {
            $itemId = $ingredient->item_id;

            if (! isset($aggregated[$itemId])) {
                $aggregated[$itemId] = (object) [
                    'item' => $ingredient->item,
                    'item_id' => $ingredient->item_id,
                    'item_name' => $ingredient->item_name,
                    'quantity_for_production' => 0,
                    'cost_per_unit' => $ingredient->cost_per_unit,
                    'waste_percentage' => $ingredient->waste_percentage,
                    'resolved_from_wip' => $ingredient->resolved_from_wip,
                ];
            }

            $aggregated[$itemId]->quantity_for_production += $ingredient->quantity_for_production;
        }

        return collect(array_values($aggregated));
    }
}
