<?php

namespace App\Traits;

use App\Models\ApprovalAuditRequest;
use App\Helpers\CleanError;
use App\Services\AuditService;
use Illuminate\Support\Str;

/**
 * Universal Approval Workflow Trait
 *
 * Add this trait to any Livewire component to instantly gain approval workflow
 * capability for ANY resource type (items, products, recipes, stock, suppliers, etc.).
 *
 * Features:
 * - Super admin bypass (executes immediately, no approval needed)
 * - Regular user approval (requires supervisor/admin approval)
 * - One method call for all actions: create, update, delete, adjust
 * - Automatic reason modal for non-super-admins
 * - Polymorphic actor support (Users & Employees)
 * - Branch context aware
 * - Complete audit trail integration
 *
 * Usage:
 * ```php
 * class ItemsComponent extends Component {
 *     use RequiresApprovalWorkflow;
 *
 *     public function saveItem() {
 *         $this->submitForApproval('create', 'item', null, $itemData, 'New item for stock');
 *     }
 * }
 * ```
 *
 * @see MDs/UNIVERSAL_APPROVAL_WORKFLOW_IMPLEMENTATION.md for detailed docs
 */
trait RequiresApprovalWorkflow
{
    /**
     * Determine if current user is super admin
      * Works with unified web guard system
     *
     * @return bool
     */
    protected function isSuperAdmin(): bool
    {
        $user = auth()->user() ?? auth()->user();
        return $user && ($user->is_super_admin ?? false);
    }

    /**
     * Get current authenticated actor (User or Employee)
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getCurrentActor()
    {
        return auth()->user() ?? auth()->user();
    }

    /**
     * Get current branch ID for context
     *
     * @return int|null
     */
    protected function getCurrentBranchId(): ?int
    {
        $user = auth()->user() ?? auth()->user();
        return $user?->branch_id;
    }

    /**
     * Submit an action for approval or execute immediately if super admin
     *
     * This is the main entry point for all approval workflows.
     * For super admins: action executes immediately, logged, done.
     * For regular users: approval request created, reason modal shown, waits for approval.
     *
     * @param string $action            The action type: 'create', 'update', 'delete', 'adjust'
     * @param string $resourceType      The resource type: 'item', 'product', 'recipe', 'stock', etc.
     * @param string|null $resourceId   The resource ID (null for create, UUID for others)
     * @param array $payload            The exact data to apply when action executes
     * @param string $reason            Why this change is being requested
     *
     * @return array [
     *     'approved' => bool,         // true if super admin, false if approval needed
     *     'request_id' => int|null,   // null if super admin, request ID if approval needed
     *     'message' => string         // User-friendly message
     * ]
     *
     * @throws \Exception if validation fails
     *
     * @example
     * // Create action
     * $this->submitForApproval('create', 'item', null, [
     *     'name' => 'Sugar 50kg',
     *     'sku' => 'SUGAR-50',
     *     'cost' => 5000,
     * ], 'New item for bakery section');
     *
     * @example
     * // Update action
     * $this->submitForApproval('update', 'product', $productId, [
     *     'price' => 5500,
     * ], 'Price adjustment after cost review');
     *
     * @example
     * // Delete action
     * $this->submitForApproval('delete', 'supplier', $supplierId, [
     *     'id' => $supplierId,
     *     'name' => 'Old Supplier Ltd',
     * ], 'Discontinuing supplier, switching to new vendor');
     *
     * @example
     * // Adjust action (stock/price/quantity)
     * $this->submitForApproval('adjust', 'stock', $stockId, [
     *     'quantity' => 500,
     *     'previous_quantity' => 200,
     * ], 'Stock recount after physical verification');
     */
    protected function submitForApproval(
        string $action,
        string $resourceType,
        ?string $resourceId,
        array $payload,
        string $reason
    ): array {
        $actor = $this->getCurrentActor();
        $branchId = $this->getCurrentBranchId();

        if (!$actor) {
            $userMessage = 'No authenticated user found. Please log in again.';
            CleanError::show($userMessage, null, [
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'branch_id' => $branchId,
            ], $this);

            return [
                'approved' => false,
                'request_id' => null,
                'message' => $userMessage,
            ];
        }

        // SUPER ADMIN: Execute immediately, no approval needed
        if ($this->isSuperAdmin()) {
            try {
                $this->executeAction($action, $resourceType, $resourceId, $payload);

                // Log the auto-approved action
                AuditService::log(
                    $actor,
                    $action,
                    null,
                    "Auto-approved (super admin): {$reason}",
                    'completed'
                );

                return [
                    'approved' => true,
                    'request_id' => null,
                    'message' => ucfirst($action) . ' completed immediately'
                ];
            } catch (\Throwable $e) {
                AuditService::log(
                    $actor,
                    "{$action}_failed",
                    null,
                    "Super admin execution failed: {$e->getMessage()}",
                    'failed'
                );

                $userMessage = ucfirst($action) . ' failed. Please try again.';
                CleanError::show($userMessage, $e, [
                    'action' => $action,
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'branch_id' => $branchId,
                    'actor_id' => $actor->id,
                    'actor_type' => get_class($actor),
                ], $this);

                return [
                    'approved' => false,
                    'request_id' => null,
                    'message' => $userMessage,
                ];
            }
        }

        // REGULAR USER: Create approval request
        try {
            $request = ApprovalAuditRequest::create([
                'branch_id' => $branchId,
                'requester_id' => $actor->id,
                'requester_type' => get_class($actor),
                'action' => "{$action}:{$resourceType}",
                'description' => $reason,
                'payload' => $payload,
                'status' => 'pending',
            ]);

            // Log the request creation
            AuditService::log(
                $actor,
                "request_{$action}",
                null,
                "Pending approval: {$reason}",
                'pending'
            );

            return [
                'approved' => false,
                'request_id' => $request->id,
                'message' => 'Your request has been submitted for approval'
            ];
        } catch (\Throwable $e) {
                AuditService::log(
                    $actor,
                    "request_{$action}_failed",
                    null,
                    "Request creation failed: {$e->getMessage()}",
                    'failed'
                );

                $userMessage = 'Unable to submit request for approval. Please try again.';
                CleanError::show($userMessage, $e, [
                    'action' => $action,
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'branch_id' => $branchId,
                    'actor_id' => $actor->id,
                    'actor_type' => get_class($actor),
                ], $this);

                return [
                    'approved' => false,
                    'request_id' => null,
                'message' => $userMessage,
            ];
        }
    }

    /**
     * Execute an action immediately
     *
     * This is called either:
     * 1. By submitForApproval() if user is super admin
     * 2. By approval handler (AuditManagement) when request is approved
     *
     * Dispatches to specific handlers based on action type.
     *
     * @param string $action         'create', 'update', 'delete', 'adjust'
     * @param string $resourceType   'item', 'product', 'recipe', 'stock', etc.
     * @param string|null $resourceId  null for create, UUID for others
     * @param array $payload         Exact data to apply
     *
     * @throws \Exception if action type is unknown
     */
    protected function executeAction(
        string $action,
        string $resourceType,
        ?string $resourceId,
        array $payload
    ): void {
        match ($action) {
            'create' => $this->handleCreate($resourceType, $payload),
            'update' => $this->handleUpdate($resourceType, $resourceId, $payload),
            'delete' => $this->handleDelete($resourceType, $resourceId),
            'adjust' => $this->handleAdjust($resourceType, $resourceId, $payload),
            default => throw new \Exception("Unknown action: {$action}")
        };
    }

    /**
     * Handle CREATE action for any resource type
     *
     * Creates a new model instance with provided data.
     * Only fillable fields are included.
     * Automatically adds branch_id if needed.
     * Generates slug if applicable (e.g., for departments).
     *
     * @param string $resourceType   'item', 'product', 'recipe', etc.
     * @param array $payload         Data to create with
     *
     * @throws \Exception if model class not found or creation fails
     */
    protected function handleCreate(string $resourceType, array $payload): void
    {
        $modelClass = $this->getModelClass($resourceType);

        try {
            $model = new $modelClass();
            $fillable = $model->getFillable();

            // Only include fillable fields
            $createData = array_intersect_key($payload, array_flip($fillable));

            // Add branch_id if the model has it but it's not in payload
            if (in_array('branch_id', $fillable) && !isset($createData['branch_id'])) {
                $createData['branch_id'] = $this->getCurrentBranchId();
            }

            // Generate slug if needed (e.g., for departments)
            if (isset($createData['name']) && in_array('slug', $fillable) && !isset($createData['slug'])) {
                $createData['slug'] = Str::slug($createData['name']);
            }

            // Create the model
            $modelClass::create($createData);
        } catch (\Exception $e) {
            throw new \Exception("Failed to create {$resourceType}: " . $e->getMessage());
        }
    }

    /**
     * Handle UPDATE action for any resource type
     *
     * Updates an existing model with new data.
     * Only fillable fields are updated.
     * Resource must exist or throws exception.
     *
     * @param string $resourceType    'item', 'product', 'recipe', etc.
     * @param string|null $resourceId UUID of the resource
     * @param array $payload          Data to update with
     *
     * @throws \Exception if resource not found or update fails
     */
    protected function handleUpdate(string $resourceType, ?string $resourceId, array $payload): void
    {
        if (!$resourceId) {
            throw new \Exception("Resource ID required for update");
        }

        try {
            $modelClass = $this->getModelClass($resourceType);
            $model = $modelClass::find($resourceId);

            if (!$model) {
                throw new \Exception("{$resourceType} with ID {$resourceId} not found");
            }

            $fillable = $model->getFillable();
            $updateData = array_intersect_key($payload, array_flip($fillable));

            if (!empty($updateData)) {
                $model->update($updateData);
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to update {$resourceType}: " . $e->getMessage());
        }
    }

    /**
     * Handle DELETE action for any resource type
     *
     * Soft or hard deletes a model based on its configuration.
     * Resource must exist or throws exception.
     *
     * @param string $resourceType   'item', 'product', 'recipe', etc.
     * @param string|null $resourceId UUID of the resource
     *
     * @throws \Exception if resource not found or deletion fails
     */
    protected function handleDelete(string $resourceType, ?string $resourceId): void
    {
        if (!$resourceId) {
            throw new \Exception("Resource ID required for delete");
        }

        try {
            $modelClass = $this->getModelClass($resourceType);
            $model = $modelClass::find($resourceId);

            if (!$model) {
                throw new \Exception("{$resourceType} with ID {$resourceId} not found");
            }

            $model->delete();
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete {$resourceType}: " . $e->getMessage());
        }
    }

    /**
     * Handle ADJUST action - for stock, price, quantity adjustments
     *
     * Default implementation handles:
     * - Stock: adjusts quantity
     * - Product: adjusts price
     * - Item: adjusts cost
     *
     * Override this method in your component for custom adjust logic.
     *
     * @param string $resourceType   'item', 'product', 'stock', etc.
     * @param string|null $resourceId UUID of the resource
     * @param array $payload         Data containing the adjustment
     *
     * @throws \Exception if resource not found or adjustment fails
     *
     * @example
     * // Stock adjustment
     * [
     *     'quantity' => 500,
     *     'previous_quantity' => 200,
     *     'adjustment' => 300,
     * ]
     *
     * @example
     * // Price adjustment
     * [
     *     'price' => 5500,
     *     'previous_price' => 5000,
     * ]
     */
    protected function handleAdjust(string $resourceType, ?string $resourceId, array $payload): void
    {
        if (!$resourceId) {
            throw new \Exception("Resource ID required for adjust");
        }

        try {
            $modelClass = $this->getModelClass($resourceType);
            $model = $modelClass::find($resourceId);

            if (!$model) {
                throw new \Exception("{$resourceType} with ID {$resourceId} not found");
            }

            // Resource-specific adjust logic
            switch ($resourceType) {
                case 'stock':
                    if (isset($payload['quantity'])) {
                        $model->update(['quantity' => $payload['quantity']]);
                    }
                    break;

                case 'product':
                    if (isset($payload['price'])) {
                        $model->update(['price' => $payload['price']]);
                    }
                    if (isset($payload['cost'])) {
                        $model->update(['cost' => $payload['cost']]);
                    }
                    break;

                case 'item':
                    if (isset($payload['cost'])) {
                        $model->update(['cost' => $payload['cost']]);
                    }
                    if (isset($payload['price'])) {
                        $model->update(['price' => $payload['price']]);
                    }
                    break;

                case 'recipe':
                    if (isset($payload['description'])) {
                        $model->update(['description' => $payload['description']]);
                    }
                    break;

                default:
                    // Generic adjust: try to update any changed fields
                    if (!empty($payload)) {
                        $fillable = $model->getFillable();
                        $updateData = array_intersect_key($payload, array_flip($fillable));
                        if (!empty($updateData)) {
                            $model->update($updateData);
                        }
                    }
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to adjust {$resourceType}: " . $e->getMessage());
        }
    }

    /**
     * Map resource type strings to model classes
     *
     * Add new resource types here as needed.
     * The key is what you use in submitForApproval('action', 'key', ...)
     * The value is the full model class name.
     *
     * @param string $resourceType  Lowercase resource type identifier
     *
     * @return string Full model class name (e.g., \App\Models\Item::class)
     *
     * @throws \Exception if resource type is not registered
     */
    protected function getModelClass(string $resourceType): string
    {
        $models = [
            'item' => \App\Models\Item::class,
            'product' => \App\Models\Product::class,
            'recipe' => \App\Models\Recipe::class,
            'stock' => \App\Models\Stock::class,
            'supplier' => \App\Models\Supplier::class,
            'department' => \App\Models\Department::class,
            'employee' => \App\Models\Employee::class,
            'production_record' => \App\Models\ProductionRecord::class,
            'daily_produce' => \App\Models\DailyProduce::class,
            'product_dispatch' => \App\Models\ProductDispatch::class,
            'shift' => \App\Models\Shift::class,
            'branch' => \App\Models\Branch::class,
        ];

        if (!isset($models[$resourceType])) {
            throw new \Exception(
                "Unknown resource type: '{$resourceType}'. Registered types: "
                . implode(', ', array_keys($models))
            );
        }

        return $models[$resourceType];
    }

    /**
     * Register a custom resource type to model mapping
     *
     * Call this in your component if you have custom models not listed above.
     *
     * @param string $resourceType The resource type identifier
     * @param string $modelClass   Full model class name
     *
     * @example
     * public function mount() {
     *     $this->registerResourceType('custom_widget', \App\Models\CustomWidget::class);
     * }
     *
     * Note: Currently this won't work because getModelClass is static-like.
     * Override getModelClass in your component if you need custom types.
     */
    protected function registerResourceType(string $resourceType, string $modelClass): void
    {
        // To use this, override getModelClass in your component
        // This is a helper documentation for developers
    }
}
