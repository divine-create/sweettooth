<?php

namespace App\Livewire\BranchDashboard\Production\Reports\ProductionActivities;

use App\Livewire\Traits\RequiresDepartmentSelection;
use App\Models\DailyProduce;
use App\Models\ProductionRecord;
use App\Models\ProductionRequest;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Production Activities')]
class Index extends Component
{
    use RequiresDepartmentSelection;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $periodFilter = 'week';
    public ?string $customDateFrom = null;
    public ?string $customDateTo = null;
    public ?string $shiftType = 'all';
    public ?string $requestStatus = 'all';
    public $departmentId;

    #[On('branch-changed')]
    public function handleBranchChange($branchId): void
    {
        $this->b_id = $branchId;
        $this->initDepartments($branchId);
        $this->setDateRange();
    }

    public function mount(): void
    {
        $this->b_id = $this->b_id ?? current_branch_id();
        $this->departmentId = session('selected_department_id');
        $this->initDepartments($this->b_id);
        $this->setDateRange();
    }

    public function updatedPeriodFilter(): void
    {
        $this->setDateRange();
    }

    public function setDateRange(): void
    {
        switch ($this->periodFilter) {
            case 'today':
                $this->customDateFrom = Carbon::today()->toDateString();
                $this->customDateTo = Carbon::today()->toDateString();
                break;
            case 'yesterday':
                $this->customDateFrom = Carbon::yesterday()->toDateString();
                $this->customDateTo = Carbon::yesterday()->toDateString();
                break;
            case 'week':
                $this->customDateFrom = Carbon::now()->startOfWeek()->toDateString();
                $this->customDateTo = Carbon::now()->endOfWeek()->toDateString();
                break;
            case 'month':
                $this->customDateFrom = Carbon::now()->startOfMonth()->toDateString();
                $this->customDateTo = Carbon::now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->customDateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $this->customDateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'custom':
                break;
        }
    }

    private function dailyProducesQuery()
    {
        $branchId = $this->b_id ?? current_branch_id();

        return DailyProduce::query()
            ->with(['recipe.product', 'shift'])
            ->whereHas('shift', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->shiftType && $this->shiftType !== 'all') {
                    $q->where('shift_type', $this->shiftType);
                }
            })
            ->whereBetween('produce_date', [$this->customDateFrom, $this->customDateTo]);
    }

    private function productionRecordsQuery()
    {
        $branchId = $this->b_id ?? current_branch_id();

        return ProductionRecord::query()
            ->with(['recipe', 'producedBy', 'dailyProduce.shift'])
            ->whereHas('dailyProduce.shift', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->shiftType && $this->shiftType !== 'all') {
                    $q->where('shift_type', $this->shiftType);
                }
            })
            ->whereBetween('production_time', [$this->customDateFrom, $this->customDateTo]);
    }

    private function productionRequestsQuery()
    {
        $branchId = $this->b_id ?? current_branch_id();

        $query = ProductionRequest::query()
            ->with(['recipe', 'shift', 'itemRequest'])
            ->whereHas('shift', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
                if ($this->departmentId) {
                    $q->where('department_id', $this->departmentId);
                }
                if ($this->shiftType && $this->shiftType !== 'all') {
                    $q->where('shift_type', $this->shiftType);
                }
                if ($this->customDateFrom && $this->customDateTo) {
                    $q->whereBetween('shift_date', [$this->customDateFrom, $this->customDateTo]);
                }
            });

        if ($this->requestStatus && $this->requestStatus !== 'all') {
            $query->where('fulfillment_status', $this->requestStatus);
        }

        return $query;
    }

    public function render()
    {
        $dailyProduces = $this->dailyProducesQuery()->get();
        $records = $this->productionRecordsQuery()
            ->orderByDesc('production_time')
            ->limit(50)
            ->get();
        $requests = $this->productionRequestsQuery()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $totalProduced = $dailyProduces->sum('produced_quantity');
        $totalRequested = $dailyProduces->sum('requested_quantity');
        $totalSentOut = $dailyProduces->sum('sent_out_quantity');
        $totalVariance = $dailyProduces->sum('variance');

        $productionValue = $dailyProduces->sum(function ($row) {
            $price = $row->recipe?->product?->price;
            $fallback = $row->recipe?->cost_per_unit;
            $unitValue = $price ?? $fallback ?? 0;

            return (float) $row->produced_quantity * (float) $unitValue;
        });

        $unfulfilledQuantity = $dailyProduces->sum(function ($row) {
            $requested = (float) $row->requested_quantity;
            $produced = (float) $row->produced_quantity;
            return max(0, $requested - $produced);
        });

        return view('livewire.branch-dashboard.production.reports.production-activities.index', [
            'dailyProduces' => $dailyProduces,
            'productionRecords' => $records,
            'productionRequests' => $requests,
            'summary' => [
                'total_produced' => $totalProduced,
                'total_requested' => $totalRequested,
                'total_sent_out' => $totalSentOut,
                'total_variance' => $totalVariance,
                'production_value' => $productionValue,
                'unfulfilled_quantity' => $unfulfilledQuantity,
            ],
        ]);
    }
}
