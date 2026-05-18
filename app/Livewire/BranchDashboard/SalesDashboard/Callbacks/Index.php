<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Callbacks;

use App\Livewire\BaseComponent;
use App\Models\ProductDispatchCallback;
use App\Models\SalesShift;
use App\Models\Department;
use App\Traits\Exportable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use WithPagination, Interactions, Exportable;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?string $branchId = null;
    public ?int $departmentId = null;
    /** @var array<int> */
    public array $departmentIds = [];

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public $availableShifts = [];

    // Create callback modal
    public $showCreateModal = false;
    public $selectedProduct = null;
    public $callbackQuantity = 0;
    public $callbackReason = '';
    public $callbackNotes = '';
    public $callbackUom = 'kg';
    public $currentSalesShiftId = null;

    protected array $bulkActions = [
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    // Reason options
    public array $reasonOptions = [
        'expired' => 'Expired',
        'damaged' => 'Damaged',
        'quality_issue' => 'Quality Issue',
        'customer_return' => 'Customer Return',
        'over_stock' => 'Over Stock',
        'wrong_item' => 'Wrong Item',
        'other' => 'Other',
    ];

    // Table headers
    public array $headers = [
        ['index' => 'callback_id', 'label' => 'Callback ID'],
        ['index' => 'product', 'label' => 'Product'],
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
        'approved_by_production' => 'Approved by Production',
        'received_by_production' => 'Received by Production',
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
        $query = ProductDispatchCallback::query()
            ->with(['product', 'salesShift', 'recordedBy', 'approvedBy', 'receivedBy'])
            ->whereHas('salesShift', function ($q) {
                $q->where('branch_id', $this->getBranchId());
            })
            ->when($this->search, function ($q) {
                $q->whereHas('product', function ($pq) {
                    $pq->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->startDate && $this->endDate, function ($q) {
                $q->whereHas('salesShift', function ($sq) {
                    $sq->whereBetween('shift_date', [$this->startDate, $this->endDate]);
                });
            });

        return $query;
    }

    public function getBranchId()
    {
        return $this->b_id ?? current_branch_id();
    }

    public function mount()
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->startDate = \Carbon\Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = \Carbon\Carbon::today()->format('Y-m-d');
        if (! $this->salesDeptSlug) {
            $this->salesDeptSlug = request()->route('salesDeptSlug')
                ?? request()->query('sales_dept_slug')
                ?? request()->query('salesDeptSlug');
        }
        $this->loadBranchAndDepartment();
        $this->loadAvailableShifts();
        $this->loadCurrentSalesShift();
    }

    protected function loadBranchAndDepartment(): void
    {
        $this->branchId = $this->getBranchId();

        if (! $this->salesDeptSlug) {
            return;
        }

        $department = Department::where('slug', $this->salesDeptSlug)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->first();

        if (! $department) {
            $department = Department::where('slug', $this->salesDeptSlug)
                ->whereNull('branch_id')
                ->first();
        }

        if ($department) {
            $this->departmentId = (int) $department->id;
            $this->departmentIds = $this->resolveEquivalentSalesDepartmentIds($department);
        }
    }

    protected function loadAvailableShifts()
    {
        $branchId = $this->getBranchId();
        $departmentFilterIds = $this->getDepartmentFilterIds();

        // Get sales shifts from last 30 days
        $this->availableShifts = \App\Models\SalesShift::where('branch_id', $branchId)
            ->where('shift_date', '>=', now()->subDays(30))
            ->when(! empty($departmentFilterIds), fn ($q) => $q->whereIn('department_id', $departmentFilterIds))
            ->with('department')
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_type', 'desc')
            ->get();
    }

    protected function loadCurrentSalesShift()
    {
        $employee = auth()->user();
        $departmentFilterIds = $this->getDepartmentFilterIds();

        // First try to find active sales shift for this employee
        $activeShift = \App\Models\SalesShift::where('branch_id', $this->getBranchId())
            ->where('shift_date', \Carbon\Carbon::today())
            ->where('status', 'active')
            ->where('employee_id', $employee->id)
            ->when(! empty($departmentFilterIds), fn ($q) => $q->whereIn('department_id', $departmentFilterIds))
            ->first();

        // If not found, try to find any active sales shift in the employee's department
        if (!$activeShift && $employee->department_id) {
            $activeShift = \App\Models\SalesShift::where('branch_id', $this->getBranchId())
                ->where('shift_date', \Carbon\Carbon::today())
                ->where('status', 'active')
                ->when(! empty($departmentFilterIds), fn ($q) => $q->whereIn('department_id', $departmentFilterIds))
                ->when(empty($departmentFilterIds), fn ($q) => $q->where('department_id', $employee->department_id))
                ->first();
        }

        if ($activeShift) {
            $this->currentSalesShiftId = $activeShift->id;
        }
    }

    public function openCreateModal()
    {
        if (!$this->currentSalesShiftId) {
            $this->toast()->error('No active sales shift found. Please start a shift first.')->send();
            return;
        }

        $this->selectedProduct = null;
        $this->callbackQuantity = 0;
        $this->callbackReason = '';
        $this->callbackNotes = '';
        $this->callbackUom = 'kg';
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->selectedProduct = null;
        $this->callbackQuantity = 0;
        $this->callbackReason = '';
        $this->callbackNotes = '';
        $this->callbackUom = 'kg';
    }

    public function createCallback()
    {
        $this->toast()->error('Please create callbacks from dispatches to ensure correct stock tracking.')->send();
        return;

        $this->validate([
            'selectedProduct' => 'required|exists:products,id',
            'callbackQuantity' => 'required|numeric|min:0.01',
            'callbackReason' => 'required|in:expired,damaged,quality_issue,customer_return,over_stock,wrong_item,other',
            'callbackUom' => 'required|string',
        ], [
            'selectedProduct.required' => 'Please select a product',
            'callbackQuantity.required' => 'Callback quantity is required',
            'callbackQuantity.min' => 'Callback quantity must be greater than 0',
            'callbackReason.required' => 'Please select a callback reason',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $employee = auth()->user();

            // Create callback record
            ProductDispatchCallback::create([
                'product_dispatch_id' => null, // Direct callback without dispatch
                'sales_shift_id' => $this->currentSalesShiftId,
                'product_id' => $this->selectedProduct,
                'recorded_by_id' => $employee->id,
                'recorded_by_type' => get_class($employee),
                'quantity' => $this->callbackQuantity,
                'uom' => $this->callbackUom,
                'reason' => $this->callbackReason,
                'status' => 'pending',
                'notes' => $this->callbackNotes,
                'callback_time' => now(),
            ]);

            \Illuminate\Support\Facades\DB::commit();

            $this->toast()->success('Product callback created successfully! Awaiting production approval.')->send();
            $this->closeCreateModal();
            $this->resetPage();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->toast()->error('Error creating callback: ' . $e->getMessage())->send();
        }
    }

    #[Computed]
    public function rows()
    {
        $departmentFilterIds = $this->getDepartmentFilterIds();
        $query = ProductDispatchCallback::with(['product', 'salesShift', 'recordedBy', 'approvedBy', 'receivedBy'])
            ->whereHas('salesShift', function ($q) {
                $q->where('branch_id', $this->getBranchId());
            });
        if (! empty($departmentFilterIds)) {
            $query->whereHas('salesShift', function ($q) use ($departmentFilterIds) {
                $q->whereIn('department_id', $departmentFilterIds);
            });
        }

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('product', function ($productQuery) {
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

        return $query->orderBy('callback_time', 'desc')->paginate((int) $this->quantity);
    }

    public function viewDetails($callbackId)
    {
        $callback = ProductDispatchCallback::with([
            'product',
            'productDispatch',
            'salesShift',
            'recordedBy',
            'approvedBy',
            'receivedBy'
        ])->find($callbackId);

        if (!$callback) {
            $this->toast()->error('Callback not found.')->send();
            return;
        }

        // Store for modal display
        $this->dispatch('show-callback-details', callback: $callback->toArray());
    }


    /**
     * Export callbacks as CSV
     */
    public function exportCSV()
    {
        try {
            $callbacks = $this->getFilteredCallbacks();

            if ($callbacks->isEmpty()) {
                session()->flash('warning', 'No callbacks to export.');
                return;
            }

            $csvData = [
                ['Callback ID', 'Product', 'SKU', 'Quantity', 'UOM', 'Reason', 'Status', 'Callback Time', 'Recorded By', 'Approved By', 'Received By', 'Notes'],
            ];

            foreach ($callbacks as $callback) {
                $csvData[] = [
                    $callback->id ?? 'N/A',
                    $callback->product?->name ?? 'N/A',
                    $callback->product?->sku ?? 'N/A',
                    $callback->quantity ?? 0,
                    $callback->uom ?? 'kg',
                    ucfirst(str_replace('_', ' ', $callback->reason ?? 'N/A')),
                    $callback->formatted_status ?? 'Pending',
                    $callback->callback_time ? \Carbon\Carbon::parse($callback->callback_time)->format('Y-m-d H:i') : 'N/A',
                    $callback->recordedBy?->name ?? 'N/A',
                    $callback->approvedBy?->name ?? 'N/A',
                    $callback->receivedBy?->name ?? 'N/A',
                    $callback->notes ?? 'N/A',
                ];
            }

            $filename = 'product-callbacks-' . now()->format('Y-m-d-His') . '.csv';
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
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'Export failed: ' . $e->getMessage());
            return;
        }
    }

    public function exportCallbacks()
    {
        $this->toast()->info('Export feature coming soon.')->send();
    }

    public function getStatusBadgeClass($status)
    {
        $value = $status instanceof \App\Enums\CallbackStatus ? $status->value : $status;

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
        $departmentFilterIds = $this->getDepartmentFilterIds();
        $products = \App\Models\Product::where('is_active', 1)
            ->where(function ($query) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $this->getBranchId());
            })
            ->when(! empty($departmentFilterIds), fn ($q) => $q->whereIn('sales_department_id', $departmentFilterIds))
            ->orderBy('name')
            ->get();

        $currentSalesShift = $this->currentSalesShiftId ? \App\Models\SalesShift::find($this->currentSalesShiftId) : null;

        return view('livewire.branch-dashboard.sales-dashboard.callbacks.index', [
            'rows' => $this->rows,
            'products' => $products,
            'currentSalesShift' => $currentSalesShift,
            'availableShifts' => $this->availableShifts,
        ]);
    }

    /**
     * @return array<int>
     */
    private function getDepartmentFilterIds(): array
    {
        if (! empty($this->departmentIds)) {
            return $this->departmentIds;
        }

        return $this->departmentId ? [(int) $this->departmentId] : [];
    }

    /**
     * Resolve equivalent sales department IDs for slug across branch/global scope.
     *
     * @return array<int>
     */
    private function resolveEquivalentSalesDepartmentIds(Department $department): array
    {
        $branchId = $this->getBranchId();

        $query = Department::query()
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->where('slug', $department->slug);

        if ($branchId) {
            $query->where(function ($scopeQuery) use ($branchId) {
                $scopeQuery->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $ids = $query->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array((int) $department->id, $ids, true)) {
            $ids[] = (int) $department->id;
        }

        return $ids;
    }
}
