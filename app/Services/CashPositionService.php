<?php

namespace App\Services;

use App\Models\CashPosition;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class CashPositionService
{
    /**
     * Create or get daily cash position
     */
    public function createDailyPosition(
        string $cashType,
        ?int $branchId = null,
        Carbon $date = null
    ): CashPosition {
        $date = $date ?? now();

        return CashPosition::updateOrCreate(
            [
                'cash_type' => $cashType,
                'branch_id' => $branchId,
                'position_date' => $date,
            ],
            [
                'opening_balance' => $this->getOpeningBalance($cashType, $branchId, $date),
                'sales_receipts' => 0,
                'withdrawals' => 0,
                'closing_balance' => 0,
            ]
        );
    }

    /**
     * Record sales receipts (cash from POS)
     */
    public function recordReceipt(
        string $cashType,
        float $amount,
        ?int $branchId = null,
        ?Carbon $date = null
    ): CashPosition {
        $date = $date ?? now();

        return DB::transaction(function () use ($cashType, $amount, $branchId, $date) {
            $position = $this->ensurePosition($cashType, $branchId, $date);

            $position->sales_receipts = floatval($position->sales_receipts) + $amount;
            $position->calculateBalance();

            return $position;
        });
    }

    /**
     * Record withdrawal (cash deposit to bank)
     */
    public function recordWithdrawal(
        string $cashType,
        float $amount,
        ?int $branchId = null,
        ?Carbon $date = null
    ): CashPosition {
        $date = $date ?? now();

        return DB::transaction(function () use ($cashType, $amount, $branchId, $date) {
            $position = $this->ensurePosition($cashType, $branchId, $date);

            $position->withdrawals = floatval($position->withdrawals) + $amount;
            $position->calculateBalance();

            return $position;
        });
    }

    /**
     * Record physical cash count
     */
    public function recordPhysicalCount(
        CashPosition $position,
        float $countAmount,
        ?int $countedByUserId = null
    ): array {
        return DB::transaction(function () use ($position, $countAmount, $countedByUserId) {
            $position->recordPhysicalCount($countAmount, $countedByUserId ?? auth()->id());

            $result = [
                'balanced' => $position->isBalanced(),
                'variance' => $position->variance_amount,
                'variance_percentage' => $position->getVariancePercentage(),
                'expected_balance' => $position->closing_balance,
                'actual_count' => $countAmount,
            ];

            return $result;
        });
    }

    /**
     * Get opening balance from previous day
     */
    protected function getOpeningBalance(
        string $cashType,
        ?int $branchId,
        Carbon $date
    ): float {
        $previousPosition = CashPosition::where('cash_type', $cashType)
            ->where('branch_id', $branchId)
            ->where('position_date', '<', $date)
            ->orderBy('position_date', 'desc')
            ->first();

        if ($previousPosition) {
            return floatval($previousPosition->physical_count ?? $previousPosition->closing_balance);
        }

        return 0;
    }

    /**
     * Ensure position exists
     */
    protected function ensurePosition(
        string $cashType,
        ?int $branchId,
        Carbon $date
    ): CashPosition {
        return CashPosition::firstOrCreate(
            [
                'cash_type' => $cashType,
                'branch_id' => $branchId,
                'position_date' => $date->toDateString(),
            ],
            [
                'opening_balance' => $this->getOpeningBalance($cashType, $branchId, $date),
                'sales_receipts' => 0,
                'withdrawals' => 0,
                'closing_balance' => 0,
            ]
        );
    }

    /**
     * Get daily cash position with reconciliation
     */
    public function getDailyPosition(
        string $cashType,
        ?int $branchId = null,
        ?Carbon $date = null
    ): CashPosition {
        $date = $date ?? now();

        return CashPosition::where('cash_type', $cashType)
            ->where('branch_id', $branchId)
            ->where('position_date', $date)
            ->firstOrFail();
    }

    /**
     * Get cash position for date range
     */
    public function getPositionsByDateRange(
        string $cashType,
        Carbon $start,
        Carbon $end,
        ?int $branchId = null
    ) {
        return CashPosition::where('cash_type', $cashType)
            ->where('branch_id', $branchId)
            ->whereBetween('position_date', [$start, $end])
            ->orderBy('position_date')
            ->get();
    }

    /**
     * Get all cash positions for a date (all types, all branches)
     */
    public function getDailySummary(?Carbon $date = null)
    {
        $date = $date ?? now();

        return CashPosition::where('position_date', $date)
            ->orderBy('cash_type')
            ->orderBy('branch_id')
            ->get();
    }

    /**
     * Get total cash across all types and branches
     */
    public function getTotalCashBalance(?Carbon $date = null): float
    {
        $date = $date ?? now();

        return CashPosition::where('position_date', $date)
            ->sum('closing_balance');
    }

    /**
     * Get positions with discrepancies
     */
    public function getPositionsWithVariance(
        ?Carbon $date = null,
        ?int $branchId = null
    ) {
        $query = CashPosition::whereNotNull('variance_amount')
            ->where('variance_amount', '!=', 0);

        if ($date) {
            $query->where('position_date', $date);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('position_date', 'desc')->get();
    }

    /**
     * Get uncounted positions (missing physical count)
     */
    public function getUncountedPositions(?Carbon $date = null)
    {
        $query = CashPosition::whereNull('physical_count')
            ->where('position_date', '<=', $date ?? now());

        return $query->orderBy('position_date')->get();
    }

    /**
     * Monthly cash summary
     */
    public function getMonthlySummary(int $year, int $month, ?int $branchId = null)
    {
        return CashPosition::whereYear('position_date', $year)
            ->whereMonth('position_date', $month)
            ->where(function ($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->orderBy('position_date')
            ->get();
    }

    /**
     * Get cash position report
     */
    public function getCashPositionReport(Carbon $date = null)
    {
        $date = $date ?? now();

        $positions = $this->getDailySummary($date);

        $report = [
            'date' => $date->toDateString(),
            'total_opening' => 0,
            'total_receipts' => 0,
            'total_withdrawals' => 0,
            'total_closing' => 0,
            'total_physical_count' => 0,
            'total_variance' => 0,
            'positions_by_type' => [],
        ];

        foreach ($positions as $position) {
            $report['total_opening'] += floatval($position->opening_balance);
            $report['total_receipts'] += floatval($position->sales_receipts);
            $report['total_withdrawals'] += floatval($position->withdrawals);
            $report['total_closing'] += floatval($position->closing_balance);
            $report['total_physical_count'] += floatval($position->physical_count ?? 0);
            $report['total_variance'] += floatval($position->variance_amount ?? 0);

            $typeKey = $position->cash_type;
            if (!isset($report['positions_by_type'][$typeKey])) {
                $report['positions_by_type'][$typeKey] = [
                    'total_opening' => 0,
                    'total_receipts' => 0,
                    'total_withdrawals' => 0,
                    'total_closing' => 0,
                    'counted' => 0,
                    'variance' => 0,
                    'positions' => [],
                ];
            }

            $report['positions_by_type'][$typeKey]['total_opening'] += floatval($position->opening_balance);
            $report['positions_by_type'][$typeKey]['total_receipts'] += floatval($position->sales_receipts);
            $report['positions_by_type'][$typeKey]['total_withdrawals'] += floatval($position->withdrawals);
            $report['positions_by_type'][$typeKey]['total_closing'] += floatval($position->closing_balance);
            $report['positions_by_type'][$typeKey]['counted'] += $position->physical_count ? 1 : 0;
            $report['positions_by_type'][$typeKey]['variance'] += floatval($position->variance_amount ?? 0);
            $report['positions_by_type'][$typeKey]['positions'][] = $position;
        }

        return $report;
    }
}
