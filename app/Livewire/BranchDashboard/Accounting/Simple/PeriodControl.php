<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\AccountingPeriod;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class PeriodControl extends Component
{
    use WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public bool $showCreate = false;
    public ?int $quantity = 12;
    public ?string $search = null;
    public int $year;
    public int $month;
    public string $period_start = '';
    public string $period_end = '';
    public string $status = 'open';

    public array $headers = [
        ['index' => 'period', 'label' => 'Period'],
        ['index' => 'period_start', 'label' => 'Start'],
        ['index' => 'period_end', 'label' => 'End'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Action', 'display' => true],
    ];

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
        $this->period_start = now()->startOfMonth()->toDateString();
        $this->period_end = now()->endOfMonth()->toDateString();
    }

    public function updatedYear(): void
    {
        $this->syncPeriodDates();
    }

    public function updatedMonth(): void
    {
        $this->syncPeriodDates();
    }

    private function syncPeriodDates(): void
    {
        try {
            $start = now()->setDate($this->year, $this->month, 1)->startOfMonth();
            $this->period_start = $start->toDateString();
            $this->period_end = $start->copy()->endOfMonth()->toDateString();
        } catch (\Throwable $e) {
            // Ignore invalid date input; validation handles it on submit.
        }
    }

    public function toggleCreate(): void
    {
        $this->showCreate = ! $this->showCreate;
    }

    public function createPeriod(): void
    {
        $this->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'status' => 'required|string|in:open,closed,locked',
        ]);

        $exists = AccountingPeriod::query()
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->exists();

        if ($exists) {
            $this->addError('month', 'This period already exists.');
            return;
        }

        AccountingPeriod::create([
            'year' => $this->year,
            'month' => $this->month,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'status' => $this->status,
        ]);

        $this->showCreate = false;
        session()->flash('success', 'Accounting period created.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedQuantity(): void
    {
        $this->resetPage();
    }
    public function closePeriod(int $periodId): void
    {
        $period = AccountingPeriod::find($periodId);
        if (! $period || $period->status === 'closed') {
            return;
        }

        $period->update([
            'status' => 'closed',
            'closed_by_id' => auth()->id(),
            'closed_at' => now(),
        ]);
    }

    public function reopenPeriod(int $periodId): void
    {
        $period = AccountingPeriod::find($periodId);
        if (! $period || $period->status !== 'closed') {
            return;
        }

        $period->update([
            'status' => 'open',
            'closed_by_id' => null,
            'closed_at' => null,
        ]);
    }

    public function lockPeriod(int $periodId): void
    {
        $period = AccountingPeriod::find($periodId);
        if (! $period || $period->status === 'locked') {
            return;
        }

        $period->update([
            'status' => 'locked',
        ]);
    }

    public function render()
    {
        $periods = AccountingPeriod::query()
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('year', 'like', '%' . $this->search . '%')
                        ->orWhere('month', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('period_start', 'desc')
            ->paginate((int) ($this->quantity ?? 12));

        return view('livewire.branch-dashboard.accounting.simple.period-control', [
            'rows' => $periods,
            'headers' => $this->headers,
        ]);
    }
}
