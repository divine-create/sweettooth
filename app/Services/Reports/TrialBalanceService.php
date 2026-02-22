<?php

namespace App\Services\Reports;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class TrialBalanceService
{
    /**
     * Generate trial balance report
     * Shows debit and credit balances for all accounts
     */
    public function generate(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null, ?string $branchId = null): array
    {
        $query = GlEntry::where('status', 'posted')
            ->selectRaw('gl_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->with('glAccount')
            ->groupBy('gl_account_id');

        if ($period) {
            $query->where('accounting_period_id', $period->id);
        }

        if ($startDate) {
            $query->whereDate('entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $entries = $query->get();

        $totalDebits = 0;
        $totalCredits = 0;
        $accounts = [];

        foreach ($entries as $entry) {
            if (!$entry->glAccount || !$entry->glAccount->is_active) {
                continue;
            }

            $accounts[] = [
                'account_number' => $entry->glAccount->account_number,
                'account_name' => $entry->glAccount->account_name,
                'account_type' => $entry->glAccount->account_type,
                'debit' => $entry->total_debit ?? 0,
                'credit' => $entry->total_credit ?? 0,
            ];

            $totalDebits += $entry->total_debit ?? 0;
            $totalCredits += $entry->total_credit ?? 0;
        }

        // Sort by account number
        usort($accounts, function ($a, $b) {
            return strcmp($a['account_number'], $b['account_number']);
        });

        return [
            'period' => $period ? $period->period_name : 'Custom Range',
            'generated_at' => now(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'accounts' => $accounts,
            'summary' => [
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
                'difference' => $totalDebits - $totalCredits,
            ],
        ];
    }

    /**
     * Validate trial balance (debits must equal credits)
     */
    public function validate(?AccountingPeriod $period = null): bool
    {
        $report = $this->generate($period);
        return $report['summary']['is_balanced'];
    }

    /**
     * Get trial balance with hierarchy
     */
    public function generateWithHierarchy(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $report = $this->generate($period, $startDate, $endDate);
        
        $grouped = [];
        foreach ($report['accounts'] as $account) {
            $type = $account['account_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $account;
        }

        return [
            'period' => $report['period'],
            'generated_at' => $report['generated_at'],
            'accounts_by_type' => $grouped,
            'summary' => $report['summary'],
        ];
    }

    /**
     * Export trial balance to array
     */
    public function export(?AccountingPeriod $period = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        return $this->generate($period, $startDate, $endDate);
    }
}
