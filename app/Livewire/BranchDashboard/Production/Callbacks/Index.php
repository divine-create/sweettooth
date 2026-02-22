<?php

namespace App\Livewire\BranchDashboard\Production\Callbacks;

use App\Livewire\BaseComponent;
use App\Enums\CallbackStatus;
use App\Models\ProductionCallback;
use App\Models\Shift;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * Production Callbacks Index Component
 * 
 * Lists and manages all production callbacks (items reported to inventory).
 * Displays callbacks for damaged/defective:
 *   1. Raw materials - From stock inventory
 *   2. Finished products - From production quality checks
 * 
 * Features:
 *   - Status filtering (pending, approved, rejected, completed)
 *   - Date range filtering (default: last 30 days)
 *   - Search by item/product/actor name
 *   - Shift-based filtering
 *   - Inline actions and modal details view
 *   - Status statistics dashboard
 *   - Export functionality (coming soon)
 * 
 * Workflow States:
 *   - pending: Awaiting inventory approval
 *   - approved_by_inventory: Approved, stock updated
 *   - rejected: Inventory rejected, no stock change
 *   - completed: Final state
 * 
 * @property string|null $b_id Branch ID for filtering
 * @property int|null $quantity Records per page (pagination)
 * @property string|null $search Search term
 * @property string|null $filterStatus Status filter
 * @property string|null $startDate Date range start
 * @property string|null $endDate Date range end
 */
#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public $availableShifts = [];
    public ?int $currentShiftId = null;

    // Table headers
    public array $headers = [
        ['index' => 'callback_id', 'label' => 'Callback ID'],
        ['index' => 'type', 'label' => 'Type'],
        ['index' => 'item_product', 'label' => 'Item/Product'],
        ['index' => 'quantity', 'label' => 'Quantity'],
        ['index' => 'reason', 'label' => 'Reason'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'callback_time', 'label' => 'Callback Time'],
        ['index' => 'recorded_by', 'label' => 'Recorded By'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    // Status options for filter
    public array $statusOptions = [
        'pending' => 'Pending',
        'approved_by_inventory' => 'Approved by Inventory',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
    ];

    protected function getModelClass(): string
    {
        return ProductionCallback::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        $query = ProductionCallback::query()
            ->with(['item', 'product', 'shift', 'recordedBy', 'approvedBy'])
            ->whereHas('shift', function ($q) {
                $q->where('branch_id', $this->getBranchId());
            });

        return $query;
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->startDate = \Carbon\Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = \Carbon\Carbon::today()->format('Y-m-d');
        $this->loadAvailableShifts();
        $this->loadCurrentShift();
    }

    protected function loadAvailableShifts()
    {
        $branchId = $this->getBranchId();

        // Get production shifts from last 30 days
        $this->availableShifts = Shift::where('branch_id', $branchId)
            ->where('shift_date', '>=', now()->subDays(30))
            ->whereHas('department.category', function ($q) {
                $q->where('name', 'Production');
            })
            ->with('department')
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_type', 'desc')
            ->get();
    }

    protected function loadCurrentShift()
    {
        $employee = auth()->user();

        // Find active production shift for this employee
        $activeShift = Shift::where('branch_id', $this->getBranchId())
            ->where('shift_date', \Carbon\Carbon::today())
            ->where('status', 'active')
            ->where('employee_id', $employee->id)
            ->whereHas('department.category', function ($q) {
                $q->where('name', 'Production');
            })
            ->first();

        if ($activeShift) {
            $this->currentShiftId = $activeShift->id;
        }
    }

    public function getRowsProperty()
    {
        $query = ProductionCallback::with(['item', 'product', 'shift', 'recordedBy', 'approvedBy'])
            ->whereHas('shift', function ($q) {
                $q->where('branch_id', $this->getBranchId());
            });

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('item', function ($itemQuery) {
                    $itemQuery->where('name', 'like', '%' . $this->search . '%')
                             ->orWhere('sku', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('product', function ($productQuery) {
                    $productQuery->where('name', 'like', '%' . $this->search . '%')
                                 ->orWhere('sku', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('recordedBy', function ($employeeQuery) {
                    $employeeQuery->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        // Status filter
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Date range filter
        if ($this->startDate) {
            $query->whereDate('callback_time', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('callback_time', '<=', $this->endDate);
        }

        return $query->orderBy('callback_time', 'desc')->paginate($this->quantity);
    }

    public function viewDetails($callbackId)
    {
        $callback = ProductionCallback::with([
            'item',
            'product',
            'shift',
            'recordedBy',
            'approvedBy'
        ])->find($callbackId);

        if (!$callback) {
            $this->toast()->error('Callback not found.')->send();
            return;
        }

        // Store for modal display
        $this->dispatch('show-callback-details', callback: $callback->toArray());
    }

    public function exportCallbacks()
    {
        $this->toast()->info('Export feature coming soon.')->send();
    }

    public function getStatusBadgeClass($status)
    {
        if ($status instanceof CallbackStatus) {
            return $status->getBadgeColor();
        }

        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            'approved_by_inventory' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        };
    }

    public function render()
    {
        $currentShift = $this->currentShiftId ? Shift::find($this->currentShiftId) : null;

        return view('livewire.branch-dashboard.production.callbacks.index', [
            'rows' => $this->rows,
            'currentShift' => $currentShift,
            'availableShifts' => $this->availableShifts,
        ]);
    }
}
