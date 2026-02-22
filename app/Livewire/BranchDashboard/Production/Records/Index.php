<?php

namespace App\Livewire\BranchDashboard\Production\Records;

use App\Models\Department;
use App\Models\ProductionRecord;
use App\Models\ProductionRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use WithPagination, Interactions;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    #[Url(keep: true)]
    public string $tab = 'records';

    public ?Department $department = null;

    public string $search = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public int $perPage = 20;

    public function mount($deptSlug = null)
    {
        $deptSlug = $deptSlug
            ?? request()->query('dept_slug')
            ?? request()->query('deptSlug');

        if ($deptSlug) {
            $this->dept_slug = $deptSlug;
            $branchId = $this->getBranchId();
            $this->department = Department::where('slug', $deptSlug)
                ->when($branchId, function ($q) use ($branchId) {
                    $q->where(function ($sub) use ($branchId) {
                        $sub->where('branch_id', $branchId)
                            ->orWhereNull('branch_id');
                    });
                })
                ->first();

            if (!$this->department) {
                abort(404, 'Department not found');
            }
        }
    }

    public function getBranchId()
    {
        return $this->b_id ? $this->b_id : request()->query('b_id');
    }

    public function updatedSearch()
    {
        $this->resetPage('recordsPage');
        $this->resetPage('requestsPage');
    }

    public function updatedDateFrom()
    {
        $this->resetPage('recordsPage');
        $this->resetPage('requestsPage');
    }

    public function updatedDateTo()
    {
        $this->resetPage('recordsPage');
        $this->resetPage('requestsPage');
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    protected function applyDateFilter(Builder $query, string $column): Builder
    {
        if ($this->dateFrom) {
            $query->whereDate($column, '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate($column, '<=', $this->dateTo);
        }

        return $query;
    }

    protected function recordsQuery(): Builder
    {
        $branchId = $this->getBranchId();
        $departmentId = $this->department?->id;

        $query = ProductionRecord::query()
            ->with(['recipe.product', 'producedBy'])
            ->when($departmentId, function ($q) use ($departmentId, $branchId) {
                $q->whereHas('recipe', function ($recipe) use ($departmentId, $branchId) {
                    $recipe->where('department_id', $departmentId)
                        ->when($branchId, function ($recipe) use ($branchId) {
                            $recipe->where(function ($sub) use ($branchId) {
                                $sub->where('branch_id', $branchId)
                                    ->orWhereNull('branch_id');
                            });
                        });
                });
            });

        if ($this->search !== '') {
            $search = '%' . $this->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', $search)
                    ->orWhereHas('recipe', function ($recipe) use ($search) {
                        $recipe->where('product_name', 'like', $search)
                            ->orWhereHas('product', function ($product) use ($search) {
                                $product->where('name', 'like', $search)
                                    ->orWhere('sku', 'like', $search);
                            });
                    });
            });
        }

        $this->applyDateFilter($query, 'production_time');

        return $query->orderByDesc('production_time')->orderByDesc('id');
    }

    protected function requestsQuery(): Builder
    {
        $branchId = $this->getBranchId();
        $departmentId = $this->department?->id;

        $query = ProductionRequest::query()
            ->with(['recipe', 'itemRequest', 'shift'])
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('itemRequest', function ($item) use ($branchId) {
                    $item->where('branch_id', $branchId);
                });
            })
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('production_department_id', $departmentId);
            });

        if ($this->search !== '') {
            $search = '%' . $this->search . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('itemRequest', function ($item) use ($search) {
                    $item->where('request_number', 'like', $search);
                })->orWhereHas('recipe', function ($recipe) use ($search) {
                    $recipe->where('product_name', 'like', $search);
                });
            });
        }

        $this->applyDateFilter($query, 'created_at');

        return $query->orderByDesc('created_at');
    }

    protected function analyticsPayload(): array
    {
        $branchId = $this->getBranchId();
        $departmentId = $this->department?->id;
        $from = Carbon::today()->subDays(6)->startOfDay();
        $to = Carbon::today()->endOfDay();

        $recordQuery = ProductionRecord::query()
            ->when($departmentId, function ($q) use ($departmentId, $branchId) {
                $q->whereHas('recipe', function ($recipe) use ($departmentId, $branchId) {
                    $recipe->where('department_id', $departmentId)
                        ->when($branchId, function ($recipe) use ($branchId) {
                            $recipe->where(function ($sub) use ($branchId) {
                                $sub->where('branch_id', $branchId)
                                    ->orWhereNull('branch_id');
                            });
                        });
                });
            })
            ->whereBetween('production_time', [$from, $to]);

        $totalProduced = (float) $recordQuery->sum('quantity_produced');
        $totalApproved = (float) $recordQuery->sum('quantity_approved');
        $totalRejected = (float) $recordQuery->sum('quantity_rejected');

        $requestsOpen = ProductionRequest::query()
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('itemRequest', function ($item) use ($branchId) {
                    $item->where('branch_id', $branchId);
                });
            })
            ->when($departmentId, function ($q) use ($departmentId) {
                $q->where('production_department_id', $departmentId);
            })
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        return [
            'totalProduced' => $totalProduced,
            'totalApproved' => $totalApproved,
            'totalRejected' => $totalRejected,
            'requestsOpen' => $requestsOpen,
            'rangeLabel' => $from->format('M d') . ' - ' . $to->format('M d'),
        ];
    }

    public function render()
    {
        $records = $this->tab === 'records'
            ? $this->recordsQuery()->paginate($this->perPage, ['*'], 'recordsPage')
            : null;

        $requests = $this->tab === 'requests'
            ? $this->requestsQuery()->paginate($this->perPage, ['*'], 'requestsPage')
            : null;

        $analytics = $this->tab === 'analytics'
            ? $this->analyticsPayload()
            : [];

        return view('livewire.branch-dashboard.production.records.index', [
            'records' => $records,
            'requests' => $requests,
            'analytics' => $analytics,
        ]);
    }
}
