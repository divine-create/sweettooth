<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\Transfers;

use App\Models\Department;
use App\Models\ProductStock;
use App\Models\SalesPointTransfer;
use App\Services\SalesPointTransferService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * Sales-point transfers (Phase 1): move a produced product's on-hand from THIS sales point
 * to another. See SALES_POINT_TRANSFER_SPEC.md. Supervisor+ only (route middleware).
 */
#[Layout('components.layouts.app.branch-dashboard')]
class Index extends Component
{
    use Interactions, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    #[Url(keep: true)]
    public ?string $salesDeptSlug = null;

    public ?Department $department = null;

    /** all | sent | incoming */
    public string $tab = 'sent';

    // Create form
    public ?int $to_department_id = null;

    public ?string $product_id = null;

    public $quantity = null;

    public string $notes = '';

    public function mount($salesDeptSlug = null): void
    {
        $this->salesDeptSlug = $salesDeptSlug ?? $this->salesDeptSlug;
        $this->b_id = request()->query('b_id', $this->b_id);

        $this->department = Department::where('slug', $this->salesDeptSlug)->first();

        if (! $this->department) {
            abort(404, 'Sales point not found');
        }
    }

    public function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id');
    }

    /** Other sales points this one can transfer to. */
    public function getToDepartmentsProperty()
    {
        $branchId = $this->getBranchId();

        return Department::query()
            ->whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['sales']))
            ->where('id', '!=', $this->department->id)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(fn ($sub) => $sub->where('branch_id', $branchId)->orWhereNull('branch_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Products with available stock at THIS sales point today (what can be sent). */
    public function getAvailableProductsProperty()
    {
        return ProductStock::query()
            ->where('product_stocks.department_id', $this->department->id)
            ->where('product_stocks.stock_date', Carbon::today()->toDateString())
            ->where('product_stocks.closing_quantity', '>', 0)
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->orderBy('products.name')
            ->get([
                'product_stocks.product_id',
                'products.name',
                'product_stocks.closing_quantity as available',
            ])
            ->unique('product_id')
            ->values();
    }

    public function getTransfersProperty()
    {
        $deptId = $this->department->id;

        return SalesPointTransfer::query()
            ->with(['product:id,name', 'fromDepartment:id,name', 'toDepartment:id,name'])
            ->when($this->tab === 'sent', fn ($q) => $q->where('from_department_id', $deptId))
            ->when($this->tab === 'incoming', fn ($q) => $q->where('to_department_id', $deptId))
            ->when($this->tab === 'all', fn ($q) => $q->where(fn ($sub) => $sub->where('from_department_id', $deptId)->orWhere('to_department_id', $deptId)))
            ->latest('id')
            ->paginate(15);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function createTransfer(SalesPointTransferService $service): void
    {
        $this->validate([
            'to_department_id' => 'required|integer|different:department.id',
            'product_id' => 'required|string',
            'quantity' => 'required|numeric|min:0.01',
        ], [], [
            'to_department_id' => 'destination sales point',
        ]);

        try {
            $service->transfer(
                (int) $this->department->id,
                (int) $this->to_department_id,
                (string) $this->product_id,
                (float) $this->quantity,
                current_actor(),
                ['transfer_type' => 'rebalance', 'notes' => $this->notes ?: null],
            );

            $this->reset(['to_department_id', 'product_id', 'quantity', 'notes']);
            $this->tab = 'sent';
            $this->resetPage();
            $this->toast()->success('Transfer completed.')->send();
        } catch (\Throwable $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function reverseTransfer(int $transferId, SalesPointTransferService $service): void
    {
        $transfer = SalesPointTransfer::find($transferId);
        if (! $transfer) {
            $this->toast()->error('Transfer not found.')->send();

            return;
        }

        try {
            $service->reverse($transfer, current_actor());
            $this->toast()->success('Transfer reversed.')->send();
        } catch (\Throwable $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function render()
    {
        return view('livewire.branch-dashboard.sales-dashboard.transfers.index', [
            'transfers' => $this->transfers,
            'toDepartments' => $this->toDepartments,
            'availableProducts' => $this->availableProducts,
        ]);
    }
}
