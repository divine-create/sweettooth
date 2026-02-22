<?php

namespace App\Livewire\BranchDashboard\Production\Request;

use App\Enums\ProductionRequestSourceType;
use App\Events\ProductionRequest\ProgressUpdated;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductionProgressFeedback;
use App\Models\ProductionRequest;
use App\Models\ProductDispatch;
use App\Models\SalesProductionRequestItem;
use App\Services\SalesProductionDispatchService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class ProductionProgressTracker extends Component
{
    public $productionRequestId;
    public $selectedMilestone = 'in_production';
    public $progressPercentage = 0;
    public $notes = '';
    public $etaOverrideMinutes = null;
    public $request;
    public $showForm = false;
    public $dispatchQuantity = null;
    public $dispatchSalesDepartmentName = null;
    public $dispatchSalesDepartmentId = null;
    public array $availableSalesDepartments = [];
    public $milestones = [
        'started' => 'Production Started',
        'in_production' => 'In Production',
        'quality_check' => 'Quality Check',
        'completed' => 'Completed'
    ];

    public function mount($requestId = null)
    {
        if ($requestId) {
            $this->productionRequestId = $requestId;
            $this->loadRequest();
        } else {
            $this->loadAvailableSalesDepartments();
        }
    }

    public function loadRequest()
    {
        $this->request = ProductionRequest::with([
            'progressFeedback',
            'recipe:id,product_id,sku',
        ])
            ->find($this->productionRequestId);
        $this->etaOverrideMinutes = $this->request?->eta_override_minutes;

        $this->loadAvailableSalesDepartments();
        $this->dispatchSalesDepartmentId = null;
        $this->dispatchSalesDepartmentName = null;

        if (! $this->request) {
            return;
        }

        // Resolve dispatch target from sales-demand source linkage.
        if (
            $this->request->source_type === ProductionRequestSourceType::SALES_DEMAND->value
            && ! empty($this->request->source_id)
        ) {
            $salesItem = SalesProductionRequestItem::query()
                ->with('request.salesDepartment')
                ->find((int) $this->request->source_id);

            $salesDept = $salesItem?->request?->salesDepartment;
            if ($salesDept) {
                $this->dispatchSalesDepartmentId = (int) $salesDept->id;
                $this->dispatchSalesDepartmentName = $salesDept->name;
                return;
            }
        }

        if (! empty($this->availableSalesDepartments)) {
            $this->dispatchSalesDepartmentId = (int) $this->availableSalesDepartments[0]['id'];
            $this->dispatchSalesDepartmentName = (string) $this->availableSalesDepartments[0]['name'];
        }
    }

    public function updateProgress()
    {
        $this->validate([
            'selectedMilestone' => 'required|in:started,in_production,quality_check,completed',
            'progressPercentage' => 'required|integer|min:0|max:100',
            'etaOverrideMinutes' => 'nullable|integer|min:0|max:100000',
        ]);

        try {
            $feedback = ProductionProgressFeedback::create([
                'production_request_id' => $this->productionRequestId,
                'milestone' => $this->selectedMilestone,
                'progress_percentage' => $this->progressPercentage,
                'notes' => $this->notes ?: null,
                'updated_by_id' => auth()->id(),
            ]);

            // Update request status based on milestone
            $statusMap = [
                'started' => 'in_progress',
                'in_production' => 'in_progress',
                'quality_check' => 'quality_check',
                'completed' => 'completed',
            ];

            $updates = [
                'status' => $statusMap[$this->selectedMilestone],
                'eta_override_minutes' => $this->etaOverrideMinutes !== '' ? $this->etaOverrideMinutes : null,
            ];

            if (in_array($this->selectedMilestone, ['started', 'in_production']) && !$this->request->started_at) {
                $updates['started_at'] = now();
            }

            if ($this->selectedMilestone === 'completed') {
                $updates['completed_at'] = now();
            }

            $this->request->update($updates);

            // Broadcast progress update
            broadcast(new ProgressUpdated($this->request, $feedback))->toOthers();

            $this->loadRequest();
            $this->resetForm();

            session()->flash('success', 'Progress updated successfully');
            $this->dispatch('progressUpdated', ['requestId' => $this->productionRequestId]);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update progress: ' . $e->getMessage());
        }
    }

    public function dispatchToSales()
    {
        $this->validate([
            'dispatchQuantity' => 'required|numeric|min:0.01',
            'dispatchSalesDepartmentId' => 'required|exists:departments,id',
        ]);

        if (!$this->request) {
            session()->flash('error', 'No request loaded.');
            return;
        }

        $allowedSalesDeptIds = $this->allowedDispatchSalesDepartmentIds();
        if (empty($allowedSalesDeptIds)) {
            session()->flash('error', 'No eligible sales department is assigned to this product. Assign product sales department first.');

            return;
        }

        if (! in_array((int) $this->dispatchSalesDepartmentId, $allowedSalesDeptIds, true)) {
            session()->flash('error', 'Selected sales department is not allowed for this product.');

            return;
        }

        $remaining = max(0, ($this->request->planned_production_quantity ?? 0) - $this->request->dispatches()->sum('quantity'));

        if ($this->dispatchQuantity > $remaining) {
            session()->flash('error', 'Cannot dispatch more than remaining quantity ('.number_format($remaining,2).').');
            return;
        }

        $branchId = request()->query('b_id') ?? current_branch_id();
        $uomSymbol = $this->request->recipe?->unitOfMeasure?->symbol ?? null;
        /** @var SalesProductionDispatchService $dispatchService */
        $dispatchService = app(SalesProductionDispatchService::class);
        $linkedSalesItem = $dispatchService->resolveLinkedSalesItemFromProductionRequest($this->request);
        try {
            DB::transaction(function () use (
                $branchId,
                $uomSymbol,
                $dispatchService,
                $linkedSalesItem
            ) {
                ProductDispatch::create([
                    'production_request_id' => $this->request->id,
                    'sales_production_request_item_id' => $linkedSalesItem?->id,
                    'sales_department_id' => (int) $this->dispatchSalesDepartmentId,
                    'branch_id' => $branchId,
                    'production_shift_id' => $this->request->shift_id,
                    'product_id' => $this->request->recipe?->product_id,
                    'uom' => $uomSymbol,
                    'quantity' => $this->dispatchQuantity,
                    'status' => 'pending_verification',
                    'dispatch_date' => now()->toDateString(),
                    'dispatch_time' => now(),
                    'shift_type' => $this->request->shift?->shift_type,
                    'dispatched_by_id' => auth()->id(),
                    'dispatched_by_type' => auth()->user() ? get_class(auth()->user()) : null,
                ]);

                if ($linkedSalesItem) {
                    $dispatchService->markItemAsDispatched($linkedSalesItem);
                }
            });
        } catch (\Throwable $e) {
            session()->flash('error', 'Dispatch failed: ' . $e->getMessage());
            return;
        }

        $dept = Department::find((int) $this->dispatchSalesDepartmentId);
        $this->dispatchSalesDepartmentName = $dept?->name ?? 'Sales Dept';

        $this->dispatchQuantity = null;
        $this->loadRequest();
        session()->flash('success', 'Dispatched to '.$this->dispatchSalesDepartmentName.'.');
    }

    public function updatedDispatchSalesDepartmentId($value): void
    {
        if ($value && ! in_array((int) $value, $this->allowedDispatchSalesDepartmentIds(), true)) {
            $this->dispatchSalesDepartmentId = null;
            $this->dispatchSalesDepartmentName = null;
            session()->flash('error', 'Selected sales department is not allowed for this product.');

            return;
        }

        $dept = Department::find((int) $value);
        $this->dispatchSalesDepartmentName = $dept?->name ?? null;
    }

    private function loadAvailableSalesDepartments(): void
    {
        $branchId = request()->query('b_id') ?: current_branch_id();
        $allowedIds = $this->allowedDispatchSalesDepartmentIds();

        $query = Department::query()
            ->where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->orderBy('name')
            ->select('id', 'name');

        if (! empty($allowedIds)) {
            $query->whereIn('id', $allowedIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        $this->availableSalesDepartments = $query->get()
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])
            ->toArray();
    }

    /**
     * @return array<int>
     */
    private function allowedDispatchSalesDepartmentIds(): array
    {
        $product = $this->resolveDispatchProduct();
        if (! $product || ! $product->sales_department_id) {
            return [];
        }

        $ownerDept = Department::query()
            ->select('id', 'slug')
            ->find((int) $product->sales_department_id);

        if (! $ownerDept) {
            return [(int) $product->sales_department_id];
        }

        $branchId = request()->query('b_id') ?: current_branch_id();

        $ids = Department::query()
            ->whereHas('category', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['sales']);
            })
            ->where('slug', $ownerDept->slug)
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($scopeQuery) use ($branchId) {
                    $scopeQuery->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! in_array((int) $product->sales_department_id, $ids, true)) {
            $ids[] = (int) $product->sales_department_id;
        }

        return array_values(array_unique($ids));
    }

    private function resolveDispatchProduct(): ?Product
    {
        if (! $this->request) {
            return null;
        }

        $productId = $this->request->recipe?->product_id;
        if ($productId) {
            return Product::query()->select('id', 'sales_department_id')->find($productId);
        }

        $recipeSku = $this->request->recipe?->sku;
        if (! empty($recipeSku)) {
            return Product::query()
                ->select('id', 'sales_department_id')
                ->where('sku', $recipeSku)
                ->first();
        }

        return null;
    }

    private function resetForm()
    {
        $this->selectedMilestone = 'in_production';
        $this->progressPercentage = 0;
        $this->notes = '';
        $this->etaOverrideMinutes = $this->request?->eta_override_minutes;
        $this->showForm = false;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.production.request.production-progress-tracker');
    }
}
