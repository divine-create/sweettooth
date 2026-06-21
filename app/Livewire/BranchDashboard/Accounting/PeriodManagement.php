<?php

namespace App\Livewire\BranchDashboard\Accounting;

use App\Models\AccountingPeriod;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class PeriodManagement extends Component
{
    public ?int $selectedPeriodId = null;

    public bool $showCreateForm = false;

    public int $newYear;

    public int $newMonth;

    public string $newStatus = 'open';

    public function mount()
    {
        $currentPeriod = AccountingPeriod::current()->first();
        $this->selectedPeriodId = $currentPeriod?->id;
        $this->newYear = now()->year;
        $this->newMonth = now()->month;
    }

    public function render()
    {
        $periods = AccountingPeriod::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $selectedPeriod = $this->selectedPeriodId ? AccountingPeriod::find($this->selectedPeriodId) : null;

        return view('livewire.branch-dashboard.accounting.period-management', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
        ]);
    }

    public function createPeriod()
    {
        // Validate inputs
        if ($this->newYear < 2020 || $this->newYear > now()->year + 1) {
            $this->addError('newYear', 'Invalid year');

            return;
        }

        if ($this->newMonth < 1 || $this->newMonth > 12) {
            $this->addError('newMonth', 'Invalid month');

            return;
        }

        $branchId = current_branch_id();

        // Check if period already exists (for this branch)
        $exists = AccountingPeriod::where('year', $this->newYear)
            ->where('month', $this->newMonth)
            ->where('branch_id', $branchId)
            ->exists();

        if ($exists) {
            $this->addError('newMonth', 'Period already exists');

            return;
        }

        // Create new period
        $startDate = now()
            ->setYear($this->newYear)
            ->setMonth($this->newMonth)
            ->startOfMonth();

        $endDate = $startDate->copy()->endOfMonth();

        AccountingPeriod::create([
            'branch_id' => $branchId,
            'year' => $this->newYear,
            'month' => $this->newMonth,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'status' => $this->newStatus,
        ]);

        session()->flash('message', "Period {$this->newMonth}/{$this->newYear} created successfully");
        $this->showCreateForm = false;
        $this->reset(['newYear', 'newMonth', 'newStatus']);
        $this->dispatch('period-created');
    }

    public function closePeriod(int $periodId)
    {
        $period = AccountingPeriod::find($periodId);
        if (! $period) {
            return;
        }

        if ($period->status === 'closed') {
            session()->flash('error', 'Period is already closed');

            return;
        }

        $period->update([
            'status' => 'closed',
            'closed_by_id' => auth()->id(),
            'closed_at' => now(),
        ]);

        session()->flash('message', "Period {$period->month}/{$period->year} closed successfully");
        $this->dispatch('period-closed');
    }

    public function reopenPeriod(int $periodId)
    {
        $period = AccountingPeriod::find($periodId);
        if (! $period) {
            return;
        }

        if ($period->status !== 'closed') {
            session()->flash('error', 'Only closed periods can be reopened');

            return;
        }

        $period->update([
            'status' => 'open',
            'closed_by_id' => null,
            'closed_at' => null,
        ]);

        session()->flash('message', "Period {$period->month}/{$period->year} reopened successfully");
        $this->dispatch('period-reopened');
    }

    public function lockPeriod(int $periodId)
    {
        $period = AccountingPeriod::find($periodId);
        if (! $period) {
            return;
        }

        if ($period->status === 'locked') {
            session()->flash('error', 'Period is already locked');

            return;
        }

        $period->update([
            'status' => 'locked',
        ]);

        session()->flash('message', "Period {$period->month}/{$period->year} locked successfully");
        $this->dispatch('period-locked');
    }

    public function selectPeriod(int $periodId)
    {
        $this->selectedPeriodId = $periodId;
        $this->dispatch('period-selected', id: $periodId);
    }
}
