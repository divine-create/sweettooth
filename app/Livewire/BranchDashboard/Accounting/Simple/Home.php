<?php

namespace App\Livewire\BranchDashboard\Accounting\Simple;

use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class Home extends Component
{
    #[Url(keep: true)]
    public ?string $b_id = null;

    private function getBranchId(): ?string
    {
        return $this->b_id ?: request()->query('b_id') ?: current_branch_id();
    }

    public function render()
    {
        $branchId = $this->getBranchId();
        $period = AccountingPeriod::current()->first()
            ?? AccountingPeriod::open()->orderByDesc('period_start')->first()
            ?? AccountingPeriod::orderByDesc('period_start')->first();

        $adjustmentsQuery = StockMovement::query()
            ->where(function ($query) {
                $query
                    ->where('type', 'damaged')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'adjustment')
                            ->whereNotNull('adjustment_reason');
                    });
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        $stats = [
            'sales' => [
                'posted' => Sale::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'posted')->count(),
                'pending' => Sale::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'pending')->count(),
                'failed' => Sale::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'failed')->count(),
            ],
            'purchases' => [
                'posted' => Purchase::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'posted')->count(),
                'pending' => Purchase::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'pending')->count(),
                'failed' => Purchase::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'failed')->count(),
            ],
            'payments' => [
                'posted' => Payment::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'posted')->count(),
                'pending' => Payment::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'pending')->count(),
                'failed' => Payment::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('gl_posting_status', 'failed')->count(),
            ],
            'adjustments' => [
                'posted' => $adjustmentsQuery->clone()->where('gl_posting_status', 'posted')->count(),
                'pending' => $adjustmentsQuery->clone()->where('gl_posting_status', 'pending')->count(),
                'failed' => $adjustmentsQuery->clone()->where('gl_posting_status', 'failed')->count(),
            ],
        ];

        $bankAccounts = BankAccount::active()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->get();

        return view('livewire.branch-dashboard.accounting.simple.home', [
            'period' => $period,
            'stats' => $stats,
            'bankAccounts' => $bankAccounts,
        ]);
    }
}
