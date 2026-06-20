<?php

namespace App\Livewire\BranchDashboard\Inventory;

use App\Livewire\BaseComponent;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\RecipeIngredient;
use App\Models\Stock;
use App\Models\UnitOfMeasure;
use App\Services\AuditService;
use App\Services\InventoryApprovalService;
use App\Services\SidebarVisibilityService;
use App\Traits\Exportable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app.branch-dashboard')]
class Items extends BaseComponent
{
    use Exportable, WithFileUploads;

    // CSV unit-price import state
    public $importFile;

    public bool $showImportModal = false;

    public array $importResult = [];

    #[Url(keep: true)]
    public ?string $b_id = null;

    public function mount()
    {
        $this->b_id = current_branch_id();
    }

    #[On('branch-changed')]
    public function handleBranchChange($branchId)
    {
        $this->b_id = $branchId;
        $this->resetPage();
        $this->resetFilters();
    }

    public ?int $quantity = 10;

    public ?string $search = null;

    public ?string $advancedSearch = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    // Filters
    public ?int $filterCategory = null;

    public ?string $filterStatus = null;

    // Form Fields
    public ?int $itemId = null;

    public string $name = '';

    public string $sku = '';

    public ?int $category_id = null;

    public ?int $uom_id = null;

    public ?float $reorder_level = null;

    public ?float $max_stock_level = null;

    public ?float $unit_price = null;

    public ?float $sell_price = null;

    public string $status = 'active';

    public bool $requires_request = false;

    public bool $isEditing = false;

    // Modals
    public bool $showModal = false;

    public bool $showAuditModal = false;

    public ?string $auditAction = null;

    public string $auditReason = '';

    public ?int $pendingItemId = null;

    // Temporary storage for pending item data (after Save, before audit)
    public array $pendingItemData = [];

    // Deletion data for audit
    public array $relatedDataToDelete = [];

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDeleteItems'],
    ];

    protected function getModelClass(): string
    {
        return Item::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id ?? current_branch_id() ?? request()->query('b_id');
    }

    // ===================================================================
    // MAIN SAVE METHOD — This is the key fix
    // ===================================================================
    // Then in your save() method — this will now work perfectly
    public function save()
    {
        $this->validate($this->itemValidationRules());

        $data = [
            'branch_id' => $this->getBranchId(),
            'name' => $this->name,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'uom_id' => $this->uom_id,
            'reorder_level' => $this->reorder_level ?? 0,
            'max_stock_level' => $this->max_stock_level ?? 0,
            'unit_price' => $this->unit_price ?? 0,
            'last_unit_price' => $this->unit_price ?? 0,
            'sell_price' => $this->sell_price ?: null,
            'status' => $this->status,
            'requires_request' => (bool) $this->requires_request,
        ];

        // Only Super Admin level users can create items directly without approval
        $user = Auth::user();
        $roleLevel = SidebarVisibilityService::getRoleLevel($user);

        if ($roleLevel >= \App\Services\SidebarVisibilityService::LEVEL_SUPER_ADMIN) {
            $this->executeImmediateSave($data);

            return;
        }

        // This now works — because $pendingItemData is public!
        $this->pendingItemData = $data;
        $this->auditAction = $this->isEditing ? 'update_item' : 'create_item';
        $this->auditReason = '';
        $this->showModal = false;
        $this->showAuditModal = true;
    }

    private function itemValidationRules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:item_categories,id',
            'uom_id' => 'required|exists:units_of_measure,id',
            'reorder_level' => 'nullable|numeric|min:0',        // ← FIXED
            'max_stock_level' => 'nullable|numeric|min:0',      // ← also make sure this one is correct
            'unit_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'requires_request' => 'required|boolean',
        ];

        if ($this->isEditing) {
            $rules['sku'] = 'required|string|max:255|unique:items,sku,'.$this->itemId;
        } else {
            $rules['sku'] = 'required|string|max:255|unique:items,sku';
        }

        return $rules;
    }

    private function executeImmediateSave(array $data)
    {
        if ($this->isEditing && $this->itemId) {
            $item = Item::where('id', $this->itemId)
                ->where('branch_id', $this->getBranchId())
                ->firstOrFail();

            // Store original values for change tracking
            $oldName = $item->name;
            $oldCategory = $item->category?->name ?? 'None';
            $oldUom = $item->unitOfMeasure?->symbol;
            $oldReorderLevel = $item->reorder_level;
            $oldMaxStockLevel = $item->max_stock_level;
            $oldUnitPrice = (float) ($item->unit_price ?? 0);
            $oldStatus = $item->status;
            $oldRequiresRequest = (bool) $item->requires_request;

            $item->update($data);

            // Get new category name
            $newCategory = ItemCategory::find($this->category_id)?->name ?? 'None';

            // Log the item update with changes
            $changes = [];
            if ($oldName !== $this->name) {
                $changes[] = "Name: {$oldName} → {$this->name}";
            }
            if ($oldCategory !== $newCategory) {
                $changes[] = "Category: {$oldCategory} → {$newCategory}";
            }
            $newUom = UnitOfMeasure::find($this->uom_id)?->symbol;
            if ($oldUom !== $newUom) {
                $changes[] = "UOM: {$oldUom} → {$newUom}";
            }
            if ((float) $oldReorderLevel !== (float) $this->reorder_level) {
                $changes[] = "Reorder Level: {$oldReorderLevel} → {$this->reorder_level}";
            }
            if ((float) $oldMaxStockLevel !== (float) $this->max_stock_level) {
                $changes[] = "Max Stock: {$oldMaxStockLevel} → {$this->max_stock_level}";
            }
            if ((float) $oldUnitPrice !== (float) ($this->unit_price ?? 0)) {
                $changes[] = "Unit Price: {$oldUnitPrice} → ".($this->unit_price ?? 0);
            }
            if ($oldStatus !== $this->status) {
                $changes[] = "Status: {$oldStatus} → {$this->status}";
            }
            if ($oldRequiresRequest !== (bool) $this->requires_request) {
                $changes[] = 'Requires Request: '.($oldRequiresRequest ? 'Yes' : 'No').' → '.($this->requires_request ? 'Yes' : 'No');
            }

            AuditService::log(
                current_actor(),
                'update',
                $item,
                "Updated item '{$item->name}' (SKU: {$item->sku}). Changes: ".implode(', ', $changes),
                'completed'
            );

            $this->toast()->success('Item updated successfully!')->send();
        } else {
            $item = Item::create($data);

            Stock::create([
                'branch_id' => $item->branch_id,
                'item_id' => $item->id,
                'quantity_available' => 0,
                'quantity_reserved' => 0,
                'quantity_damaged' => 0,
                'average_cost' => 0,
                'last_stock_take_date' => now(),
                'health_status' => 'good',
            ]);

            // Log the item creation
            AuditService::log(
                current_actor(),
                'create',
                $item,
                sprintf(
                    "Created item '%s' (SKU: %s) in category '%s'. UOM: %s, Reorder Level: %s, Max Stock: %s, Unit Price: %s, Requires Request: %s",
                    $item->name,
                    $item->sku,
                    $item->category?->name ?? 'None',
                    $item->unitOfMeasure?->symbol ?? 'N/A',
                    $item->reorder_level,
                    $item->max_stock_level,
                    $item->unit_price,
                    $item->requires_request ? 'Yes' : 'No'
                ),
                'completed'
            );

            $this->toast()->success('Item created successfully!')->send();
        }

        $this->closeModal();
    }

    // ===================================================================
    // AUDIT SUBMISSION — After employee enters reason
    // ===================================================================
    public function submitAuditRequest()
    {
        $this->validate([
            'auditReason' => 'required|string|min:10|max:500',
        ]);

        try {
            $user = current_actor();
            $request = null;
            $msg = '';

            if ($this->auditAction === 'create_item') {
                // Ensure we have pending item data
                if (empty($this->pendingItemData)) {
                    throw new \Exception('No item data found. Please fill in the form and save first.');
                }
                $request = InventoryApprovalService::requestItemCreation(
                    $user,
                    $this->pendingItemData,
                    $this->auditReason
                );
                $msg = 'Item creation request submitted for approval!';
            } elseif ($this->auditAction === 'update_item') {
                // Ensure we have pending item data
                if (empty($this->pendingItemData)) {
                    throw new \Exception('No item data found. Please fill in the form and save first.');
                }
                $request = InventoryApprovalService::requestItemUpdate(
                    $user,
                    $this->itemId,
                    $this->pendingItemData,
                    $this->auditReason
                );
                $msg = 'Item update request submitted for approval!';
            } else {
                throw new \Exception('Unknown audit action: '.$this->auditAction);
            }

            // Verify request was created
            if (! $request || ! $request->id) {
                throw new \Exception('Failed to create approval request');
            }

            $this->toast()->success($msg.' (Request ID: '.$request->id.')')->send();
            $this->closeAuditModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->toast()->error('Failed: '.$e->getMessage())->send();
        }
    }

    // ===================================================================
    // MODAL OPENERS
    // ===================================================================
    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $branchId = $this->getBranchId();
        $item = Item::where('id', $id)->where('branch_id', $branchId)->firstOrFail();

        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->sku = $item->sku;
        $this->category_id = $item->category_id;
        $this->uom_id = $item->uom_id;
        $this->reorder_level = $item->reorder_level;
        $this->max_stock_level = $item->max_stock_level;
        $this->unit_price = (float) ($item->unit_price ?? 0);
        $this->sell_price = $item->sell_price ? (float) $item->sell_price : null;
        $this->status = $item->status;
        $this->requires_request = (bool) $item->requires_request;
        $this->isEditing = true;
        $this->showModal = true;
    }

    // ===================================================================
    // DELETE (with audit)
    // ===================================================================
    public function delete($id)
    {
        $this->pendingItemId = $id;

        $branchId = $this->getBranchId();
        $item = Item::where('id', $id)
            ->where('branch_id', $branchId)
            ->firstOrFail();

        // Gather related data that will be deleted
        $this->relatedDataToDelete = $this->gatherRelatedDataForDeletion($item);

        if (should_bypass_approval()) {
            $this->showDeletionConfirmationDialog($item);
        } else {
            $this->auditAction = 'delete_item';
            $this->showAuditModal = true;
        }
    }

    private function gatherRelatedDataForDeletion($item): array
    {
        $relatedData = [
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
            ],
            'recipes' => [],
            'products' => [],
            'stocks' => [],
            'purchases' => [],
            'stock_movements' => [],
        ];

        // Get recipes that use this item as an ingredient
        // Use RecipeIngredient to find recipes (inverse relationship)
        $recipeIngredients = RecipeIngredient::where('item_id', $item->id)
            ->with('recipe.product')
            ->get();

        if ($recipeIngredients->count() > 0) {
            $recipes = $recipeIngredients->map(fn ($ri) => [
                'id' => $ri->recipe->id,
                'name' => $ri->recipe->product_name ?? $ri->recipe->product?->name ?? 'Unknown Recipe',
                'product_id' => $ri->recipe->product_id,
                'product_name' => $ri->recipe->product?->name ?? 'No Product',
                'quantity' => $ri->quantity,
                'uom' => $ri->uom,
            ])
                ->unique('id')
                ->values();

            $relatedData['recipes'] = [
                'count' => $recipes->count(),
                'items' => $recipes->toArray(),
                'description' => "Used in {$recipes->count()} recipe(s)",
            ];

            // Get affected products
            $affectedProducts = $recipeIngredients->map(fn ($ri) => $ri->recipe->product)
                ->filter()
                ->unique('id')
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ])
                ->values();

            if ($affectedProducts->count() > 0) {
                $relatedData['products'] = [
                    'count' => $affectedProducts->count(),
                    'items' => $affectedProducts->toArray(),
                    'description' => "Recipes for {$affectedProducts->count()} product(s) will be affected",
                ];
            }
        }

        // Get stocks for this item
        $stocks = $item->stocks()
            ->get()
            ->map(fn ($stock) => [
                'id' => $stock->id,
                'branch_name' => $stock->branch->name ?? 'Unknown',
                'quantity_available' => $stock->quantity_available,
                'quantity_reserved' => $stock->quantity_reserved,
                'quantity_damaged' => $stock->quantity_damaged,
            ]);

        if ($stocks->count() > 0) {
            $relatedData['stocks'] = [
                'count' => $stocks->count(),
                'items' => $stocks->toArray(),
                'description' => "Stock records in {$stocks->count()} branch(es)",
            ];
        }

        // Get purchases containing this item
        $purchases = $item->purchaseItems()
            ->with('purchase')
            ->get()
            ->map(fn ($pi) => [
                'purchase_id' => $pi->purchase->id,
                'purchase_number' => $pi->purchase->purchase_number ?? 'N/A',
                'quantity' => $pi->quantity,
                'status' => $pi->purchase->status,
            ]);

        if ($purchases->count() > 0) {
            $relatedData['purchases'] = [
                'count' => $purchases->count(),
                'items' => $purchases->toArray(),
                'description' => "Referenced in {$purchases->count()} purchase order(s)",
            ];
        }

        // Get stock movements for this item
        $stockMovements = $item->stocks()
            ->with(['stockMovements'])
            ->get()
            ->flatMap(fn ($stock) => $stock->stockMovements)
            ->count();

        if ($stockMovements > 0) {
            $relatedData['stock_movements'] = [
                'count' => $stockMovements,
                'description' => "{$stockMovements} stock movement record(s)",
            ];
        }

        return $relatedData;
    }

    private function showDeletionConfirmationDialog($item): void
    {
        $relatedSummary = $this->buildDeletionSummary();

        $this->dialog()
            ->question('Delete Item: '.$item->name, 'This will also delete the following related data:'.$relatedSummary)
            ->confirm('Confirm', 'confirmedDelete')
            ->cancel('Cancel')
            ->send();
    }

    private function buildDeletionSummary(): string
    {
        $summary = '';

        if (! empty($this->relatedDataToDelete['recipes'])) {
            $count = $this->relatedDataToDelete['recipes']['count'];
            $summary .= "\n• Recipes: {$count} recipe(s) using this item";
        }

        if (! empty($this->relatedDataToDelete['stocks'])) {
            $count = $this->relatedDataToDelete['stocks']['count'];
            $summary .= "\n• Stock Records: {$count} branch(es)";
        }

        if (! empty($this->relatedDataToDelete['purchases'])) {
            $count = $this->relatedDataToDelete['purchases']['count'];
            $summary .= "\n• Purchases: {$count} purchase order(s)";
        }

        if (! empty($this->relatedDataToDelete['stock_movements'])) {
            $count = $this->relatedDataToDelete['stock_movements']['count'];
            $summary .= "\n• Stock Movements: {$count} record(s)";
        }

        return $summary ?: "\nNo related data to delete.";
    }

    public function confirmedDelete()
    {
        $branchId = $this->getBranchId();
        $item = Item::where('id', $this->pendingItemId)
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $itemName = $item->name;
        $itemSku = $item->sku;

        $item->delete();

        // Log the item deletion
        AuditService::log(
            current_actor(),
            'delete',
            $item,
            "Deleted item '{$itemName}' (SKU: {$itemSku})",
            'completed'
        );

        $this->toast()->success('Item deleted!')->send();
    }

    public function submitItemDeletionRequest()
    {
        $this->validate(['auditReason' => 'required|string|min:10|max:500']);

        try {
            // Include related data in the deletion request
            $deletionData = [
                'item_id' => $this->pendingItemId,
                'related_data' => $this->relatedDataToDelete,
            ];

            $request = InventoryApprovalService::requestItemDeletion(
                current_actor(),
                $this->pendingItemId,
                $this->auditReason,
                $deletionData
            );

            // Verify request was created
            if (! $request || ! $request->id) {
                throw new \Exception('Failed to create approval request');
            }

            $this->toast()->success('Deletion request submitted! (Request ID: '.$request->id.')')->send();
            $this->closeAuditModal();
        } catch (\Exception $e) {
            $this->toast()->error('Failed: '.$e->getMessage())->send();
        }
    }

    // ===================================================================
    // UTILITIES
    // ===================================================================
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeAuditModal()
    {
        $this->showAuditModal = false;
        $this->auditReason = '';
        $this->auditAction = null;

        // If there was pending item data, reopen the item modal
        if (! empty($this->pendingItemData)) {
            // Repopulate the form with the pending data
            $this->itemId = $this->pendingItemData['item_id'] ?? null;
            $this->name = $this->pendingItemData['name'] ?? '';
            $this->sku = $this->pendingItemData['sku'] ?? '';
            $this->category_id = $this->pendingItemData['category_id'] ?? null;
            $this->uom_id = $this->pendingItemData['uom_id'] ?? null;
            $this->reorder_level = $this->pendingItemData['reorder_level'] ?? null;
            $this->max_stock_level = $this->pendingItemData['max_stock_level'] ?? null;
            $this->unit_price = $this->pendingItemData['unit_price'] ?? 0;
            $this->status = $this->pendingItemData['status'] ?? 'active';
            $this->requires_request = (bool) ($this->pendingItemData['requires_request'] ?? false);
            $this->isEditing = $this->itemId ? true : false;
            $this->showModal = true;
        }

        $this->pendingItemId = null;
        $this->pendingItemData = [];
        $this->relatedDataToDelete = [];
    }

    private function resetForm()
    {
        $this->reset(['itemId', 'name', 'sku', 'category_id', 'uom_id', 'reorder_level', 'max_stock_level', 'unit_price', 'sell_price', 'status', 'requires_request', 'isEditing']);
        $this->unit_price = 0;
        $this->sell_price = null;
        $this->requires_request = false;
    }

    public function updatedName()
    {
        if (! $this->isEditing) {
            $this->generateSku();
        }
    }

    public function updatedCategoryId()
    {
        if (! $this->isEditing) {
            $this->generateSku();
        }
    }

    private function generateSku()
    {
        if ($this->isEditing || empty($this->name) || empty($this->category_id)) {
            return;
        }

        $category = ItemCategory::find($this->category_id);
        $prefix = strtoupper(substr($category?->name ?? 'IT', 0, 2));
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->name), 0, 3));
        $rand = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $this->sku = "$prefix-$code-$rand";
    }

    // ===================================================================
    // RENDER + FILTERS (unchanged from your original)
    // ===================================================================
    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate((int) ($this->quantity ?? 10));

        $branchId = $this->getBranchId();
        $lowStockAlerts = Item::query()
            ->with(['unitOfMeasure', 'stocks' => fn ($q) => $q->where('branch_id', $branchId)])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('reorder_level')
            ->where('reorder_level', '>', 0)
            ->get()
            ->filter(fn ($item) => $item->isBelowReorderLevel($branchId))
            ->values();

        return view('livewire.branch-dashboard.inventory.items', [
            'headers' => [
                ['index' => 'id', 'label' => '#'],
                ['index' => 'sku', 'label' => 'SKU'],
                ['index' => 'name', 'label' => 'Item Name'],
                ['index' => 'category', 'label' => 'Category'],
                ['index' => 'requires_request', 'label' => 'Request Flow'],
                ['index' => 'uom', 'label' => 'UOM'],
                ['index' => 'reorder_level', 'label' => 'Reorder Level'],
                ['index' => 'unit_price', 'label' => 'Unit Price'],
                ['index' => 'status', 'label' => 'Status'],
                ['index' => 'action', 'label' => 'Actions', 'display' => true],
            ],
            'rows' => $rows,
            'lowStockAlerts' => $lowStockAlerts,
        ]);
    }

    protected function getFilteredQuery()
    {
        $branchId = $this->getBranchId();

        $query = Item::query()
            ->with(['branch', 'unitOfMeasure', 'category'])
            ->when($branchId, fn ($q) => $q->where('items.branch_id', $branchId))
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest();

        return $query;
    }

    public function applyFilters()
    {
        // Filters are applied automatically via wire:model.live, so this is a no-op
        // But we need this method to exist to prevent the MethodNotFoundException
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterCategory', 'filterStatus']);
        $this->resetPage();
    }

    public function exportCSV()
    {
        try {
            // Export ALL items without pagination or search filters
            $items = Item::query()
                ->where('branch_id', $this->getBranchId())
                ->orderBy('sku')
                ->get();

            $csvData = [
                ['SKU', 'Name', 'Category', 'Reorder Level', 'Unit Price', 'Status', 'UOM'],
            ];

            foreach ($items as $item) {
                $csvData[] = [
                    $item->sku,
                    $item->name,
                    $item->category?->name ?? 'None',
                    $item->reorder_level,
                    $item->unit_price,
                    $item->status,
                    $item->uom,
                ];
            }

            $filename = 'inventory-items-'.now()->format('Y-m-d-His').'.csv';
            $handle = fopen('php://temp', 'r+');

            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Exception $e) {
            $this->toast()->error('Export failed: '.$e->getMessage())->send();

            return;
        }
    }

    public function openImportModal()
    {
        $this->reset(['importFile', 'importResult']);
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function closeImportModal()
    {
        $this->reset(['importFile', 'importResult']);
        $this->resetValidation();
        $this->showImportModal = false;
    }

    /**
     * Item fields that can be set from a CSV worksheet, keyed by the column
     * tag mapImportColumns() resolves and labelled for messages.
     * Quantity on hand is deliberately excluded — opening counts must go
     * through the Stock Take workflow so they are approved and audited.
     */
    protected array $importableFields = [
        'price' => ['column' => 'unit_price', 'label' => 'Unit Price'],
        'reorder' => ['column' => 'reorder_level', 'label' => 'Reorder Level'],
        'max' => ['column' => 'max_stock_level', 'label' => 'Max Stock Level'],
    ];

    /**
     * Import item settings (Unit Price, Reorder Level, Max Stock Level) from the
     * exported / supplied worksheet. Whichever of those columns are present are
     * applied; blank cells are left untouched so the file can be re-uploaded as
     * it is filled in. A "Quantity On Hand" column, if present, is ignored.
     */
    public function importCSV()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:5120',
        ], [], ['importFile' => 'CSV file']);

        $branchId = $this->getBranchId();

        $handle = fopen($this->importFile->getRealPath(), 'r');
        if ($handle === false) {
            $this->toast()->error('Could not read the uploaded file.')->send();

            return;
        }

        $header = fgetcsv($handle);
        $map = $this->mapImportColumns($header);

        // which editable fields are actually present in this file
        $present = array_filter(
            $this->importableFields,
            fn ($tag) => isset($map[$tag]),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($present)) {
            fclose($handle);
            $this->toast()->error('No Unit Price, Reorder Level or Max Stock Level column found in the file.')->send();

            return;
        }

        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $errors = [];
        $fieldCounts = array_fill_keys(array_column($present, 'column'), 0);
        $rowNum = 1; // header is row 1

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // ignore completely blank lines
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            // collect the values supplied for this row across the present columns
            $values = [];
            $rowHasError = false;
            foreach ($present as $tag => $field) {
                $raw = trim((string) ($row[$map[$tag]] ?? ''));
                if ($raw === '') {
                    continue;
                }

                $clean = preg_replace('/[^0-9.\-]/', '', $raw);
                if ($clean === '' || ! is_numeric($clean) || (float) $clean < 0) {
                    $errors[] = "Row {$rowNum}: invalid {$field['label']} '{$raw}'";
                    $rowHasError = true;

                    continue;
                }

                $values[$field['column']] = (float) $clean;
            }

            // nothing filled in for this row yet
            if (empty($values)) {
                if (! $rowHasError) {
                    $skipped++;
                }

                continue;
            }

            $idVal = trim((string) ($row[$map['id']] ?? ''));
            $skuVal = trim((string) ($row[$map['sku']] ?? ''));

            $query = Item::query()->where('branch_id', $branchId);
            $item = ($idVal !== '' && is_numeric($idVal))
                ? (clone $query)->find((int) $idVal)
                : null;
            if (! $item && $skuVal !== '') {
                $item = (clone $query)->where('sku', $skuVal)->first();
            }

            if (! $item) {
                $errors[] = "Row {$rowNum}: item not found in this branch (ID {$idVal}, SKU {$skuVal})";

                continue;
            }

            // apply only the values that actually differ
            $changes = [];
            foreach ($values as $column => $value) {
                if (abs((float) $item->{$column} - $value) < 0.0001) {
                    continue;
                }
                $label = collect($present)->firstWhere('column', $column)['label'];
                $changes[] = "{$label}: ".($item->{$column} ?? 0)." → {$value}";
                $item->{$column} = $value;
                $fieldCounts[$column]++;
            }

            if (empty($changes)) {
                $unchanged++;

                continue;
            }

            $item->save();
            $updated++;

            AuditService::log(
                current_actor(),
                'update',
                $item,
                "Updated via CSV import for '{$item->name}' (SKU: {$item->sku}). ".implode(', ', $changes),
                'completed'
            );
        }

        fclose($handle);

        $this->importResult = [
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
            'fields' => $fieldCounts,
            'fieldLabels' => collect($present)->pluck('label', 'column')->all(),
            'errors' => $errors,
        ];

        $this->toast()->success("Import complete: {$updated} item(s) updated, {$unchanged} unchanged, {$skipped} left blank.")->send();
    }

    /**
     * Resolve column positions from the header row by name. ID and SKU fall
     * back to the export's fixed first two columns; the editable price/level
     * columns are matched purely by header text (absent if not present).
     */
    protected function mapImportColumns($header): array
    {
        $map = [];

        if (is_array($header)) {
            foreach ($header as $idx => $col) {
                $key = strtolower(trim((string) $col));
                if ($key === 'id') {
                    $map['id'] = $idx;
                } elseif ($key === 'sku') {
                    $map['sku'] = $idx;
                } elseif (str_contains($key, 'unit price')) {
                    $map['price'] = $idx;
                } elseif (str_contains($key, 'reorder')) {
                    $map['reorder'] = $idx;
                } elseif (str_contains($key, 'max stock')) {
                    $map['max'] = $idx;
                }
            }
        }

        // ID / SKU positional fallback: worksheets always lead with ID, SKU
        $map['id'] ??= 0;
        $map['sku'] ??= 1;

        return $map;
    }
}
