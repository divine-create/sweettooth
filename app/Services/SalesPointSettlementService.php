<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Sale;
use App\Models\SalesPointSettlement;
use App\Support\CombinedSalesPoints;

/**
 * Records and summarises inter-sales-point settlements (Phase 3).
 *
 * A settlement is created for every sale line whose product's HOME sales point differs from
 * the point that collected the payment (and is not part of the same "combined point", which
 * intentionally rolls up). The collecting point then owes the home point that line amount.
 * See SALES_POINT_TRANSFER_SPEC.md §3.4 / §4.7.
 */
class SalesPointSettlementService
{
    /**
     * Create settlement rows for any cross-point lines on a completed sale.
     * Idempotent: skips if this sale already has settlements.
     */
    public function recordForSale(?Sale $sale): void
    {
        if (! $sale || ! $sale->department_id) {
            return;
        }

        if (SalesPointSettlement::where('sale_id', $sale->id)->exists()) {
            return;
        }

        $collectingDeptId = (int) $sale->department_id;
        $sale->loadMissing('saleItems.product:id,sales_department_id');

        $sameGroup = $this->sameGroupDepartmentIds($sale->branch_id, $collectingDeptId);

        foreach ($sale->saleItems as $item) {
            if (! $item->product_id || ! $item->product) {
                continue;
            }

            $homeDeptId = (int) ($item->product->sales_department_id ?? 0);

            // Own line, or a member of the same combined point → no settlement.
            if ($homeDeptId === 0 || $homeDeptId === $collectingDeptId || in_array($homeDeptId, $sameGroup, true)) {
                continue;
            }

            $amount = (float) $item->total;
            if ($amount <= 0) {
                continue;
            }

            SalesPointSettlement::create([
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'owed_by_department_id' => $collectingDeptId,
                'owed_to_department_id' => $homeDeptId,
                'amount' => $amount,
                'status' => 'open',
            ]);
        }
    }

    /**
     * Reconciliation view for a sales point: how much other points owe it (receivable,
     * from items of its that were sold elsewhere) and how much it owes others (payable,
     * cash it collected for others' items). Optional date scoping (by sale_time).
     *
     * @return array{receivable: float, payable: float, net: float}
     */
    public function balancesForDepartment(int $departmentId, ?string $date = null): array
    {
        $scope = function ($q) use ($date) {
            $q->where('status', 'open');
            if ($date) {
                $q->whereHas('sale', fn ($s) => $s->whereDate('sale_time', $date));
            }
        };

        $receivable = (float) SalesPointSettlement::where('owed_to_department_id', $departmentId)
            ->tap($scope)->sum('amount');

        $payable = (float) SalesPointSettlement::where('owed_by_department_id', $departmentId)
            ->tap($scope)->sum('amount');

        return [
            'receivable' => $receivable,
            'payable' => $payable,
            'net' => round($receivable - $payable, 2),
        ];
    }

    /**
     * Department ids that roll up into the same combined point as $departmentId
     * (so cross-point lines within the group are NOT settled).
     *
     * @return array<int, int>
     */
    protected function sameGroupDepartmentIds($branchId, int $departmentId): array
    {
        $dept = Department::find($departmentId);
        if (! $dept) {
            return [$departmentId];
        }

        $ids = CombinedSalesPoints::departmentIds($branchId, (string) $dept->slug);
        $ids[] = $departmentId;

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
