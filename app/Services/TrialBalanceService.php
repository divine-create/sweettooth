<?php

namespace App\Services;

use App\Models\GlEntry;
use App\Models\GlAccount;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    protected GeneralLedgerService $glService;

    /**
     * Cache TTL in seconds (5 minutes)
     */
    protected const CACHE_TTL = 300;

    public function __construct(GeneralLedgerService $glService)
    {
        $this->glService = $glService;
    }

    /**
     * Get trial balance for a period - OPTIMIZED
     *
     * Uses a single aggregated query instead of N+1 queries
     */
    public function getTrialBalance(?int $periodId = null, ?string $branchId = null): array
    {
        $branchId = $branchId ?? current_branch_id();
        $cacheKey = "trial_balance_{$branchId}_" . ($periodId ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($periodId, $branchId) {
            return $this->calculateTrialBalance($periodId, $branchId);
        });
    }

    /**
     * Calculate trial balance using optimized single query
     */
    protected function calculateTrialBalance(?int $periodId = null, ?string $branchId = null): array
    {
        // Use a single aggregated query to avoid N+1
        $query = DB::table('gl_accounts')
            ->leftJoin('gl_entries', function ($join) use ($periodId, $branchId) {
                $join->on('gl_accounts.id', '=', 'gl_entries.gl_account_id')
                    ->where('gl_entries.status', '=', 'posted')
                    ->whereNull('gl_entries.deleted_at');

                if ($periodId) {
                    $join->where('gl_entries.accounting_period_id', '=', $periodId);
                }
                if ($branchId) {
                    $join->where('gl_entries.branch_id', '=', $branchId);
                }
            })
            ->select(
                'gl_accounts.id as account_id',
                'gl_accounts.account_number',
                'gl_accounts.account_name',
                'gl_accounts.account_type',
                'gl_accounts.account_category',
                DB::raw('COALESCE(SUM(gl_entries.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(gl_entries.credit), 0) as total_credit')
            )
            ->where('gl_accounts.is_active', true)
            ->where('gl_accounts.account_type', '!=', 'header')
            ->whereNull('gl_accounts.deleted_at')
            ->groupBy(
                'gl_accounts.id',
                'gl_accounts.account_number',
                'gl_accounts.account_name',
                'gl_accounts.account_type',
                'gl_accounts.account_category'
            )
            ->orderBy('gl_accounts.account_number');

        $results = $query->get();

        $balances = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($results as $result) {
            $debit = (float) $result->total_debit;
            $credit = (float) $result->total_credit;

            // Only include accounts with activity
            if ($debit > 0 || $credit > 0) {
                $balances[] = [
                    'account_number' => $result->account_number,
                    'account_name' => $result->account_name,
                    'account_type' => $result->account_type,
                    'account_category' => $result->account_category,
                    'account_id' => $result->account_id,
                    'debit' => $debit,
                    'credit' => $credit,
                ];

                $totalDebits += $debit;
                $totalCredits += $credit;
            }
        }

        return [
            'accounts' => $balances,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'balanced' => abs($totalDebits - $totalCredits) < 0.01,
            'difference' => $totalDebits - $totalCredits,
            'period_id' => $periodId,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Clear trial balance cache
     */
    public function clearCache(?int $periodId = null, ?string $branchId = null): void
    {
        $branchId = $branchId ?? current_branch_id();
        Cache::forget("trial_balance_{$branchId}_" . ($periodId ?? 'all'));
    }

    /**
     * Get trial balance without cache (for fresh data)
     */
    public function getTrialBalanceFresh(?int $periodId = null, ?string $branchId = null): array
    {
        $this->clearCache($periodId, $branchId);
        return $this->getTrialBalance($periodId, $branchId);
    }

    /**
     * Check if GL is balanced
     */
    public function isBalanced(?int $periodId = null, ?string $branchId = null): bool
    {
        $branchId = $branchId ?? current_branch_id();
        $query = GlEntry::where('status', 'posted')->where('branch_id', $branchId);

        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        $totalDebits = (float) $query->sum('debit');
        $totalCredits = (float) $query->sum('credit');

        return abs($totalDebits - $totalCredits) < 0.01;
    }

    /**
     * Get trial balance for comparison
     */
    public function getComparativeTrialBalance(
        ?int $periodId1 = null,
        ?int $periodId2 = null,
        ?string $branchId = null,
    ): array {
        $tb1 = $this->getTrialBalance($periodId1, $branchId);
        $tb2 = $this->getTrialBalance($periodId2, $branchId);

        $accounts = [];
        $allAccounts = array_merge(
            array_column($tb1['accounts'], null, 'account_id'),
            array_column($tb2['accounts'], null, 'account_id'),
        );

        foreach ($allAccounts as $accountId => $account) {
            $debit1 = 0;
            $credit1 = 0;
            $debit2 = 0;
            $credit2 = 0;

            if (isset($tb1['accounts'])) {
                $found = array_search($accountId, array_column($tb1['accounts'], 'account_id'));
                if ($found !== false) {
                    $debit1 = $tb1['accounts'][$found]['debit'];
                    $credit1 = $tb1['accounts'][$found]['credit'];
                }
            }

            if (isset($tb2['accounts'])) {
                $found = array_search($accountId, array_column($tb2['accounts'], 'account_id'));
                if ($found !== false) {
                    $debit2 = $tb2['accounts'][$found]['debit'];
                    $credit2 = $tb2['accounts'][$found]['credit'];
                }
            }

            $accounts[] = [
                'account_id' => $accountId,
                'account_number' => $account['account_number'] ?? '',
                'account_name' => $account['account_name'] ?? '',
                'debit1' => $debit1,
                'credit1' => $credit1,
                'debit2' => $debit2,
                'credit2' => $credit2,
                'debit_change' => $debit2 - $debit1,
                'credit_change' => $credit2 - $credit1,
            ];
        }

        return [
            'accounts' => $accounts,
            'period1_id' => $periodId1,
            'period2_id' => $periodId2,
            'period1_totals' => $tb1,
            'period2_totals' => $tb2,
        ];
    }

    /**
     * Get balancing report
     */
    public function getBalancingReport(?int $periodId = null, ?string $branchId = null): array
    {
        $branchId = $branchId ?? current_branch_id();
        $query = GlEntry::where('status', 'posted')->where('branch_id', $branchId);

        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        $totalDebits = (float) $query->sum('debit');
        $totalCredits = (float) $query->sum('credit');
        $difference = abs($totalDebits - $totalCredits);

        return [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => $difference,
            'is_balanced' => $difference < 0.01,
            'entry_count' => $query->count(),
            'accounts_with_entries' => GlEntry::where('status', 'posted')
                ->where('branch_id', $branchId)
                ->when($periodId, fn($q) => $q->where('accounting_period_id', $periodId))
                ->distinct('gl_account_id')
                ->count(),
        ];
    }

    /**
     * Export trial balance to array
     */
    public function exportTrialBalance(?int $periodId = null, ?string $branchId = null): array
    {
        $tb = $this->getTrialBalance($periodId, $branchId);

        $data = [];
        foreach ($tb['accounts'] as $account) {
            $data[] = [
                'Account Number' => $account['account_number'],
                'Account Name' => $account['account_name'],
                'Debit' => $account['debit'] > 0 ? number_format($account['debit'], 2) : '',
                'Credit' => $account['credit'] > 0 ? number_format($account['credit'], 2) : '',
            ];
        }

        $data[] = [
            'Account Number' => 'TOTAL',
            'Account Name' => '',
            'Debit' => number_format($tb['total_debits'], 2),
            'Credit' => number_format($tb['total_credits'], 2),
        ];

        return $data;
    }
}
