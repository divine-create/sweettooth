<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\ProductionRequests;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\SalesProductionRequest;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use SalesDepartmentContext;

    public string $statusFilter = 'all';
    public string $search = '';
    public array $requests = [];

    public function mount(): void
    {
        $this->mountBase();
        $this->initializeDepartmentContext();
        $this->loadRequests();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadRequests();
    }

    public function updatedSearch(): void
    {
        $this->loadRequests();
    }

    protected function getModelClass(): string
    {
        return SalesProductionRequest::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->baseQuery()->pluck('id')->toArray();
    }

    private function loadRequests(): void
    {
        if (!$this->departmentId) {
            $this->requests = [];
            return;
        }

        $query = $this->baseQuery()->with([
            'items.productionDepartment:id,name',
            'items.recipe:id,product_name,sku',
            'items.product:id,name,sku',
        ]);

        $this->requests = $query->get()->map(function (SalesProductionRequest $request): array {
            $items = $request->items;
            $departments = $items->pluck('productionDepartment.name')
                ->filter()
                ->unique()
                ->values()
                ->implode(', ');

            $preview = $items->take(3)->map(function ($item) {
                return [
                    'name' => $item->recipe?->product_name ?? $item->product?->name ?? 'Unknown Recipe',
                    'quantity' => (float) $item->quantity_requested,
                ];
            })->toArray();

            return [
                'id' => $request->id,
                'request_number' => $request->request_number,
                'status' => is_string($request->status) ? $request->status : $request->status->value,
                'priority' => $request->priority,
                'items_count' => $items->count(),
                'total_quantity' => (float) $items->sum('quantity_requested'),
                'departments' => $departments ?: 'N/A',
                'preview' => $preview,
                'has_more_preview' => $items->count() > 3,
                'created_at' => $request->created_at?->format('M d, Y H:i') ?? 'N/A',
            ];
        })->toArray();
    }

    private function baseQuery()
    {
        $query = SalesProductionRequest::query()
            ->where('sales_department_id', $this->departmentId)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if (trim($this->search) !== '') {
            $search = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', $search)
                    ->orWhere('notes', 'like', $search)
                    ->orWhereHas('items.product', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', $search)
                            ->orWhere('sku', 'like', $search);
                    })
                    ->orWhereHas('items.recipe', function ($subQ) use ($search) {
                        $subQ->where('product_name', 'like', $search)
                            ->orWhere('sku', 'like', $search);
                    });
            });
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.production-requests.index');
    }
}
