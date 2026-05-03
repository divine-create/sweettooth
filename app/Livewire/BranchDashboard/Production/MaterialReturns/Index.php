<?php

namespace App\Livewire\BranchDashboard\Production\MaterialReturns;

use App\Livewire\BaseComponent;
use App\Models\ProductionMaterialReturn;
use App\Models\Department;
use App\Models\Shift;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $deptSlug = null;

    public ?int $perPage = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount($deptSlug = null)
    {
        if ($deptSlug) {
            $this->deptSlug = $deptSlug;
        }
        
        $this->startDate = \Carbon\Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = \Carbon\Carbon::today()->format('Y-m-d');
    }

    protected function getModelClass(): string
    {
        return ProductionMaterialReturn::class;
    }

    protected function getAllSelectableIds(): array
    {
        return ProductionMaterialReturn::where('branch_id', $this->getBranchId())->pluck('id')->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function getRowsProperty()
    {
        $branchId = $this->getBranchId();

        $query = ProductionMaterialReturn::with(['item', 'shift', 'returnedBy', 'approvedBy', 'department'])
            ->where('branch_id', $branchId);

        if ($this->deptSlug) {
            $query->whereHas('department', function ($q) {
                $q->where('slug', $this->deptSlug);
            });
        }

        if ($this->search) {
            $query->whereHas('item', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function render()
    {
        $stats = [
            'total' => $this->getStatsQuery()->count(),
            'pending' => $this->getStatsQuery()->where('status', 'pending_approval')->count(),
            'approved' => $this->getStatsQuery()->where('status', 'approved')->count(),
            'rejected' => $this->getStatsQuery()->where('status', 'rejected')->count(),
        ];

        return view('livewire.branch-dashboard.production.material-returns.index', [
            'rows' => $this->rows,
            'stats' => $stats,
        ]);
    }

    private function getStatsQuery()
    {
        $query = ProductionMaterialReturn::where('branch_id', $this->getBranchId());
        
        if ($this->deptSlug) {
            $query->whereHas('department', function ($q) {
                $q->where('slug', $this->deptSlug);
            });
        }
        
        return $query;
    }
}
