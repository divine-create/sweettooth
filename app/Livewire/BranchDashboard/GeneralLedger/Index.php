<?php

namespace App\Livewire\BranchDashboard\GeneralLedger;

use App\Livewire\BaseComponent;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use App\Traits\Exportable;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Carbon\Carbon;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use WithPagination, Interactions, Exportable;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?int $filterAccountId = null;
    public ?int $filterPeriodId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Table headers
    public array $headers = [
        ['index' => 'id', 'label' => 'Entry ID'],
        ['index' => 'entry_date', 'label' => 'Date'],
        ['index' => 'account', 'label' => 'Account'],
        ['index' => 'description', 'label' => 'Description'],
        ['index' => 'debit', 'label' => 'Debit'],
        ['index' => 'credit', 'label' => 'Credit'],
        ['index' => 'balance', 'label' => 'Balance'],
        ['index' => 'reference', 'label' => 'Reference'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Actions', 'display' => true],
    ];

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    protected function getModelClass(): string
    {
        return GlEntry::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        return GlEntry::query()
            ->with(['glAccount', 'period'])
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%')
                      ->orWhere('reference_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterAccountId, function ($query) {
                $query->where('gl_account_id', $this->filterAccountId);
            })
            ->when($this->filterPeriodId, function ($query) {
                $query->where('accounting_period_id', $this->filterPeriodId);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('entry_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('entry_date', '<=', $this->dateTo);
            })
            ->orderBy('entry_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public function mount()
    {
        $this->dateFrom = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = null;
        $this->filterStatus = null;
        $this->filterAccountId = null;
        $this->filterPeriodId = null;
        $this->dateFrom = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
        $this->resetPage();
    }

    public function bulkDelete(string $message): void
    {
        GlEntry::whereIn('id', $this->selectedIds)->delete();
        $this->dialog()->success('Success', $message)->send();
        $this->selectedIds = [];
    }

    public function confirmBulkDelete(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete ' . count($this->selectedIds) . ' ledger entries?')
            ->confirm('Confirm Delete', 'bulkDelete', count($this->selectedIds) . ' entries deleted successfully!')
            ->cancel('Cancel', 'cancelledBulkDelete', 'Bulk delete cancelled')
            ->send();
    }

    public function cancelledBulkDelete(string $message): void
    {
        $this->dialog()->info('Cancelled', $message)->send();
    }

    protected function exportSelected(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('info', 'No entries selected for export.');
            return;
        }

        $entries = GlEntry::whereIn('id', $this->selectedIds)
            ->with(['glAccount', 'period'])
            ->get();

        $this->export(
            'general_ledger_' . date('Y-m-d'),
            $entries,
            'exports.accounting.journal'
        );

        session()->flash('success', count($this->selectedIds) . ' entries exported successfully.');
        $this->resetBulkSelection();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 20);
        $periods = AccountingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return view('livewire.branch-dashboard.general-ledger.index', [
            'headers' => $this->headers,
            'rows' => $rows,
            'periods' => $periods,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
