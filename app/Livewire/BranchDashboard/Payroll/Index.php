<?php

namespace App\Livewire\BranchDashboard\Payroll;

use App\Livewire\BaseComponent;
use App\Models\Payroll;
use App\Models\PayrollRun;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public string $tab = 'runs';

    public ?string $search = null;

    public string $status_filter = '';

    public int $perPage = 20;

    protected function getModelClass(): string
    {
        return PayrollRun::class;
    }

    protected function getAllSelectableIds(): array
    {
        return [];
    }

    protected function getFilteredQuery()
    {
        return PayrollRun::query()->where('branch_id', current_branch_id());
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->resetPage();
        $this->search = null;
        $this->status_filter = '';
    }

    public function render()
    {
        $branchId = current_branch_id();

        $runsQuery = PayrollRun::with(['creator'])
            ->where('branch_id', $branchId);

        if ($this->search) {
            $runsQuery->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->status_filter) {
            $runsQuery->where('status', $this->status_filter);
        }

        $standaloneQuery = Payroll::with(['employee'])
            ->where('branch_id', $branchId)
            ->whereNull('payroll_run_id');

        if ($this->search) {
            $standaloneQuery->whereHas('employee', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'));
        }

        if ($this->status_filter) {
            $standaloneQuery->where('status', $this->status_filter);
        }

        $stats = [
            'total_runs'           => PayrollRun::where('branch_id', $branchId)->whereNotIn('status', ['cancelled'])->count(),
            'pending_approval'     => Payroll::where('branch_id', $branchId)->where('status', 'draft')->count(),
            'total_paid_this_month' => Payroll::where('branch_id', $branchId)
                ->where('status', 'paid')
                ->whereMonth('pay_period_end', now()->month)
                ->whereYear('pay_period_end', now()->year)
                ->sum('net_salary'),
        ];

        return view('livewire.branch-dashboard.payroll.index', [
            'runs'        => $runsQuery->orderByDesc('id')->paginate($this->perPage),
            'standalone'  => $standaloneQuery->orderByDesc('id')->paginate($this->perPage),
            'stats'       => $stats,
        ]);
    }
}
