<?php

namespace App\Livewire\BranchDashboard\Production;

use App\Livewire\BaseComponent;
use App\Models\DailyProduce;
use App\Models\Department;
use App\Traits\Exportable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;
use Carbon\Carbon;

#[Layout('components.layouts.app.branch-dashboard')]
class DailyList extends BaseComponent
{
    use WithPagination, Interactions, Exportable;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $dept_slug = null;

    public ?int $quantity = 20;
    public ?string $search = null;
    public ?string $filterStatus = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Table headers
    public array $headers = [
        ['index' => 'id', 'label' => 'Production ID'],
        ['index' => 'produce_date', 'label' => 'Date'],
        ['index' => 'recipe', 'label' => 'Product'],
        ['index' => 'opening_quantity', 'label' => 'Opening'],
        ['index' => 'quantity_produced', 'label' => 'Produced'],
        ['index' => 'quantity_approved', 'label' => 'Approved'],
        ['index' => 'quantity_rejected', 'label' => 'Rejected'],
        ['index' => 'quality_percentage', 'label' => 'Quality %'],
        ['index' => 'closing_quantity', 'label' => 'Closing'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Actions', 'display' => true],
    ];

    protected array $bulkActions = [
        'delete' => ['label' => 'Delete Selected', 'method' => 'bulkDelete'],
        'export' => ['label' => 'Export Selected', 'method' => 'exportSelected'],
    ];

    protected function getModelClass(): string
    {
        return DailyProduce::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    protected function getFilteredQuery()
    {
        $query = DailyProduce::query()
            ->with(['recipe', 'shift.department']);

        if ($this->dept_slug) {
            $query->whereHas('shift.department', function ($q) {
                $q->where('slug', $this->dept_slug);
            });
        }

        if ($this->b_id) {
            $query->where('branch_id', $this->b_id);
        }

        return $query
            ->when($this->search, function ($query) {
                $query->where('recipe_id', 'like', '%' . $this->search . '%')
                      ->orWhereHas('recipe', function ($q) {
                          $q->where('product_name', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('produce_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('produce_date', '<=', $this->dateTo);
            })
            ->orderBy('produce_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public function getBranchId()
    {
        return $this->b_id ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->b_id = current_branch_id();
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
        $this->dateFrom = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
        $this->resetPage();
    }

    public function bulkDelete(string $message): void
    {
        DailyProduce::whereIn('id', $this->selectedIds)->delete();
        $this->dialog()->success('Success', $message)->send();
        $this->selectedIds = [];
    }

    public function confirmBulkDelete(): void
    {
        $this->dialog()
            ->question('Warning!', 'Are you sure you want to delete ' . count($this->selectedIds) . ' production record(s)?')
            ->confirm('Confirm Delete', 'bulkDelete', count($this->selectedIds) . ' record(s) deleted successfully!')
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
            session()->flash('info', 'No production records selected for export.');
            return;
        }

        $records = DailyProduce::whereIn('id', $this->selectedIds)
            ->with(['recipe', 'shift'])
            ->get();

        $this->export(
            'daily_production_' . date('Y-m-d'),
            $records,
            'exports.production.daily_produce'
        );

        session()->flash('success', count($this->selectedIds) . ' production records exported successfully.');
        $this->resetBulkSelection();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 20);

        return view('livewire.branch-dashboard.production.daily-list', [
            'headers' => $this->headers,
            'rows' => $rows,
        ])->layout('components.layouts.app.branch-dashboard');
    }
}
