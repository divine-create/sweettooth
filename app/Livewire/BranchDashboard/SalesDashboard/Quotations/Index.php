<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Quotations;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Quotation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use SalesDepartmentContext, WithPagination, Interactions;

    #[Url]
    public string $statusFilter = 'open';

    #[Url]
    public string $search = '';

    /** Quotation currently shown in the view/print modal. */
    public ?int $viewingId = null;

    public function mount(): void
    {
        $this->mountBase();
        $this->initializeDepartmentContext();
        $this->departmentName = $this->departmentName ?: 'Quotations';
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Send the quotation into the POS cart for payment/conversion. */
    public function convertToSale(int $id): void
    {
        $quotation = $this->scopedQuery()->find($id);
        if (! $quotation) {
            $this->toast()->error('Quotation not found.')->send();
            return;
        }
        if (! $quotation->isOpen()) {
            $this->toast()->warning('Only open quotations can be converted.')->send();
            return;
        }

        $this->redirect(branch_route('branch-dashboard.sales-dashboard.pos.index', [
            'salesDeptSlug' => $this->salesDeptSlug,
            'quotation' => $quotation->id,
        ]), navigate: true);
    }

    public function cancelQuotation(int $id): void
    {
        $quotation = $this->scopedQuery()->find($id);
        if (! $quotation) {
            $this->toast()->error('Quotation not found.')->send();
            return;
        }
        if ($quotation->status === 'converted') {
            $this->toast()->warning('A converted quotation cannot be cancelled.')->send();
            return;
        }

        $quotation->update(['status' => 'cancelled']);
        $this->toast()->success('Quotation cancelled.')->send();
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function getViewingProperty(): ?Quotation
    {
        if (! $this->viewingId) {
            return null;
        }

        return $this->scopedQuery()->with('items')->find($this->viewingId);
    }

    protected function getModelClass(): string
    {
        return Quotation::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->scopedQuery()->pluck('id')->toArray();
    }

    /** Base query scoped to the current branch + sales department. */
    protected function scopedQuery()
    {
        return Quotation::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId));
    }

    public function render()
    {
        $quotations = $this->scopedQuery()
            ->with('items')
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('quotation_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term));
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                if ($this->statusFilter === 'open') {
                    // Open = not converted/cancelled and not past validity.
                    $q->whereNotIn('status', ['converted', 'cancelled', 'expired'])
                        ->where(fn ($w) => $w->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString()));
                } else {
                    $q->where('status', $this->statusFilter);
                }
            })
            ->latest()
            ->paginate(15);

        return view('livewire.branch-dashboard.sales-dashboard.quotations.index', [
            'quotations' => $quotations,
        ]);
    }
}
