<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\GlEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class BudgetVsActualReport extends Component
{
    use ExportsCsv;

    public ?int $periodId = null;

    public string $category = '';

    public function mount(): void
    {
        $branchId = current_branch_id();
        $current = AccountingPeriod::current()->where('branch_id', $branchId)->first();
        if ($current) {
            $this->periodId = $current->id;
        }
    }

    public function render()
    {
        $branchId = current_branch_id();

        $periods = AccountingPeriod::where('branch_id', $branchId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $rows = $this->buildReport($branchId);

        $totalBudget = collect($rows)->sum('budget');
        $totalActual = collect($rows)->sum('actual');
        $totalVariance = $totalActual - $totalBudget;

        return view('livewire.branch-dashboard.accounting.report.budget-vs-actual-report', [
            'periods' => $periods,
            'rows' => $rows,
            'totalBudget' => $totalBudget,
            'totalActual' => $totalActual,
            'totalVariance' => $totalVariance,
        ]);
    }

    public function exportToCsv()
    {
        $rows = $this->buildReport(current_branch_id());

        $header = ['Account #', 'Account Name', 'Account Type', 'Category', 'Budget', 'Actual', 'Variance', 'Variance %'];
        $csvRows = [];

        foreach ($rows as $row) {
            $csvRows[] = [
                $row['account_number'],
                $row['account_name'],
                $row['account_type'],
                $row['category'],
                number_format($row['budget'], 2, '.', ''),
                number_format($row['actual'], 2, '.', ''),
                number_format($row['variance'], 2, '.', ''),
                $row['variance_pct'] === null ? '' : number_format($row['variance_pct'], 2, '.', ''),
            ];
        }

        return $this->streamCsv($this->csvFilename('budget_vs_actual'), $header, $csvRows);
    }

    private function buildReport(string $branchId): array
    {
        $budgetQuery = Budget::with('glAccount')
            ->where('branch_id', $branchId)
            ->where('is_approved', true);

        if ($this->periodId) {
            $budgetQuery->where('accounting_period_id', $this->periodId);
        }

        if ($this->category) {
            $budgetQuery->where('category', $this->category);
        }

        $budgets = $budgetQuery->get()->keyBy('gl_account_id');

        // Get actual GL balances for the same accounts & period
        $actualQuery = GlEntry::query()
            ->where('branch_id', $branchId)
            ->where('status', 'posted')
            ->when($this->periodId, fn ($q) => $q->where('accounting_period_id', $this->periodId))
            ->when($budgets->isNotEmpty(), fn ($q) => $q->whereIn('gl_account_id', $budgets->keys()))
            ->selectRaw('gl_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('gl_account_id')
            ->get()
            ->keyBy('gl_account_id');

        $rows = [];

        foreach ($budgets as $accountId => $budget) {
            $actual = $actualQuery->get($accountId);
            $account = $budget->glAccount;

            // Net actual: debit-normal accounts → debit; credit-normal → credit
            $isDebitNormal = $account && in_array($account->account_type, ['asset', 'cogs', 'expense']);
            if ($actual) {
                $actualAmount = $isDebitNormal
                    ? ($actual->total_debit - $actual->total_credit)
                    : ($actual->total_credit - $actual->total_debit);
            } else {
                $actualAmount = 0;
            }

            $budgetAmount = (float) $budget->planned_amount;
            $variance = $actualAmount - $budgetAmount;
            $variancePct = $budgetAmount != 0 ? ($variance / $budgetAmount) * 100 : null;

            $rows[] = [
                'account_number' => $account?->account_number ?? '—',
                'account_name' => $account?->account_name ?? 'Unknown',
                'account_type' => $account?->account_type ?? '',
                'category' => $budget->category ?? '—',
                'budget' => $budgetAmount,
                'actual' => $actualAmount,
                'variance' => $variance,
                'variance_pct' => $variancePct,
            ];
        }

        return $rows;
    }
}
