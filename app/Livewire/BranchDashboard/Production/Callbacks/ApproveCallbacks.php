<?php

namespace App\Livewire\BranchDashboard\Production\Callbacks;

use App\Livewire\BaseComponent;
use App\Enums\CallbackStatus;
use App\Models\ProductDispatchCallback;
use App\Models\ProductDispatch;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * ApproveCallbacks Component
 * 
 * Manages the approval workflow for product return callbacks.
 * Handles callbacks from Sales department where customers return products.
 * 
 * Workflow States:
 *   1. pending - Awaiting approval from production
 *   2. approved_by_production - Production approved, awaiting receipt confirmation
 *   3. received_by_production - Production received item, awaiting completion/stock update
 *   4. completed - Final state, stock has been updated
 * 
 * Key Features:
 *   - Polymorphic actor tracking (Employee or User can approve)
 *   - Automatic stock updates via model methods
 *   - Search and filtering by status, date range
 *   - Detailed callback view modal
 *   - Statistics dashboard (pending, approved, received, completed counts)
 * 
 * @property string|null $b_id Branch ID for filtering
 * @property int|null $quantity Records per page (pagination)
 * @property string|null $search Search term for product/actor name
 * @property string|null $filterStatus Filter by callback status
 * @property string|null $startDate Date range start
 * @property string|null $endDate Date range end
 */
#[Layout('components.layouts.app.branch-dashboard')]
class ApproveCallbacks extends BaseComponent
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $deptSlug = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Modal for viewing details
    public $selectedCallback = null;
    public $showDetailsModal = false;

    // Table headers
    public array $headers = [
        ['index' => 'callback_id', 'label' => 'Callback ID'],
        ['index' => 'product', 'label' => 'Product'],
        ['index' => 'quantity', 'label' => 'Quantity'],
        ['index' => 'reason', 'label' => 'Reason'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'sales_shift', 'label' => 'From Sales Shift'],
        ['index' => 'callback_time', 'label' => 'Callback Time'],
        ['index' => 'action', 'label' => 'Action'],
    ];

    // Status options for filter
    public array $statusOptions = [
        'pending' => 'Pending Approval',
        'approved_by_production' => 'Approved (Awaiting Receipt)',
        'received_by_production' => 'Received (Awaiting Completion)',
        'completed' => 'Completed',
    ];

    protected function getModelClass(): string
    {
        return ProductDispatchCallback::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        $branchId = $this->getBranchId();

        $query = ProductDispatchCallback::query()
            ->with(['product', 'salesShift', 'productDispatch.shift', 'recordedBy', 'approvedBy', 'receivedBy'])
            ->whereHas('salesShift', function ($sq) use ($branchId) {
                $sq->where('branch_id', $branchId);
            });

        // Filter by the department that PRODUCED the item
        if ($this->deptSlug) {
            $query->whereHas('productDispatch.shift.department', function ($dq) {
                $dq->where('slug', $this->deptSlug);
            });
        }

        return $query;
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount($deptSlug = null)
    {
        if ($deptSlug) {
            $this->deptSlug = $deptSlug;
        }
        $this->startDate = \Carbon\Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = \Carbon\Carbon::today()->format('Y-m-d');
    }

    public function getRowsProperty()
    {
        $branchId = $this->getBranchId();

        $query = ProductDispatchCallback::with([
            'product',
            'salesShift',
            'productDispatch.shift',
            'recordedBy',
            'approvedBy',
            'receivedBy'
        ])
        ->whereHas('salesShift', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });

        // Filter by the department that PRODUCED the item
        if ($this->deptSlug) {
            $query->whereHas('productDispatch.shift.department', function ($dq) {
                $dq->where('slug', $this->deptSlug);
            });
        }

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('product', function ($productQuery) {
                    $productQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                })
                    ->orWhereHas('recordedBy', function ($employeeQuery) {
                        $employeeQuery->where('name', 'like', '%'.$this->search.'%');
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

        return $query->orderBy('callback_time', 'desc')->paginate((int) $this->quantity);
    }

    public function viewDetails($callbackId)
    {
        $this->selectedCallback = ProductDispatchCallback::with([
            'product',
            'productDispatch',
            'salesShift',
            'recordedBy',
            'approvedBy',
            'receivedBy'
        ])->find($callbackId);

        if (!$this->selectedCallback) {
            $this->toast()->error('Callback not found.')->send();
            return;
        }

        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedCallback = null;
    }

    public function approveCallback($callbackId)
    {
        try {
            $callback = ProductDispatchCallback::find($callbackId);

            if (!$callback) {
                $this->toast()->error('Callback not found.')->send();
                return;
            }

            // Authorization check using policy
            $user = auth()->user();
            if ($user && !$user->can('approve', $callback)) {
                $this->toast()->error('You are not authorized to approve this callback.')->send();
                return;
            }

            if (!$callback->canBeApproved()) {
                $this->toast()->error('Callback cannot be approved. Current status: ' . $callback->formatted_status)->send();
                return;
            }

            $actor = current_actor();
            if (!$actor) {
                $this->toast()->error('No authenticated actor found. Please ensure you are logged in.')->send();
                return;
            }

            // approve() method now handles its own transaction with locking
            if (!$callback->approve($actor)) {
                $this->toast()->error('Callback could not be approved. It may have been modified by another user.')->send();
                return;
            }

            // Clear stats cache
            $this->clearStatsCache();

            $this->toast()->success('Callback approved successfully!')->send();
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            $this->toast()->error('Failed to approve callback: ' . $e->getMessage())->send();
        }
    }

    public function receiveCallback($callbackId)
    {
        try {
            $callback = ProductDispatchCallback::find($callbackId);

            if (!$callback) {
                $this->toast()->error('Callback not found.')->send();
                return;
            }

            // Authorization check using policy
            $user = auth()->user();
            if ($user && !$user->can('receive', $callback)) {
                $this->toast()->error('You are not authorized to receive this callback.')->send();
                return;
            }

            if (!$callback->canBeReceived()) {
                $this->toast()->error('Callback cannot be received. Current status: ' . $callback->formatted_status)->send();
                return;
            }

            $actor = current_actor();
            if (!$actor) {
                $this->toast()->error('No authenticated actor found. Please ensure you are logged in.')->send();
                return;
            }

            // markAsReceived() method now handles its own transaction with locking
            if (!$callback->markAsReceived($actor)) {
                $this->toast()->error('Callback could not be received. It may have been modified by another user.')->send();
                return;
            }

            // Clear stats cache
            $this->clearStatsCache();

            $this->toast()->success('Callback received successfully!')->send();
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            $this->toast()->error('Failed to receive callback: ' . $e->getMessage())->send();
        }
    }

    public function completeCallback($callbackId)
    {
        try {
            $callback = ProductDispatchCallback::find($callbackId);

            if (!$callback) {
                $this->toast()->error('Callback not found.')->send();
                return;
            }

            // Authorization check using policy
            $user = auth()->user();
            if ($user && !$user->can('complete', $callback)) {
                $this->toast()->error('You are not authorized to complete this callback.')->send();
                return;
            }

            if ($callback->status !== CallbackStatus::RECEIVED_BY_PRODUCTION) {
                $this->toast()->error('Callback must be received before completion. Current status: ' . $callback->formatted_status)->send();
                return;
            }

            // Use model's completeWithStockUpdate() - handles its own transaction
            $callback->completeWithStockUpdate();

            // Clear stats cache
            $this->clearStatsCache();

            $this->toast()->success('Callback completed successfully! Stock has been updated.')->send();
            $this->dispatch('$refresh');

            // Close modal if open
            if ($this->showDetailsModal) {
                $this->closeDetailsModal();
            }

        } catch (\Exception $e) {
            $this->toast()->error('Failed to complete callback: ' . $e->getMessage())->send();
        }
    }

    /**
     * Get stats cache key for current branch
     */
    protected function getStatsCacheKey(): string
    {
        return "callback_stats_branch_{$this->getBranchId()}";
    }

    /**
     * Clear stats cache
     */
    protected function clearStatsCache(): void
    {
        cache()->forget($this->getStatsCacheKey());
    }

    /**
     * Get cached stats for the branch
     */
    protected function getCachedStats(): array
    {
        return cache()->remember(
            $this->getStatsCacheKey(),
            now()->addMinutes(5),
            function () {
                $branchId = $this->getBranchId();
                $baseQuery = ProductDispatchCallback::whereHas('productDispatch.salesShift', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });

                return [
                    'total' => (clone $baseQuery)->count(),
                    'pending' => (clone $baseQuery)->where('status', CallbackStatus::PENDING->value)->count(),
                    'approved' => (clone $baseQuery)->where('status', CallbackStatus::APPROVED_BY_PRODUCTION->value)->count(),
                    'received' => (clone $baseQuery)->where('status', CallbackStatus::RECEIVED_BY_PRODUCTION->value)->count(),
                    'completed' => (clone $baseQuery)->where('status', CallbackStatus::COMPLETED->value)->count(),
                    'stuck' => (clone $baseQuery)->stuck()->count(),
                ];
            }
        );
    }



    public function getStatusBadgeClass($status)
    {
        $value = $status instanceof CallbackStatus ? $status->value : $status;

        return match ($value) {
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            'approved_by_production' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'received_by_production' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
        };
    }

    public function render()
    {
        // Use cached stats for better performance
        $stats = $this->getCachedStats();

        return view('livewire.branch-dashboard.production.callbacks.approve-callbacks', [
            'rows' => $this->rows,
            'stats' => $stats,
        ]);
    }
}
