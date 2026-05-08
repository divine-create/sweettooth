<?php

namespace App\Livewire\BranchDashboard\SalesDashboard\VarianceResolution;

use App\Livewire\BaseComponent;
use App\Livewire\Concerns\SalesDepartmentContext;
use App\Models\Callback;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('components.layouts.app.branch-dashboard')]
class Index extends BaseComponent
{
    use Interactions, SalesDepartmentContext, WithPagination;

    #[Url(keep: true)]
    public ?string $b_id = null;

    public ?string $search = null;
    public ?string $filterReason = null;
    public ?string $filterStatus = 'pending';
    public string $startDate = '';
    public string $endDate = '';

    // Write-off modal
    public bool $showWriteOffModal = false;
    public ?int $writeOffCallbackId = null;
    public string $writeOffNotes = '';

    // Correction modal
    public bool $showCorrectionModal = false;
    public ?int $correctionCallbackId = null;
    public string $correctedQuantity = '';
    public string $correctionNotes = '';
    public ?float $correctionCurrentQty = null;
    public ?float $correctionExpectedQty = null;

    public array $headers = [
        ['index' => 'product', 'label' => 'Product'],
        ['index' => 'callback_date', 'label' => 'Date'],
        ['index' => 'shift_type', 'label' => 'Shift'],
        ['index' => 'expected_qty', 'label' => 'Expected Closing'],
        ['index' => 'actual_qty', 'label' => 'Actual Closing'],
        ['index' => 'variance', 'label' => 'Variance'],
        ['index' => 'reason', 'label' => 'Reason'],
        ['index' => 'status', 'label' => 'Status'],
        ['index' => 'action', 'label' => 'Actions'],
    ];

    protected function getModelClass(): string
    {
        return Callback::class;
    }

    protected function getAllSelectableIds(): array
    {
        return $this->getFilteredQuery()->pluck('id')->toArray();
    }

    public function getBranchId()
    {
        return $this->b_id ?: $this->branchId ?: request()->query('b_id');
    }

    public function mount()
    {
        $this->mountBase();
        $this->initializeDepartmentContext();
        $this->checkAccess();
        $this->startDate = Carbon::today()->subDays(30)->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    protected function checkAccess(): void
    {
        $level = function_exists('get_user_role_level') ? get_user_role_level(auth()->user()) : 1;
        if ($level < 2) {
            abort(403, 'Supervisor or above access required.');
        }
    }

    protected function resolveEquivalentSalesDepartmentIds(): array
    {
        $branchId = $this->getBranchId();

        if ($this->salesDeptSlug) {
            $dept = Department::where('slug', $this->salesDeptSlug)
                ->where('branch_id', $branchId)
                ->first()
                ?? Department::where('slug', $this->salesDeptSlug)
                    ->whereNull('branch_id')
                    ->first();

            if ($dept) {
                return [(int) $dept->id];
            }
        }

        if ($this->departmentId) {
            return [(int) $this->departmentId];
        }

        return [];
    }

    protected function getFilteredQuery()
    {
        $salesDepartmentIds = $this->resolveEquivalentSalesDepartmentIds();

        return Callback::with(['product:id,name', 'productStock:id,closing_quantity', 'resolvedBy:id,name'])
            ->where('branch_id', $this->getBranchId())
            ->when(! empty($salesDepartmentIds), fn ($q) => $q->whereIn('department_id', $salesDepartmentIds))
            ->whereNotNull('product_stock_id')
            ->whereIn('reason', ['shortage', 'excess'])
            ->when($this->filterReason, fn ($q) => $q->where('reason', $this->filterReason))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$this->search}%")))
            ->when($this->startDate, fn ($q) => $q->whereDate('callback_date', '>=', $this->startDate))
            ->when($this->endDate, fn ($q) => $q->whereDate('callback_date', '<=', $this->endDate))
            ->orderBy('callback_date', 'desc');
    }

    // --- Write-off flow ---

    public function openWriteOffModal(int $callbackId): void
    {
        $this->writeOffCallbackId = $callbackId;
        $this->writeOffNotes = '';
        $this->showWriteOffModal = true;
    }

    public function confirmWriteOff(): void
    {
        $this->validate([
            'writeOffNotes' => 'required|string|min:5',
        ], [
            'writeOffNotes.required' => 'Please provide a reason for the write-off.',
            'writeOffNotes.min'      => 'Notes must be at least 5 characters.',
        ]);

        if (! $this->writeOffCallbackId) {
            $this->toast()->error('No variance selected.')->send();
            return;
        }

        $callback = Callback::findOrFail($this->writeOffCallbackId);

        DB::transaction(function () use ($callback) {
            $callback->update([
                'status'           => 'approved',
                'resolution_type'  => 'write_off',
                'resolved_by'      => auth()->id(),
                'resolved_at'      => now(),
                'resolution_notes' => $this->writeOffNotes,
            ]);
        });

        Cache::forget("pending_variances_{$this->getBranchId()}");

        $this->showWriteOffModal = false;
        $this->writeOffCallbackId = null;
        $this->writeOffNotes = '';
        $this->toast()->success('Variance written off successfully.')->send();
    }

    // --- Correction flow ---

    public function openCorrectionModal(int $callbackId): void
    {
        $callback = Callback::with('productStock')->findOrFail($callbackId);
        $this->correctionCallbackId = $callbackId;
        $this->correctionCurrentQty = (float) ($callback->productStock?->closing_quantity ?? $callback->quantity);
        $this->correctionExpectedQty = (float) ($callback->expected_quantity ?? 0);
        $this->correctedQuantity = (string) $this->correctionCurrentQty;
        $this->correctionNotes = '';
        $this->showCorrectionModal = true;
    }

    public function confirmCorrection(): void
    {
        $this->validate([
            'correctedQuantity' => 'required|numeric|min:0',
            'correctionNotes'   => 'required|string|min:5',
        ], [
            'correctedQuantity.required' => 'Please enter the corrected quantity.',
            'correctedQuantity.numeric'  => 'Quantity must be a number.',
            'correctedQuantity.min'      => 'Quantity cannot be negative.',
            'correctionNotes.required'   => 'Please provide a reason for the correction.',
            'correctionNotes.min'        => 'Notes must be at least 5 characters.',
        ]);

        if (! $this->correctionCallbackId) {
            $this->toast()->error('No variance selected.')->send();
            return;
        }

        $callback = Callback::with('productStock')->findOrFail($this->correctionCallbackId);
        $newQty = (float) $this->correctedQuantity;

        DB::transaction(function () use ($callback, $newQty) {
            // Bypass saving hook — same pattern as ShiftClosing
            DB::table('product_stocks')
                ->where('id', $callback->product_stock_id)
                ->update([
                    'closing_quantity' => $newQty,
                    'updated_at'       => now(),
                ]);

            $callback->update([
                'status'             => 'approved',
                'resolution_type'    => 'correction',
                'corrected_quantity' => $newQty,
                'resolved_by'        => auth()->id(),
                'resolved_at'        => now(),
                'resolution_notes'   => $this->correctionNotes,
            ]);
        });

        Cache::forget("pending_variances_{$this->getBranchId()}");

        $this->showCorrectionModal = false;
        $this->correctionCallbackId = null;
        $this->correctedQuantity = '';
        $this->correctionNotes = '';
        $this->correctionCurrentQty = null;
        $this->correctionExpectedQty = null;
        $this->toast()->success('Stock count corrected successfully.')->send();
    }

    public function render()
    {
        $rows = $this->getFilteredQuery()->paginate($this->quantity ?? 20);

        $pendingCount = Callback::where('branch_id', $this->getBranchId())
            ->whereNotNull('product_stock_id')
            ->whereIn('reason', ['shortage', 'excess'])
            ->where('status', 'pending')
            ->count();

        return view('livewire.branch-dashboard.sales-dashboard.variance-resolution.index', [
            'rows'         => $rows,
            'pendingCount' => $pendingCount,
        ]);
    }
}
