<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\GlEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class BranchComparisonReport extends Component
{
    public ?int $periodId = null;

    public function mount(): void
    {
        $current = AccountingPeriod::current()->first();
        if ($current) {
            $this->periodId = $current->id;
        }
    }

    public function render()
    {
        $periods = AccountingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        foreach ($branches as $branch) {
            $entries = GlEntry::where('gl_entries.branch_id', $branch->id)
                ->where('gl_entries.status', 'posted')
                ->when($this->periodId, fn ($q) => $q->where('gl_entries.accounting_period_id', $this->periodId))
                ->join('gl_accounts', 'gl_entries.gl_account_id', '=', 'gl_accounts.id')
                ->selectRaw("
                    SUM(CASE WHEN gl_accounts.account_type IN ('revenue') THEN gl_entries.credit - gl_entries.debit ELSE 0 END) as revenue,
                    SUM(CASE WHEN gl_accounts.account_type IN ('cogs') THEN gl_entries.debit - gl_entries.credit ELSE 0 END) as cogs,
                    SUM(CASE WHEN gl_accounts.account_type IN ('expense') THEN gl_entries.debit - gl_entries.credit ELSE 0 END) as opex
                ")
                ->first();

            $revenue = (float) ($entries->revenue ?? 0);
            $cogs = (float) ($entries->cogs ?? 0);
            $opex = (float) ($entries->opex ?? 0);
            $grossProfit = $revenue - $cogs;
            $netIncome = $grossProfit - $opex;
            $margin = $revenue > 0 ? ($netIncome / $revenue) * 100 : null;

            $rows[] = [
                'branch' => $branch->name,
                'branch_id' => $branch->id,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'opex' => $opex,
                'net_income' => $netIncome,
                'net_margin' => $margin,
            ];
        }

        // Sort by revenue descending
        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        $totals = [
            'revenue' => array_sum(array_column($rows, 'revenue')),
            'cogs' => array_sum(array_column($rows, 'cogs')),
            'gross_profit' => array_sum(array_column($rows, 'gross_profit')),
            'opex' => array_sum(array_column($rows, 'opex')),
            'net_income' => array_sum(array_column($rows, 'net_income')),
        ];

        return view('livewire.branch-dashboard.accounting.report.branch-comparison-report', [
            'periods' => $periods,
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }
}
