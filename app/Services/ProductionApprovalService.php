<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ApprovalAuditRequest;
use Illuminate\Support\Facades\DB;

class ProductionApprovalService
{
    /**
     * Execute approved product creation
     */
    public static function executeProductCreation(ApprovalAuditRequest $request): ?Product
    {
        $payload = $request->payload;

        try {
            $product = Product::create([
                'name' => $payload['name'],
                'sku' => strtoupper($payload['sku']),
                'product_type_id' => $payload['product_type_id'],
                'sales_department_id' => $payload['sales_department_id'] ?? null,
                'category_id' => $payload['category_id'] ?? null,
                'description' => $payload['description'] ?? null,
                'price' => $payload['price'],
                'cost' => $payload['cost'] ?? null,
                'shelf_life_days' => $payload['shelf_life_days'],
                'uom_id' => $payload['uom_id'],
                'sales_uom_id' => $payload['sales_uom_id'] ?? null,
                'is_active' => $payload['is_active'] ?? true,
                'is_available' => $payload['is_available'] ?? true,
                'image_url' => $payload['image_url'] ?? null,
                'allergens' => $payload['allergens'] ?? [],
                'tags' => $payload['tags'] ?? [],
            ]);

            return $product;
        } catch (\Exception $e) {
            throw new \Exception("Failed to create product: " . $e->getMessage());
        }
    }

    /**
     * Execute approved product update
     */
    public static function executeProductUpdate(ApprovalAuditRequest $request): ?Product
    {
        $payload = $request->payload;
        $productId = $payload['id'] ?? null;

        if (!$productId) {
            throw new \Exception("Product ID not found in approval request");
        }

        try {
            $product = Product::findOrFail($productId);

            $product->update([
                'name' => $payload['name'],
                'sku' => strtoupper($payload['sku']),
                'product_type_id' => $payload['product_type_id'],
                'sales_department_id' => $payload['sales_department_id'] ?? null,
                'category_id' => $payload['category_id'] ?? null,
                'description' => $payload['description'] ?? null,
                'price' => $payload['price'],
                'cost' => $payload['cost'] ?? null,
                'shelf_life_days' => $payload['shelf_life_days'],
                'uom_id' => $payload['uom_id'],
                'sales_uom_id' => $payload['sales_uom_id'] ?? null,
                'is_active' => $payload['is_active'] ?? true,
                'is_available' => $payload['is_available'] ?? true,
                'image_url' => $payload['image_url'] ?? null,
                'allergens' => $payload['allergens'] ?? [],
                'tags' => $payload['tags'] ?? [],
            ]);

            return $product;
        } catch (\Exception $e) {
            throw new \Exception("Failed to update product: " . $e->getMessage());
        }
    }

    /**
     * Execute approved product deletion
     */
    public static function executeProductDeletion(ApprovalAuditRequest $request): ?Product
    {
        $productId = $request->payload['id'] ?? null;

        if (!$productId) {
            throw new \Exception("Product ID not found in approval request");
        }

        try {
            $product = Product::findOrFail($productId);
            $product->delete();
            return $product;
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete product: " . $e->getMessage());
        }
    }

    /**
     * Execute approved bulk product deletion
     */
    public static function executeProductBulkDeletion(ApprovalAuditRequest $request): array
    {
        $ids = $request->payload['ids'] ?? [];

        if (empty($ids)) {
            throw new \Exception("No product IDs found in approval request");
        }

        try {
            $deleted = [];
            foreach ($ids as $id) {
                $product = Product::find($id);
                if ($product) {
                    $deleted[] = $product->id;
                    $product->delete();
                }
            }
            return $deleted;
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete products: " . $e->getMessage());
        }
    }

    /**
     * Execute approved product assignments (sales department and/or recipe)
     */
    public static function executeProductAssignments(ApprovalAuditRequest $request): array
    {
        $payload = $request->payload;
        $type = $payload['type'] ?? null;
        $productIds = $payload['product_ids'] ?? [];
        $productId = $payload['product_id'] ?? null;
        $salesDepartmentId = $payload['sales_department_id'] ?? null;
        $recipeId = $payload['recipe_id'] ?? null;
        $departmentId = $payload['department_id'] ?? null;

        if ($type === 'sales_department' && empty($productIds)) {
            throw new \Exception("No product IDs found in approval request");
        }

        if ($type === 'recipe' && ! $productId) {
            throw new \Exception("No product ID found in approval request");
        }

        $results = [
            'products_updated' => 0,
            'recipe_updated' => false,
        ];

        try {
            if ($type === 'sales_department') {
                if (! $salesDepartmentId) {
                    throw new \Exception("No sales department provided");
                }

                $results['products_updated'] = Product::whereIn('id', $productIds)
                    ->update(['sales_department_id' => $salesDepartmentId]);
            }

            if ($type === 'recipe') {
                if (! $recipeId) {
                    throw new \Exception("No recipe provided");
                }

                $product = Product::findOrFail($productId);
                $recipe = Recipe::findOrFail($recipeId);

                if ($request->branch_id && $recipe->branch_id !== $request->branch_id) {
                    throw new \Exception("Recipe branch mismatch");
                }

                if ($departmentId && $recipe->department_id !== $departmentId) {
                    throw new \Exception("Recipe department mismatch");
                }

                $recipe->update([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'product_type_id' => $product->product_type_id,
                ]);

                $results['recipe_updated'] = true;
            }

            return $results;
        } catch (\Exception $e) {
            throw new \Exception("Failed to execute product assignments: " . $e->getMessage());
        }
    }

    /**
     * Execute approved recipe creation
     */
    public static function executeRecipeCreation(ApprovalAuditRequest $request): ?Recipe
    {
        try {
            \Log::info('🔵 [RECIPE CREATION] Starting recipe creation', [
                'request_id' => $request->id,
                'product_name' => $request->payload['product_name'] ?? 'Unknown',
            ]);

            $payload = $request->payload;
            $actor = $request->requester;  // Use property, not method

            if (!$actor) {
                throw new \Exception("Requester not found for recipe creation");
            }

            \Log::info('✅ [RECIPE CREATION] Requester found', [
                'requester_id' => $actor->id,
                'requester_type' => get_class($actor),
            ]);

            // Get branch_id from the approval request (where it's stored), not from payload
            $branchId = $request->branch_id ?? $payload['branch_id'] ?? null;
            
            if (!$branchId) {
                throw new \Exception("Branch ID not found in approval request");
            }

            \Log::info('🔵 [RECIPE CREATION] Starting database transaction', [
                'branch_id' => $branchId,
                'ingredients_count' => count($payload['ingredients'] ?? []),
            ]);

            $recipe = DB::transaction(function () use ($payload, $actor, $branchId) {
                \Log::info('🔵 [RECIPE CREATION] Creating recipe record');
                
                \Log::info('🔵 [RECIPE CREATION] Product type ID', [
                    'product_type_id' => $payload['product_type_id'] ?? null,
                ]);
                
                $recipe = Recipe::create([
                    'branch_id' => $branchId,
                    'product_id' => $payload['product_id'] ?? null,
                    'product_name' => $payload['product_name'],
                    'sku' => strtoupper($payload['sku']),
                    'department_id' => $payload['department_id'],
                    'product_type_id' => $payload['product_type_id'],
                    'cost_per_unit' => $payload['cost_per_unit'],
                    'uom_id' => $payload['uom_id'],
                    'yield_quantity' => $payload['yield_quantity'],
                    'preparation_time' => $payload['preparation_time'] ?? null,
                    'instructions' => $payload['instructions'] ?? null,
                    'status' => $payload['status'] ?? 'active',
                    'created_by_id' => $actor->id,
                    'created_by_type' => get_class($actor),
                ]);

                \Log::info('✅ [RECIPE CREATION] Recipe record created', [
                    'recipe_id' => $recipe->id,
                    'sku' => $recipe->sku,
                ]);

                // Save ingredients
                if (!empty($payload['ingredients']) && is_array($payload['ingredients'])) {
                    \Log::info('🔵 [RECIPE CREATION] Creating ingredients', [
                        'count' => count($payload['ingredients']),
                    ]);

                    foreach ($payload['ingredients'] as $index => $ingredient) {
                        if (!empty($ingredient['item_id'])) {
                            try {
                                RecipeIngredient::create([
                                    'recipe_id' => $recipe->id,
                                    'item_id' => $ingredient['item_id'],
                                    'quantity' => $ingredient['quantity'],
                                    'uom_id' => $ingredient['uom_id'],
                                    'cost_per_unit' => $ingredient['cost_per_unit'] ?? 0,
                                    'waste_percentage' => $ingredient['waste_percentage'] ?? 0,
                                    'sort_order' => $index + 1,
                                    'notes' => $ingredient['notes'] ?? null,
                                    'preparation_notes' => $ingredient['preparation_notes'] ?? null,
                                ]);
                                \Log::info('✅ [RECIPE CREATION] Ingredient created', [
                                    'index' => $index,
                                    'item_id' => $ingredient['item_id'],
                                ]);
                            } catch (\Exception $e) {
                                \Log::error('❌ [RECIPE CREATION] Failed to create ingredient', [
                                    'index' => $index,
                                    'item_id' => $ingredient['item_id'],
                                    'error' => $e->getMessage(),
                                ]);
                                throw new \Exception("Failed to create ingredient at index {$index}: " . $e->getMessage());
                            }
                        }
                    }
                }

                // Log recipe creation
                \Log::info('🔵 [RECIPE CREATION] Logging recipe creation audit');
                ProductionAuditService::logRecipeCreated($actor, $recipe, $payload['ingredients'] ?? []);

                \Log::info('✅ [RECIPE CREATION] Recipe creation audit logged');
                return $recipe;
            });

            \Log::info('✅ [RECIPE CREATION] Recipe created successfully', [
                'recipe_id' => $recipe->id,
                'product_name' => $recipe->product_name,
            ]);

            return $recipe;
        } catch (\Exception $e) {
            \Log::error('❌ [RECIPE CREATION] Recipe creation failed', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to create recipe: " . $e->getMessage());
        }
    }

    /**
     * Execute approved recipe update
     */
    public static function executeRecipeUpdate(ApprovalAuditRequest $request): ?Recipe
    {
        try {
            \Log::info('🔵 [RECIPE UPDATE] Starting recipe update', [
                'request_id' => $request->id,
            ]);

            $payload = $request->payload;
            $recipeId = $payload['id'] ?? null;
            $actor = $request->requester;  // Use property, not method

            if (!$recipeId) {
                throw new \Exception("Recipe ID not found in approval request");
            }

            if (!$actor) {
                throw new \Exception("Requester not found for recipe update");
            }

            \Log::info('✅ [RECIPE UPDATE] Validation passed', [
                'recipe_id' => $recipeId,
                'requester_id' => $actor->id,
            ]);

            \Log::info('🔵 [RECIPE UPDATE] Fetching recipe', ['recipe_id' => $recipeId]);
            $recipe = Recipe::findOrFail($recipeId);

            \Log::info('✅ [RECIPE UPDATE] Recipe found, starting transaction');
            DB::transaction(function () use ($recipe, $payload, $actor) {
                \Log::info('🔵 [RECIPE UPDATE] Updating recipe fields');
                
                // Update basic recipe fields
                $recipe->update([
                    'product_id' => $payload['product_id'] ?? null,
                    'product_name' => $payload['product_name'],
                    'sku' => strtoupper($payload['sku']),
                    'department_id' => $payload['department_id'],
                    'product_type_id' => $payload['product_type_id'],
                    'cost_per_unit' => $payload['cost_per_unit'],
                    'uom_id' => $payload['uom_id'],
                    'yield_quantity' => $payload['yield_quantity'],
                    'preparation_time' => $payload['preparation_time'] ?? null,
                    'instructions' => $payload['instructions'] ?? null,
                    'status' => $payload['status'] ?? 'active',
                ]);

                // Update ingredients - delete old ones and create new ones
                if (!empty($payload['ingredients']) && is_array($payload['ingredients'])) {
                    $recipe->ingredients()->delete();
                    
                    foreach ($payload['ingredients'] as $index => $ingredient) {
                        if (!empty($ingredient['item_id'])) {
                            // Skip 'id' field if present in payload from edit form
                            $ingredientData = [
                                'recipe_id' => $recipe->id,
                                'item_id' => $ingredient['item_id'],
                                'quantity' => $ingredient['quantity'],
                                'uom_id' => $ingredient['uom_id'],
                                'cost_per_unit' => $ingredient['cost_per_unit'] ?? 0,
                                'waste_percentage' => $ingredient['waste_percentage'] ?? 0,
                                'sort_order' => $index + 1,
                                'notes' => $ingredient['notes'] ?? null,
                                'preparation_notes' => $ingredient['preparation_notes'] ?? null,
                            ];
                            RecipeIngredient::create($ingredientData);
                        }
                    }
                }

                // Log recipe update
                ProductionAuditService::logRecipeUpdated($actor, $recipe, $payload['ingredients'] ?? []);
            });

            \Log::info('✅ [RECIPE UPDATE] Recipe updated successfully', [
                'recipe_id' => $recipe->id,
            ]);

            return $recipe;
        } catch (\Exception $e) {
            \Log::error('❌ [RECIPE UPDATE] Recipe update failed', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to update recipe: " . $e->getMessage());
        }
    }

    /**
     * Execute approved recipe deletion
     */
    public static function executeRecipeDeletion(ApprovalAuditRequest $request): ?Recipe
    {
        try {
            \Log::info('🔵 [RECIPE DELETION] Starting recipe deletion', [
                'request_id' => $request->id,
            ]);

            $recipeId = $request->payload['id'] ?? null;
            $actor = $request->requester;  // Use property, not method

            if (!$recipeId) {
                throw new \Exception("Recipe ID not found in approval request");
            }

            if (!$actor) {
                throw new \Exception("Requester not found for recipe deletion");
            }

            \Log::info('✅ [RECIPE DELETION] Validation passed', [
                'recipe_id' => $recipeId,
                'requester_id' => $actor->id,
            ]);

            \Log::info('🔵 [RECIPE DELETION] Fetching recipe', ['recipe_id' => $recipeId]);
            $recipe = Recipe::findOrFail($recipeId);

            \Log::info('✅ [RECIPE DELETION] Recipe found, starting deletion', [
                'product_name' => $recipe->product_name,
            ]);

            DB::transaction(function () use ($recipe, $actor) {
                \Log::info('🔵 [RECIPE DELETION] Logging deletion');
                // Log deletion before deleting
                if ($actor) {
                    ProductionAuditService::logRecipeDeleted(
                        $actor,
                        $recipe,
                        'Approved deletion request'
                    );
                }

                \Log::info('🔵 [RECIPE DELETION] Deleting ingredients and recipe');
                // Delete ingredients and recipe
                $recipe->ingredients()->delete();
                $recipe->delete();
                \Log::info('✅ [RECIPE DELETION] Recipe deleted');
            });

            \Log::info('✅ [RECIPE DELETION] Recipe deletion completed', [
                'recipe_id' => $recipeId,
            ]);

            return $recipe;
        } catch (\Exception $e) {
            \Log::error('❌ [RECIPE DELETION] Recipe deletion failed', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to delete recipe: " . $e->getMessage());
        }
    }

    /**
     * Execute approved bulk recipe deletion
     */
    public static function executeRecipeBulkDeletion(ApprovalAuditRequest $request): array
    {
        try {
            \Log::info('🔵 [RECIPE BULK DELETION] Starting bulk recipe deletion', [
                'request_id' => $request->id,
            ]);

            $ids = $request->payload['ids'] ?? [];
            $actor = $request->requester;

            if (empty($ids)) {
                throw new \Exception("No recipe IDs found in approval request");
            }

            if (!$actor) {
                throw new \Exception("Requester not found for recipe bulk deletion");
            }

            \Log::info('✅ [RECIPE BULK DELETION] Validation passed', [
                'recipe_count' => count($ids),
                'requester_id' => $actor->id,
            ]);

            $deleted = [];
            foreach ($ids as $id) {
                \Log::info('🔵 [RECIPE BULK DELETION] Processing recipe', ['recipe_id' => $id]);
                $recipe = Recipe::find($id);
                if ($recipe) {
                    DB::transaction(function () use ($recipe, $actor) {
                        \Log::info('🔵 [RECIPE BULK DELETION] Logging deletion');
                        // Log deletion before deleting
                        if ($actor) {
                            ProductionAuditService::logRecipeDeleted(
                                $actor,
                                $recipe,
                                'Approved bulk deletion request'
                            );
                        }

                        \Log::info('🔵 [RECIPE BULK DELETION] Deleting ingredients and recipe');
                        // Delete ingredients and recipe
                        $recipe->ingredients()->delete();
                        $recipe->delete();
                        \Log::info('✅ [RECIPE BULK DELETION] Recipe deleted');
                    });

                    $deleted[] = $id;
                    \Log::info('✅ [RECIPE BULK DELETION] Recipe processed', ['recipe_id' => $id]);
                } else {
                    \Log::warning('⚠️ [RECIPE BULK DELETION] Recipe not found', ['recipe_id' => $id]);
                }
            }

            \Log::info('✅ [RECIPE BULK DELETION] Bulk deletion completed', [
                'total_ids' => count($ids),
                'deleted_count' => count($deleted),
            ]);

            return $deleted;
        } catch (\Exception $e) {
            \Log::error('❌ [RECIPE BULK DELETION] Bulk deletion failed', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to delete recipes: " . $e->getMessage());
        }
    }

    /**
     * Create approval request for product creation
     */
    public static function requestProductCreation($requester, array $productData, string $reason): ApprovalAuditRequest
    {
        return ApprovalAuditRequest::create([
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'product:create',
            'description' => $reason,
            'payload' => $productData,
            'status' => 'pending',
        ]);
    }

    /**
     * Create approval request for product update
     */
    public static function requestProductUpdate($requester, array $productData, string $reason): ApprovalAuditRequest
    {
        return ApprovalAuditRequest::create([
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'product:edit',
            'description' => $reason,
            'payload' => $productData,
            'status' => 'pending',
        ]);
    }

    /**
     * Create approval request for product deletion
     */
    public static function requestProductDeletion($requester, int $productId, string $reason): ApprovalAuditRequest
    {
        return ApprovalAuditRequest::create([
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'product:delete',
            'description' => $reason,
            'payload' => ['id' => $productId],
            'status' => 'pending',
        ]);
    }

    /**
     * Create approval request for recipe creation
     */
    public static function requestRecipeCreation($requester, array $recipeData, string $reason): ApprovalAuditRequest
    {
        return ApprovalAuditRequest::create([
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'recipe:create_recipe',
            'description' => $reason,
            'payload' => $recipeData,
            'status' => 'pending',
        ]);
    }

    /**
     * Create approval request for recipe deletion
     */
    public static function requestRecipeDeletion($requester, int $recipeId, string $reason): ApprovalAuditRequest
    {
        return ApprovalAuditRequest::create([
            'requester_id' => $requester->id,
            'requester_type' => get_class($requester),
            'action' => 'recipe:delete_recipe',
            'description' => $reason,
            'payload' => ['id' => $recipeId],
            'status' => 'pending',
        ]);
    }
}
