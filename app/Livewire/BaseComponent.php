<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * Class BaseComponent
 *
 * Abstract base class for Livewire components that need:
 * - Multi-select functionality
 * - Dynamic bulk actions (delete, archive, activate, restore, etc.)
 * - Export for selected items
 *
 * Each child component defines its own bulk actions in `$bulkActions`,
 * mapping an action key to both a label and a handler method name.
 *
 * Example:
 *  protected array $bulkActions = [
 *      'delete' => ['label' => 'Delete', 'method' => 'bulkDelete'],
 *      'activate' => ['label' => 'Activate', 'method' => 'bulkActivate'],
 *      'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
 *  ];
 */
abstract class BaseComponent extends Component
{
    use WithPagination, Interactions;

    /**
     * List of selected item IDs for multi-select.
     *
     * @var array
     */
    public array $selectedIds = [];

    /**
     * Whether all items on current page are selected.
     *
     * @var bool
     */
    public bool $selectAll = false;

    /**
     * Whether multi-select mode is currently enabled.
     *
     * @var bool
     */
    public bool $bulkMode = false;

    /**
     * The currently selected bulk action key.
     *
     * @var string|null
     */
    public ?string $selectedBulkAction = null;

    /**
     * Defines available bulk actions.
     *
     * Each key is an action identifier (used in dropdowns),
     * and maps to an array containing:
     * - 'label': The display name
     * - 'method': The Livewire method name to call
     *
     * @var array
     */
    protected array $bulkActions = [
        'delete' => ['label' => 'Delete', 'method' => 'bulkDelete'],
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    /**
     * Initializes shared state for all inheriting components.
     * Must be called from each child’s `mount()` method.
     *
     * @return void
     */
    protected function mountBase(): void
    {
        $this->resetBulkSelection();
    }

    /**
     * Resets all bulk selection states.
     *
     * @return void
     */
    protected function resetBulkSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->bulkMode = false;
        $this->selectedBulkAction = null;
    }

    /**
     * Enables or disables multi-select mode.
     *
     * @return void
     */
    public function toggleBulkMode(): void
    {
        $this->bulkMode = !$this->bulkMode;
        if (!$this->bulkMode) {
            $this->resetBulkSelection();
        }
    }

    /**
     * Automatically updates selected IDs when "Select All" is toggled.
     *
     * @param bool $value
     * @return void
     */
    public function updatedSelectAll($value): void
    {
        if ($value) {
            // Limit the number of selectable IDs to prevent performance issues
            $allIds = $this->getAllSelectableIds();
            if (count($allIds) > 1000) { // Limit to 1000 items to prevent performance issues
                $this->selectedIds = array_slice($allIds, 0, 1000);
                $this->selectAll = false; // Don't set selectAll to true if we had to limit
                $this->dispatch('toast', [
                    'type' => 'warning',
                    'message' => 'Only first 1000 items selected due to performance limits.'
                ]);
            } else {
                $this->selectedIds = $allIds;
            }
        } else {
            $this->selectedIds = [];
        }
    }

    /**
     * Dynamically performs the bulk action selected by the user.
     *
     * @return void
     */
    public function performBulkAction(): void
    {
        if (empty($this->selectedBulkAction)) {
            session()->flash('error', 'Please select a bulk action.');
            return;
        }

        // Find the action in bulkActions
        $action = $this->bulkActions[$this->selectedBulkAction] ?? null;

        if (!$action || !isset($action['method'])) {
            session()->flash('error', 'Invalid or undefined bulk action.');
            return;
        }

        $method = $action['method'];

        // Check if the method exists in the component
        if (!method_exists($this, $method)) {
            session()->flash('error', "The method '{$method}' does not exist on this component.");
            return;
        }

        // Dynamically call the method
        try {
            $this->{$method}();
        } catch (\Throwable $e) {
            session()->flash('error', "Error executing bulk action: " . $e->getMessage());
        }
    }

    // ------------------------------------------------------------
    // DEFAULT HANDLERS (can be overridden in child components)
    // ------------------------------------------------------------

 

    /**
     * Exports selected records (to CSV, Excel, etc.).
     * Child components can override this to implement custom export logic.
     *
     * @return void
     */
    protected function exportSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('info', 'No items selected for export.');
            return;
        }

        // Example placeholder
        session()->flash('success', 'Export started for ' . count($this->selectedIds) . ' items.');
    }

    // ------------------------------------------------------------
    // ABSTRACT METHODS (must be defined by child components)
    // ------------------------------------------------------------

    /**
     * Get the model class associated with this component.
     *
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * Get all selectable IDs for bulk selection.
     *
     * @return array
     */
    abstract protected function getAllSelectableIds(): array;
}
