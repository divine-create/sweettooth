<?php

namespace App\Livewire\BranchDashboard\Inventory\Reports;

use App\Models\DepartmentReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
#[Title('Inventory Reports')]
class Index extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $search = '';
    public string $status = 'all';
    public string $type = 'all';

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    #[On('branch-changed')]
    public function handleBranchChange($branchId): void
    {
        $this->b_id = $branchId;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $branchId = $this->b_id ?? current_branch_id();
        $search = trim($this->search);

        $query = DepartmentReport::query()
            ->with(['department', 'generatedBy'])
            ->forBranch($branchId)
            ->byCategory('inventory');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('report_name', 'like', "%{$search}%")
                    ->orWhere('report_type', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dept) use ($search) {
                        $dept->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('generatedBy', function ($actor) use ($search) {
                        $actor->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->status !== 'all') {
            $query->byStatus($this->status);
        }

        if ($this->type !== 'all') {
            $query->byType($this->type);
        }

        if ($this->dateFrom && $this->dateTo) {
            $query->dateRange($this->dateFrom, $this->dateTo);
        }

        $reports = $query->orderBy('report_date', 'desc')->paginate(12);

        $typeOptions = DepartmentReport::query()
            ->forBranch($branchId)
            ->byCategory('inventory')
            ->select('report_type')
            ->distinct()
            ->orderBy('report_type')
            ->pluck('report_type')
            ->filter()
            ->values();

        return view('livewire.branch-dashboard.inventory.reports.index', [
            'reports' => $reports,
            'typeOptions' => $typeOptions,
        ]);
    }
}
