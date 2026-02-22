<?php

namespace App\Services;

use App\Models\GlAccount;
use App\Models\GlEntry;
use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BudgetService
{
    /**
     * Create budget for an account and period
     */
    public function createBudget(
        GlAccount $account,
        float $budgetAmount,
        AccountingPeriod $period,
        ?string $notes = null
    ): array {
        $branchId = function_exists('current_branch_id') ? current_branch_id() : null;
        $branchId = $branchId ?: auth()->user()?->branch_id;

        $budget = Budget::create([
            'branch_id' => $branchId,
            'gl_account_id' => $account->id,
            'accounting_period_id' => $period->id,
            'planned_amount' => $budgetAmount,
            'forecast_amount' => null,
            'description' => $notes,
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by_id' => auth()->id(),
            'approved_by_type' => auth()->user()?->getMorphClass() ?? User::class,
            'created_by_id' => auth()->id(),
        ]);

        return [
            'account_id' => $account->id,
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
            'period_id' => $period->id,
            'period' => $period->getDisplayName(),
            'budget_amount' => $budget->planned_amount,
            'actual_amount' => 0,
            'variance' => $budget->planned_amount,
            'variance_percentage' => 100,
            'notes' => $budget->description,
            'created_at' => $budget->created_at,
        ];
    }

    /**
     * Get budget vs actual for a period
     */
    public function getBudgetVsActual(AccountingPeriod $period): array
    {
        $expenseAccounts = GlAccount::where('account_type', 'expense')
            ->where('is_header', false)
            ->get();

        $budgetData = [];

        foreach ($expenseAccounts as $account) {
            $budgetAmount = $this->getAccountBudget($account, $period);
            $actual = $this->getAccountActual($account, $period);
            $variance = $budgetAmount - $actual;
            $variancePercentage = $budgetAmount > 0 ? ($variance / $budgetAmount) * 100 : 0;

            $budgetData[] = [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'budget' => $budgetAmount,
                'actual' => $actual,
                'variance' => $variance,
                'variance_percentage' => $variancePercentage,
                'status' => $this->determineStatus($variance, $budgetAmount),
            ];
        }

        return [
            'period' => $period->getDisplayName(),
            'as_of_date' => now()->toDateString(),
            'total_budget' => collect($budgetData)->sum('budget'),
            'total_actual' => collect($budgetData)->sum('actual'),
            'total_variance' => collect($budgetData)->sum('variance'),
            'accounts' => $budgetData,
            'summary' => $this->getBudgetSummary($budgetData),
        ];
    }

    /**
     * Get account budget amount (would be from budget table)
     */
    private function getAccountBudget(GlAccount $account, AccountingPeriod $period): float
    {
        $branchId = function_exists('current_branch_id') ? current_branch_id() : null;
        $branchId = $branchId ?: auth()->user()?->branch_id;

        $query = Budget::where('gl_account_id', $account->id)
            ->where('accounting_period_id', $period->id)
            ->where('is_approved', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (float) $query->sum('planned_amount');
    }

    /**
     * Get account actual spending
     */
    private function getAccountActual(GlAccount $account, AccountingPeriod $period): float
    {
        $entries = GlEntry::where('gl_account_id', $account->id)
            ->where('accounting_period_id', $period->id)
            ->where('status', 'posted')
            ->get();

        return floatval($entries->sum('debit'));
    }

    /**
     * Determine budget status
     */
    private function determineStatus(float $variance, float $budget): string
    {
        if ($budget == 0) {
            return 'no_budget';
        }

        $percentageVariance = ($variance / $budget) * 100;

        if ($percentageVariance >= -5) {
            return 'on_track';
        } elseif ($percentageVariance >= -15) {
            return 'watch';
        } else {
            return 'over_budget';
        }
    }

    /**
     * Get budget summary
     */
    private function getBudgetSummary(array $budgetData): array
    {
        $onTrack = collect($budgetData)->filter(fn($b) => $b['status'] === 'on_track')->count();
        $watch = collect($budgetData)->filter(fn($b) => $b['status'] === 'watch')->count();
        $overBudget = collect($budgetData)->filter(fn($b) => $b['status'] === 'over_budget')->count();

        return [
            'on_track_count' => $onTrack,
            'watch_count' => $watch,
            'over_budget_count' => $overBudget,
            'total_accounts' => count($budgetData),
            'on_track_percentage' => count($budgetData) > 0 ? ($onTrack / count($budgetData)) * 100 : 0,
        ];
    }

    /**
     * Get budget variance alerts
     */
    public function getBudgetAlerts(AccountingPeriod $period): Collection
    {
        $budgetVsActual = $this->getBudgetVsActual($period);
        
        return collect($budgetVsActual['accounts'])
            ->filter(fn($item) => $item['status'] !== 'on_track')
            ->map(function ($item) {
                return [
                    'account_number' => $item['account_number'],
                    'account_name' => $item['account_name'],
                    'budget' => $item['budget'],
                    'actual' => $item['actual'],
                    'variance' => $item['variance'],
                    'severity' => $item['status'] === 'over_budget' ? 'high' : 'medium',
                    'message' => $this->generateAlertMessage($item),
                ];
            });
    }

    /**
     * Generate alert message
     */
    private function generateAlertMessage(array $item): string
    {
        $budgetName = $item['account_name'];
        $percentageOver = abs($item['variance_percentage']);

        if ($item['status'] === 'over_budget') {
            return "{$budgetName} is {$percentageOver}% over budget";
        } else {
            return "{$budgetName} is within watch threshold";
        }
    }

    /**
     * Compare budgets across periods
     */
    public function compareBudgetsAcrossPeriods(
        GlAccount $account,
        int $numberOfPeriods = 3
    ): array {
        $periods = AccountingPeriod::orderBy('period_end', 'desc')
            ->limit($numberOfPeriods)
            ->get();

        $comparison = [];

        foreach ($periods as $period) {
            $budget = $this->getAccountBudget($account, $period);
            $actual = $this->getAccountActual($account, $period);

            $comparison[] = [
                'period' => $period->getDisplayName(),
                'budget' => $budget,
                'actual' => $actual,
                'variance' => $budget - $actual,
            ];
        }

        return [
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
            'periods' => $comparison,
            'trend' => $this->calculateTrend($comparison),
        ];
    }

    /**
     * Calculate budget trend
     */
    private function calculateTrend(array $comparison): string
    {
        if (empty($comparison)) {
            return 'no_data';
        }

        $variances = array_map(fn($p) => $p['variance'], $comparison);
        $recentVariance = $variances[0] ?? 0;
        $previousVariance = $variances[1] ?? 0;

        if ($recentVariance > $previousVariance) {
            return 'improving';
        } elseif ($recentVariance < $previousVariance) {
            return 'deteriorating';
        } else {
            return 'stable';
        }
    }

    /**
     * Set budget alert thresholds
     */
    public function setBudgetAlertThresholds(
        array $thresholds = []
    ): array {
        // Default thresholds
        $defaults = [
            'warning' => 85,    // Warn at 85% of budget
            'critical' => 95,   // Critical at 95% of budget
            'over_budget' => 100, // Over budget
        ];

        return array_merge($defaults, $thresholds);
    }

    /**
     * Generate budget report
     */
    public function generateBudgetReport(AccountingPeriod $period): array
    {
        $budgetVsActual = $this->getBudgetVsActual($period);
        $alerts = $this->getBudgetAlerts($period);

        return [
            'period' => $period->getDisplayName(),
            'generated_at' => now()->toDateTimeString(),
            'summary' => [
                'total_budget' => $budgetVsActual['total_budget'],
                'total_actual' => $budgetVsActual['total_actual'],
                'total_variance' => $budgetVsActual['total_variance'],
                'overall_variance_percentage' => $budgetVsActual['total_budget'] > 0
                    ? ($budgetVsActual['total_variance'] / $budgetVsActual['total_budget']) * 100
                    : 0,
            ],
            'accounts' => $budgetVsActual['accounts'],
            'alerts' => $alerts,
            'status_breakdown' => $budgetVsActual['summary'],
        ];
    }
}
